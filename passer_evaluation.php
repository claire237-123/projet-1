<?php
session_start();

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: connexion.php");
    exit;
}

$pdo = new PDO(
    "mysql:host=localhost;dbname=gestion_notes;charset=utf8",
    "root",
    ""
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$etudiant_id = $_SESSION['utilisateur_id'];
$examen_id = $_GET['id'];
$reqExamen = $pdo->prepare("
    SELECT duree
    FROM examens
    WHERE id = ?
");

$reqExamen->execute([$examen_id]);

$examen = $reqExamen->fetch(PDO::FETCH_ASSOC);

$duree_secondes = $examen['duree'] * 60;

/* =========================
   EMPÊCHER DOUBLE PASSAGE
========================= */

$check = $pdo->prepare("
    SELECT id
    FROM resultats_examens
    WHERE etudiant_id = ?
    AND examen_id = ?
");

$check->execute([
    $etudiant_id,
    $examen_id
]);

if ($check->fetch()) {
    ?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <title>Examen déjà effectué</title>

    <style>
    body{
        font-family:Arial;
        background:#e8f5e9;
        padding:40px;
        text-align:center;
    }

    .box{
        background:white;
        max-width:500px;
        margin:auto;
        padding:30px;
        border-radius:10px;
    }

    a{
        display:inline-block;
        margin-top:20px;
        padding:10px 20px;
        background:#4CAF50;
        color:white;
        text-decoration:none;
        border-radius:5px;
    }
    </style>

    </head>
    <body>

    <div class="box">

        <h2 style="color:red;">
            Examen déjà effectué
        </h2>

        <p>
            Vous avez déjà passé cet examen.
        </p>

        <a href="etudiant_evaluations.php">
            Retour
        </a>

    </div>

    </body>
    </html>

    <?php
    exit;
}

/* =========================
   QUESTIONS
========================= */

$stmt = $pdo->prepare("
    SELECT * FROM questions
    WHERE examen_id = ?
");

$stmt->execute([$examen_id]);

$questions = $stmt->fetchAll();

/* =========================
   TRAITEMENT EXAMEN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $score = 0;

    foreach ($questions as $q) {

    if(isset($_POST['q'.$q['id']])) {
        $rep = $_POST['q'.$q['id']];
    } else {
        $rep = "";
    }

    $bonne_reponse = strtolower(trim($q['bonne_reponse']));
    $reponse_etudiant = strtolower(trim($rep));

    $note_question = 0;

    if ($reponse_etudiant == $bonne_reponse) {
        $score++;
        $note_question = 1;
    }

    $insert = $pdo->prepare("
        INSERT INTO reponses_etudiants
        (
            etudiant_id,
            question_id,
            reponse,
            note
        )
        VALUES (?, ?, ?, ?)
    ");

    $insert->execute(array(
        $etudiant_id,
        $q['id'],
        $rep,
        $note_question
    ));
}
    /* NOTE SUR 20 */
    $note = ($score / count($questions)) * 20;

    /* ENREGISTRER RÉSULTAT FINAL */
    $pdo->prepare("
        INSERT INTO resultats_examens
        (
            etudiant_id,
            examen_id,
            note_finale
        )
        VALUES (?, ?, ?)
    ")->execute([
        $etudiant_id,
        $examen_id,
        $note
    ]);

    ?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <title>Résultat examen</title>

    <style>
    body{
        font-family:Arial;
        background:#e8f5e9;
        padding:40px;
        text-align:center;
    }

    .box{
        background:white;
        max-width:500px;
        margin:auto;
        padding:30px;
        border-radius:10px;
    }

    .note{
        font-size:35px;
        color:#4CAF50;
        margin-top:20px;
    }

    a{
        display:inline-block;
        margin-top:20px;
        padding:10px 20px;
        background:#4CAF50;
        color:white;
        text-decoration:none;
        border-radius:5px;
    }
    </style>

    </head>
    <body>

    <div class="box">

        <h2>
            Examen terminé
        </h2>

        <div class="note">
            <?= round($note, 2) ?> / 20
        </div>

        <a href="etudiant_evaluations.php">
            Retour
        </a>

    </div>

    </body>
    </html>

    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Passer examen</title>

<style>

body{
    font-family:Arial;
    background:#e8f5e9;
    padding:20px;
}

.box{
    background:white;
    padding:20px;
    margin-bottom:20px;
    border-radius:10px;
}

input{
    width:100%;
    padding:10px;
    margin-top:10px;
}

button{
    padding:12px 20px;
    background:#4CAF50;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

</style>
</head>
<body>
<div id="timer" style="
background:white;
padding:15px;
margin-bottom:20px;
font-size:24px;
font-weight:bold;
color:red;
text-align:center;
border-radius:10px;">
Chargement...
</div>
<h2>Passer examen</h2>

<form method="POST">

<?php foreach ($questions as $q): ?>

    <div class="box">

        <p>
            <strong>
                <?= htmlspecialchars($q['question']) ?>
            </strong>
        </p>

      <?php if($q['type'] == 'qcm'): ?>

    <label>
        <input type="radio"
               name="q<?= $q['id'] ?>"
               value="A"
               required>
        A - <?= htmlspecialchars($q['choix_a']) ?>
    </label>

    <br><br>

    <label>
        <input type="radio"
               name="q<?= $q['id'] ?>"
               value="B">
        B - <?= htmlspecialchars($q['choix_b']) ?>
    </label>

    <br><br>

    <label>
        <input type="radio"
               name="q<?= $q['id'] ?>"
               value="C">
        C - <?= htmlspecialchars($q['choix_c']) ?>
    </label>

    <br><br>

    <label>
        <input type="radio"
               name="q<?= $q['id'] ?>"
               value="D">
        D - <?= htmlspecialchars($q['choix_d']) ?>
    </label>

<?php else: ?>

    <input
        type="text"
        name="q<?= $q['id'] ?>"
        required
    >

<?php endif; ?>

    </div>

<?php endforeach; ?>

<button type="submit">
    Valider
</button>

</form>
<script>

let temps = <?= $duree_secondes ?>;

function countdown() {

    let minutes = Math.floor(temps / 60);
    let secondes = temps % 60;

    document.getElementById("timer").innerHTML =
        "Temps restant : " +
        minutes + " min " +
        secondes + " sec";

    if (temps <= 0) {

        alert("Temps écoulé ! L'examen va être envoyé.");

        document.forms[0].submit();

        return;
    }

    temps--;

}

countdown();

setInterval(countdown, 1000);

</script>
</body>
</html>