<?php
session_start();

/* =========================
   PROTECTION FORMATEUR
========================= */

if (
    !isset($_SESSION['utilisateur_id']) ||
    $_SESSION['role'] !== 'formateur'
) {
    header("Location: connexion.php");
    exit;
}

/* =========================
   CONNEXION BD
========================= */

try {

    $pdo = new PDO(
        "mysql:host=localhost;dbname=gestion_notes;charset=utf8",
        "root",
        ""
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erreur : " . $e->getMessage());
}

$formateur_id = $_SESSION['utilisateur_id'];

/* =========================
   LISTE DES CLASSES
========================= */

$classes = $pdo->query("
    SELECT *
    FROM classes
    ORDER BY nom
")->fetchAll(PDO::FETCH_ASSOC);

$classe_id = isset($_GET['classe_id'])
    ? (int)$_GET['classe_id']
    : 0;

/* =========================
   RÉCUPÉRER RÉSULTATS
========================= */

$sql = "

SELECT

    r.note_finale,
    r.date_passage,

    u.nom AS etudiant,

    e.titre AS examen,

    c.nom AS classe

FROM resultats_examens r

INNER JOIN utilisateurs u
    ON r.etudiant_id = u.id

INNER JOIN examens e
    ON r.examen_id = e.id

LEFT JOIN etudiants_classes ec
    ON u.id = ec.etudiant_id

LEFT JOIN classes c
    ON ec.classe_id = c.id

WHERE e.formateur_id = ?

";

$params = [$formateur_id];

if ($classe_id > 0) {

    $sql .= " AND c.id = ? ";

    $params[] = $classe_id;
}

$sql .= "

ORDER BY r.date_passage DESC

";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STATISTIQUES
========================= */

$nombre = count($resultats);

$moyenne = 0;
$meilleure_note = 0;

if ($nombre > 0) {

    $total = 0;

    foreach ($resultats as $r) {

        $total += $r['note_finale'];

        if ($r['note_finale'] > $meilleure_note) {
            $meilleure_note = $r['note_finale'];
        }
    }

    $moyenne = $total / $nombre;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Résultats examens</title>

<style>

body{
    font-family:Arial;
    background:#e8f5e9;
    padding:20px;
}

h2{
    color:#2e7d32;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th, td{
    padding:12px;
    border:1px solid #ccc;
    text-align:center;
}

th{
    background:#4CAF50;
    color:white;
}

.box{
    background:white;
    padding:20px;
    border-radius:10px;
}

a{
    display:inline-block;
    margin-bottom:20px;
    text-decoration:none;
    color:#2e7d32;
    font-weight:bold;
}

.note{
    font-weight:bold;
    color:#2e7d32;
}

.vide{
    background:white;
    padding:20px;
    text-align:center;
}

.stats{
    background:white;
    padding:15px;
    margin-bottom:20px;
    border-radius:10px;
}

select{
    padding:10px;
}

</style>
</head>

<body>

<p>
    <a href="formateur_notes.php">
        ← Retour aux notes
    </a>
</p>

<h2>Résultats des examens</h2>

<form method="GET" style="margin-bottom:20px;">

<label><strong>Classe :</strong></label>

<select name="classe_id" onchange="this.form.submit()">

    <option value="0">
        Toutes les classes
    </option>

    <?php foreach($classes as $c): ?>

        <option
            value="<?= $c['id'] ?>"
            <?= ($classe_id == $c['id']) ? 'selected' : '' ?>
        >
            <?= htmlspecialchars($c['nom']) ?>
        </option>

    <?php endforeach; ?>

</select>

</form>

<?php if ($nombre > 0): ?>

<div class="stats">

    <strong>Nombre de résultats :</strong>
    <?= $nombre ?>

    <br><br>

    <strong>Moyenne :</strong>
    <?= round($moyenne, 2) ?>/20

    <br><br>

    <strong>Meilleure note :</strong>
    <?= round($meilleure_note, 2) ?>/20

</div>

<div class="box">

<table>

<tr>
    <th>Étudiant</th>
    <th>Classe</th>
    <th>Examen</th>
    <th>Note /20</th>
    <th>Date</th>
</tr>

<?php foreach ($resultats as $r): ?>

<tr>

    <td>
        <?= htmlspecialchars($r['etudiant']) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['classe']) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['examen']) ?>
    </td>

    <td class="note">
        <?= round($r['note_finale'], 2) ?>
    </td>

    <td>
        <?= htmlspecialchars($r['date_passage']) ?>
    </td>

</tr>

<?php endforeach; ?>

</table>

</div>

<?php else: ?>

<div class="vide">

    Aucun résultat disponible pour cette classe.

</div>

<?php endif; ?>

</body>
</html>