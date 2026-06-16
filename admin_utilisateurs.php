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
   AJOUT UTILISATEUR
========================= */
if (isset($_POST['ajouter'])) {

    $nom = trim($_POST['nom']);
    $email = strtolower(trim($_POST['email']));
    $role = strtolower(trim($_POST['role']));
    $mot_de_passe = $_POST['mot_de_passe'];

    $roles_valides = ['etudiant', 'formateur', 'administrateur'];

    if ($nom && $email && $role && $mot_de_passe) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Email invalide";
        } elseif (!in_array($role, $roles_valides)) {
            $message = "❌ Rôle invalide";
        } else {

            // Vérifier email unique
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "❌ Email déjà utilisé";
            } else {

                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, email, role, mot_de_passe, sexe, statut_scolarite, date_inscription)
                    VALUES (?, ?, ?, ?, 'masculin', 'en_cours', NOW())
                ");

                $insert->execute([
                    $nom,
                    $email,
                    $role,
                    $hash
                ]);

                $message = "✅ Utilisateur créé avec succès";
            }
        }

    } else {
        $message = "❌ Tous les champs sont obligatoires";
    }
}

/* =========================
   ARCHIVAGE (SOFT DELETE)
========================= */
if (isset($_POST['archiver'])) {

    $id = $_POST['user_id'];

    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET statut_scolarite = 'termine'
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    $message = "📦 Utilisateur archivé avec succès";
}

/* =========================
   RESTAURER UTILISATEUR
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
   LISTE UTILISATEURS ACTIFS
========================= */
$users = $pdo->query("
    SELECT * FROM utilisateurs
    WHERE statut_scolarite = 'en_cours'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion utilisateurs</title>

<style>
body {
    font-family: Arial;
    background: #e8f5e9;
    padding: 20px;
}

h2 { color: #2e7d32; }

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
    background: #4CAF50;
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
    background: #ffffff;
    border-left: 5px solid #4CAF50;
    padding: 10px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<h2>Gestion des utilisateurs</h2>

<p>
<a href="adminpage.php">← Dashboard</a> |
<a href="utilisateurs_archives.php">📁 Utilisateurs archivés</a>
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

<!-- ================= AJOUT ================= -->
<div class="box">
<h3>Ajouter utilisateur</h3>

<form method="POST">

    <input type="text" name="nom" placeholder="Nom complet" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>

    <select name="role" required>
        <option value="">-- rôle --</option>
        <option value="etudiant">Étudiant</option>
        <option value="formateur">Formateur</option>
        <option value="administrateur">Administrateur</option>
    </select>

    <button type="submit" name="ajouter">Créer</button>
</form>
</div>

<!-- ================= LISTE ================= -->
<div class="box">
<h3>Utilisateurs actifs</h3>

<table>
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Email</th>
    <th>Rôle</th>
    <th>Actions</th>
</tr>

<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['nom']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= $u['role'] ?></td>
    <td>

        <!-- ARCHIVER -->
        <form method="POST" style="display:inline" onsubmit="return confirm('Archiver cet utilisateur ?')">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button name="archiver">📦 Archiver</button>
        </form>

    </td>
</tr>
<?php endforeach; ?>

</table>
</div>

</body>
</html>