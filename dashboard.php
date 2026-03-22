<?php
require 'condb.php';
session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: connexion.php");
    exit;
}

$nom = $_SESSION['nom'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tableau de bord</title>
</head>
<body>
  <h1>Bienvenue, <?= htmlspecialchars($nom) ?> !</h1>
  <p>Rôle : <strong><?= htmlspecialchars($role) ?></strong></p>

  <!-- Ajoute ici d'autres liens si besoin -->

  <p><a href="deconnexion.php">Se déconnecter</a></p>
</body>
</html>
