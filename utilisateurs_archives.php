<?php
session_start();

/* =========================
   PROTECTION ADMIN
========================= */
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

/* =========================
   CONNEXION BD
========================= */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}

$message = "";

/* =========================
   RESTAURATION UTILISATEUR
========================= */
if (isset($_POST['restaurer'])) {

    $id = $_POST['user_id'];

    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET statut_scolarite = 'en_cours'
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    $message = "♻️ Utilisateur restauré avec succès";
}

/* =========================
   LISTE UTILISATEURS ARCHIVÉS
========================= */
$users = $pdo->query("
    SELECT * FROM utilisateurs
    WHERE statut_scolarite = 'termine'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Utilisateurs archivés</title>

<style>
body {
    font-family: Arial;
    background: #f5f5f5;
    padding: 20px;
}

h2 { color: #333; }

table {
    width: 100%;
    background: white;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    border: 1px solid #ccc;
}

th {
    background: #444;
    color: white;
}

.box {
    background: white;
    padding: 15px;
    margin-top: 20px;
}

button {
    padding: 6px 10px;
    cursor: pointer;
}

.msg {
    background: #dff0d8;
    border-left: 5px solid #4CAF50;
    padding: 10px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<h2>📁 Utilisateurs archivés</h2>

<p>
<a href="admin_utilisateurs.php">← Retour gestion utilisateurs</a>
</p>

<!-- ================= NOTIFICATION ================= -->
<?php if ($message): ?>
<div class="msg" id="notif"><?= htmlspecialchars($message) ?></div>

<script>
setTimeout(() => {
    let n = document.getElementById("notif");
    if (n) n.style.display = "none";
}, 3000);
</script>
<?php endif; ?>

<!-- ================= TABLE ================= -->
<div class="box">

<table>
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Email</th>
    <th>Rôle</th>
    <th>Action</th>
</tr>

<?php if (count($users) > 0): ?>

    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['nom']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= $u['role'] ?></td>
        <td>

            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button name="restaurer">♻️ Restaurer</button>
            </form>

        </td>
    </tr>
    <?php endforeach; ?>

<?php else: ?>
    <tr>
        <td colspan="5" style="text-align:center;">Aucun utilisateur archivé</td>
    </tr>
<?php endif; ?>

</table>

</div>

</body>
</html>