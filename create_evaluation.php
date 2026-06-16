<?php
session_start();

/* PROTECTION FORMATEUR */
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'formateur') {
    header("Location: connexion.php");
    exit;
}

/* CONNEXION */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die($e->getMessage());
}

$formateur_id = $_SESSION['utilisateur_id'];
$message = "";

/* MATIÈRES DU FORMATEUR */
$matieres = $pdo->prepare("SELECT * FROM matieres WHERE formateur_id = ?");
$matieres->execute([$formateur_id]);
$matieres = $matieres->fetchAll();

/* =========================
   CRÉATION EXAMEN
========================= */
if (isset($_POST['creer'])) {

    $titre = trim($_POST['titre']);
    $matiere_id = $_POST['matiere_id'];
    $duree = intval($_POST['duree']);

    if ($titre && $matiere_id) {

        $stmt = $pdo->prepare("
            INSERT INTO examens (titre, description, formateur_id,  duree)
            VALUES (?, ?, ?,?)
        ");

        $stmt->execute([$titre, '', $formateur_id, $duree]);

        $_SESSION['examen_id'] = $pdo->lastInsertId();
        $_SESSION['matiere_id'] = $matiere_id;

        $message = "✔ Examen créé. Ajoute maintenant les questions.";
    } else {
        $message = "❌ Champs obligatoires";
    }
}
/* =========================
   AJOUT QUESTIONS
========================= */
if (isset($_POST['ajouter_question'])) {

    if (!isset($_SESSION['examen_id'])) {

        $message = "❌ Crée d'abord un examen";

    } else {

        $examen_id = $_SESSION['examen_id'];

        $question = trim($_POST['question']);
        $type = trim($_POST['type']);

        $choix_a = trim($_POST['choix_a']);
        $choix_b = trim($_POST['choix_b']);
        $choix_c = trim($_POST['choix_c']);
        $choix_d = trim($_POST['choix_d']);

        $reponse = trim($_POST['reponse']);

        $stmt = $pdo->prepare("
            INSERT INTO questions
            (
                examen_id,
                question,
                type,
                choix_a,
                choix_b,
                choix_c,
                choix_d,
                bonne_reponse
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute(array(
            $examen_id,
            $question,
            $type,
            $choix_a,
            $choix_b,
            $choix_c,
            $choix_d,
            strtoupper($reponse)
        ));

        $message = "✔ Question ajoutée";
    }
}
/* =========================
   PUBLIER EXAMEN
========================= */
if (isset($_POST['publier'])) {

    $id = $_POST['examen_id'];

    $stmt = $pdo->prepare("
        UPDATE examens
        SET statut = 'publié'
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $message = " Examen publié";
}

/* =========================
   FERMER EXAMEN
========================= */
if (isset($_POST['fermer'])) {

    $id = $_POST['examen_id'];

    $stmt = $pdo->prepare("
        UPDATE examens
        SET statut = 'fermé'
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $message = " Examen fermé";
}
/* =========================
   LISTE DES EXAMENS
========================= */
$liste_examens = $pdo->prepare("
    SELECT *
    FROM examens
    WHERE formateur_id = ?
    ORDER BY id DESC
");

$liste_examens->execute([$formateur_id]);
$liste_examens = $liste_examens->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Créer Examen</title>

<style>
body { font-family: Arial; background:#e8f5e9; padding:20px; }
.box { background:white; padding:20px; margin-bottom:20px; }
input, select { width:100%; padding:10px; margin-top:10px; }
button { padding:10px; background:#4CAF50; color:white; border:none; margin-top:10px; cursor:pointer; }
.msg { padding:10px; background:#dff0d8; margin-bottom:10px; }
</style>
</head>

<body>

<h2>Créer un examen</h2>

<?php if ($message): ?>
<div class="msg"><?= $message ?></div>
<?php endif; ?>

<!-- CRÉATION EXAMEN -->
<div class="box">
<form method="POST">

    <input type="number" name="duree" placeholder="Durée (minutes)" required>
    <input type="text" name="titre" placeholder="Titre examen" required>
    
    <select name="matiere_id" required>
        <option value="">-- Matière --</option>
        <?php foreach ($matieres as $m): ?>
            <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['nom']) ?>
            </option>
           
        <?php endforeach; ?>
    </select>

    <button name="creer">Créer examen</button>

</form>
</div>

<!-- AJOUT QUESTIONS -->
<?php if (isset($_SESSION['examen_id'])): ?>

<div class="box">
<h3>Ajouter des questions</h3>

<form method="POST">

    <select name="type" required>
        <option value="texte">Question texte</option>
        <option value="qcm">QCM</option>
    </select>

     <input type="text"
           name="question"
           placeholder="Question"
           required>

    <input type="text"
           name="choix_a"
           placeholder="Choix A">

    <input type="text"
           name="choix_b"
           placeholder="Choix B">

    <input type="text"
           name="choix_c"
           placeholder="Choix C">

    <input type="text"
           name="choix_d"
           placeholder="Choix D">

    <input type="text"
           name="reponse"
           placeholder="Bonne réponse (A,B,C,D ou texte)"
           required>

    <button type="submit" name="ajouter_question">
        Ajouter question
    </button>

</form>
</div>

<?php endif; ?>
<div class="box">

<h3>Mes examens</h3>

<table border="1" width="100%" cellpadding="10">

<tr>
    <th>Titre</th>
    <th>Statut</th>
    <th>Action</th>
</tr>

<?php foreach($liste_examens as $e): ?>

<tr>

    <td><?= htmlspecialchars($e['titre']) ?></td>

    <td><?= htmlspecialchars($e['statut']) ?></td>

    <td>

        <?php if($e['statut'] == 'brouillon'): ?>

            <form method="POST">
                <input type="hidden" name="examen_id" value="<?= $e['id'] ?>">
                <button type="submit" name="publier">
                    Publier
                </button>
            </form>

        <?php elseif($e['statut'] == 'publie'): ?>

            <form method="POST">
                <input type="hidden" name="examen_id" value="<?= $e['id'] ?>">
                <button type="submit" name="fermer">
                    Fermer
                </button>
            </form>

        <?php else: ?>

            <strong>Examen terminé</strong>

        <?php endif; ?>

    </td>

</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>