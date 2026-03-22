<?php
session_start();
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

// Connexion à la base
$pdo = new PDO('mysql:host=localhost;dbname=gestion_notes;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Supprimer un étudiant
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
}

// Modifier un étudiant
if (isset($_POST['edit_id'])) {
    $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, email=?, role=? WHERE id=?");
    $stmt->execute([
        $_POST['nom'], $_POST['email'], $_POST['role'], $_POST['edit_id']
    ]);
}

// Récupérer toutes les classes pour le filtre
$classes = $pdo->query("SELECT id, nom FROM classes ORDER BY nom")->fetchAll();

// ✅ Sécurisation des paramètres GET
$search = isset($_GET['search']) ? $_GET['search'] : "";
$classe_id = isset($_GET['classe_id']) ? $_GET['classe_id'] : "";

// Construire condition de recherche
$where = "u.role = 'etudiant'";
$params = [];

// Filtre par classe
if (!empty($classe_id)) {
    $where .= " AND c.id = ?";
    $params[] = $classe_id;
}

// Filtre par recherche (nom ou email)
if (!empty($search)) {
    $where .= " AND (u.nom LIKE ? OR u.email LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}

// Liste des étudiants avec leur(s) classe(s) sans doublons
$sql = "
    SELECT u.id, u.nom, u.email, u.role, u.date_inscription,
           GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ', ') AS classes
    FROM utilisateurs u
    LEFT JOIN etudiants_classes ec ON u.id = ec.etudiant_id
    LEFT JOIN classes c ON ec.classe_id = c.id
    WHERE $where
    GROUP BY u.id, u.nom, u.email, u.role, u.date_inscription
    ORDER BY u.nom
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Regrouper par classe (pour l’affichage)
$groupes = [];
foreach ($etudiants as $e) {
    $classe = $e['classes'] ?: 'Non assigné';
    $groupes[$classe][] = $e;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des étudiants</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background-color: #f0fff0; }
    h1 { color: #4CAF50; }
    h2 { color: #2e7d32; margin-top: 40px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
    form { display: inline; }
    input[type="text"], input[type="email"], select {
      padding: 5px;
      box-sizing: border-box;
    }
    button {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50;
      color: white;
      cursor: pointer;
    }
    .delete-btn { background-color: #668fdbff; }
    .filter { margin-bottom: 20px; }
    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr { display: block; }
      th { background-color: #eee; }
      td { margin-bottom: 10px; }
    }
  </style>
</head>
<body>
  <h1>Gestion des étudiants</h1>
  <p><a href="adminpage.php">← Retour </a></p>

  <!-- Formulaire de filtre -->
  <form method="get" class="filter">
    <label>Filtrer par classe :</label>
    <select name="classe_id">
        <option value="">-- Toutes --</option>
        <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($classe_id == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Rechercher (nom ou email) :</label>
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">

    <button type="submit">🔎 Rechercher</button>
  </form>

  <?php foreach ($groupes as $classe => $liste): ?>
    <h2>Classe : <?= htmlspecialchars($classe) ?></h2>
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Date inscription</th>
    
        </tr>
      </thead>
      <tbody>
        <?php foreach ($liste as $e): ?>
        <tr>
          <form method="POST">
            <td><input type="text" name="nom" value="<?= htmlspecialchars($e['nom']) ?>" required></td>
            <td><input type="email" name="email" value="<?= htmlspecialchars($e['email']) ?>" required></td>
            
            <td><?= $e['date_inscription'] ?></td>
            
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
</body>
</html>
