<?php
session_start();
if ($_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");

// ✅ Valider une note
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'valider') {
    $note_id = intval($_POST['note_id']);
    $pdo->prepare("UPDATE notes SET statut = 'validee' WHERE id = ?")->execute([$note_id]);
}

// ✅ Refuser une note
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'refuser') {
    $note_id = intval($_POST['note_id']);
    $pdo->prepare("UPDATE notes SET statut = 'refusee' WHERE id = ?")->execute([$note_id]);
}

// ✅ Notes en attente
$notes = $pdo->query("
    SELECT n.id, u.nom AS etudiant, n.matiere, n.note, n.date_ajout
    FROM notes n
    JOIN utilisateurs u ON n.etudiant_id = u.id
    WHERE n.statut = 'en_attente'
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Validation des notes</title>
</head>
<style>
     body {
            background-image: url('note.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            height: 20vh;
            display: flex;
            justify-content: left;
            align-items: center;
        }
</style>

<body>
<h1>Notes en attente</h1>
<table border="1" cellpadding="5">
<tr><th>Étudiant</th><th>Matière</th><th>Note</th><th>Date</th><th>Actions</th></tr>
<?php foreach ($notes as $n): ?>
<tr>
    <td><?= htmlspecialchars($n['etudiant']) ?></td>
    <td><?= htmlspecialchars($n['matiere']) ?></td>
    <td><?= $n['note'] ?></td>
    <td><?= $n['date_ajout'] ?></td>
    <td>
        <form method="POST" style="display:inline">
            <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
            <input type="hidden" name="action" value="valider">
            <button type="submit">✅ Valider</button>
        </form>
        <form method="POST" style="display:inline">
            <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
            <input type="hidden" name="action" value="refuser">
            <button type="submit">❌ Refuser</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
