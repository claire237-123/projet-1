<?php
session_start();

// Vérification que c'est bien un admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrateur') {
    die("Accès refusé");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Création table classes si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) UNIQUE NOT NULL,
            scolarite DECIMAL(10,2) DEFAULT 0
        )
    ");

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Ajout d'une classe
if (isset($_POST['ajouter']) && !empty($_POST['nom'])) {
    $nom = trim($_POST['nom']);
    $scolarite = (float)$_POST['scolarite'];

    $stmt = $pdo->prepare("INSERT INTO classes (nom, scolarite) VALUES (?, ?)");
    try {
        $stmt->execute([$nom, $scolarite]);
        $message = "✅ Classe ajoutée avec succès.";
    } catch (PDOException $e) {
        $message = "⚠️ Erreur : la classe existe déjà.";
    }
}

// Modification d'une classe
if (isset($_POST['modifier'])) {
    $id = (int)$_POST['id'];
    $nom = trim($_POST['nom']);
    $scolarite = (float)$_POST['scolarite'];

    $stmt = $pdo->prepare("UPDATE classes SET nom = ?, scolarite = ? WHERE id = ?");
    $stmt->execute([$nom, $scolarite, $id]);
    $message = "✏️ Classe modifiée avec succès.";
}

// Suppression d'une classe
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    $message = "🗑️ Classe supprimée.";
}

// Récupération de toutes les classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des classes</title>
</head>
<body>  
    <a href="adminpage.php">Retour</a>
<h1>Gestion des classes</h1>

<?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>

<!-- Formulaire ajout -->
<h2>Ajouter une classe</h2>
<form method="post">
    Nom : <input type="text" name="nom" required>
    Scolarité : <input type="number" name="scolarite" step="1000" required> FCFA
    <button type="submit" name="ajouter">Ajouter</button>
</form>

<!-- Liste des classes -->
<h2>Liste des classes</h2>
<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Scolarité (FCFA)</th>
    <th>Actions</th>
</tr>
<?php foreach ($classes as $c): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['nom']) ?></td>
    <td><?= number_format($c['scolarite'], 0, ',', ' ') ?> FCFA</td>
    <td>
        <!-- Formulaire modification -->
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <input type="text" name="nom" value="<?= htmlspecialchars($c['nom']) ?>" required>
            <input type="number" name="scolarite" value="<?= $c['scolarite'] ?>" step="1000" required>
            <button type="submit" name="modifier">Modifier</button>
        </form>
        <!-- Suppression -->
        <a href="?supprimer=<?= $c['id'] ?>" onclick="return confirm('Supprimer cette classe ?')">Supprimer</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<p><a href="admin_gestion_scolarite.php">👉 Gestion des scolarités</a></p>

</body>
</html>
