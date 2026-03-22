<?php
session_start();

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

// Connexion à la base
$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ajouter un utilisateur
if (isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']);
    $email = strtolower(trim($_POST['email']));
    $role = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    $allowed_roles = array('etudiant', 'formateur', 'administrateur');

    if (!$nom || !$email || !$role || !$mot_de_passe) {
        // Champs vides
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Email invalide
    } elseif (!in_array($role, $allowed_roles)) {
        // Rôle invalide
    } else {
        // Vérifier si l'email existe déjà
        $checkEmail = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $checkEmail->execute(array($email));
        
        if (!$checkEmail->fetch()) {
            $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, role, mot_de_passe) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($nom, $email, $role, $mot_de_passe_hache));
        }
    }
}

// Modifier un utilisateur
if (isset($_POST['modifier'])) {
    $id = $_POST['user_id'];
    $nom = trim($_POST['nom']);
    $email = strtolower(trim($_POST['email']));
    $role = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : '';

    $allowed_roles = array('etudiant', 'formateur', 'administrateur');

    if ($nom && $email && $role && in_array($role, $allowed_roles) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Vérifier si le nouvel email est unique (sauf l'utilisateur lui-même)
        $checkEmail = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $checkEmail->execute(array($email, $id));
        
        if (!$checkEmail->fetch()) {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute(array($nom, $email, $role, $id));
        }
    }
}

// Supprimer un utilisateur
if (isset($_POST['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
}

// Liste des utilisateurs
$utilisateurs = $pdo->query("SELECT * FROM utilisateurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des utilisateurs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background:rgb(106, 182, 110);;
      margin: 0;
      padding: 20px;
    }
    h1 {
      color: #333;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
    }
    th {
      background: #eee;
    }
    form.inline {
      display: inline;
    }
    input, select {
      padding: 5px;
    }
    .ajout-form {
      background: #fff;
      padding: 15px;
      margin-top: 20px;
      border: 1px solid #ccc;
    }
    button {
      padding: 5px 10px;
      margin-top: 5px;
    }

    @media (max-width: 768px) {
      table, th, td {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <h1>Gestion des utilisateurs</h1>
  <p><a href="adminpage.php">← Retour</a></p>

  <table>
    <tr>
      <th>Nom</th>
      <th>Email</th>
      <th>Rôle</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($utilisateurs as $u): ?>
    <tr>
      <form method="POST">
        <td><input type="text" name="nom" value="<?= htmlspecialchars($u['nom']) ?>" required></td>
        <td><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></td>
        <td>
          <select name="role" required>
            <option value="administrateur" <?= $u['role'] === 'administrateur' ? 'selected' : '' ?>>Admin</option>
            <option value="formateur" <?= $u['role'] === 'formateur' ? 'selected' : '' ?>>Formateur</option>
            <option value="etudiant" <?= $u['role'] === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
          </select>
        </td>
        <td>
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <button type="submit" name="modifier">💾 Modifier</button>
      </form>
      <form method="POST" class="inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <button type="submit" name="supprimer">🗑️ Supprimer</button>
      </form>
        </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div class="ajout-form">
    <h2>Ajouter un nouvel utilisateur</h2>
    <form method="POST">
      <input type="text" name="nom" placeholder="Nom" required>
      <input type="email" name="email" placeholder="Email" required>
      <select name="role" required>
        <option value="etudiant">Étudiant</option>
        <option value="formateur">Formateur</option>
        <option value="administrateur">Administrateur</option>
      </select>
      <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
      <br>
      <button type="submit" name="ajouter">➕ Ajouter</button>
    </form>
  </div>
</body>
</html>
