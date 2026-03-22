<?php
session_start();
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

// Connexion BDD
$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer tous les étudiants
$etudiants = $pdo->query("SELECT id, nom, email FROM utilisateurs WHERE role='etudiant' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les classes
$classes = $pdo->query("SELECT id, nom FROM classes ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Assigner une classe
if (isset($_POST['etudiant_id'], $_POST['classe_id'])) {
    $etudiant_id = $_POST['etudiant_id'];
    $classe_id   = $_POST['classe_id'];

    // Vérifier si déjà assigné
    $check = $pdo->prepare("SELECT * FROM etudiants_classes WHERE etudiant_id=? AND classe_id=?");
    $check->execute([$etudiant_id, $classe_id]);

    if ($check->rowCount() == 0) {
        // Insert si pas déjà assigné
        $stmt = $pdo->prepare("INSERT INTO etudiants_classes (etudiant_id, classe_id) VALUES (?, ?)");
        $stmt->execute([$etudiant_id, $classe_id]);
        $message = "✅ Étudiant assigné avec succès.";
    } else {
        $message = "⚠️ Cet étudiant est déjà dans cette classe.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Assigner une classe à un étudiant</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
    h1 { color: #4CAF50; }
    form { margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px; }
    select, button { padding: 8px; margin: 5px 0; }
    .msg { margin: 15px 0; font-weight: bold; }
  </style>
</head>
<body>
  <h1>Assigner une classe à un étudiant</h1>
  <p><a href="adminpage.php">← Retour</a></p>

  <?php if (!empty($message)): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Étudiant :</label><br>
    <select name="etudiant_id" required>
      <option value="">-- Choisir un étudiant --</option>
      <?php foreach ($etudiants as $e): ?>
        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?> (<?= htmlspecialchars($e['email']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <br>

    <label>Classe :</label><br>
    <select name="classe_id" required>
      <option value="">-- Choisir une classe --</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
      <?php endforeach; ?>
    </select>
    <br><br>

    <button type="submit">➕ Assigner</button>
  </form>
  <a class="btn" href="liste_etudiants.php" >rechercher un étudiant</a>
</body>
</html>
