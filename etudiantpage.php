<?php
session_start();
require 'condb.php';

// Protection : uniquement pour étudiants
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: connexion.php");
    exit;
}

$util_id = $_SESSION['utilisateur_id'];
$nom = htmlspecialchars($_SESSION['nom']);

// Récupérer les notes
$stmt = $pdo->prepare("SELECT matiere, note FROM notes WHERE etudiant_id = ?");
$stmt->execute([$util_id]);
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des moyennes par matière
$matieres = [];
foreach ($all as $n) {
    $matieres[$n['matiere']][] = floatval($n['note']);
}
$avg = [];
foreach ($matieres as $m => $notes) {
    $avg[$m] = array_sum($notes) / count($notes);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Espace Étudiant</title>
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

  body {
    background: url('etd.jpg') no-repeat center center/cover;
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    position: relative;
  }

  /* Overlay sombre */
  body::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.55);
    z-index: 1;
  }

  /* Conteneur */
  .content {
    position: relative;
    z-index: 2;
    padding: 40px 30px;
    border-radius: 15px;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(8px);
    max-width: 600px;
    text-align: center; /* ✅ Centre tous les textes */
    animation: fadeIn 1s ease-in-out;
  }

  h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.8s forwards;
  }

  p {
    margin: 15px 0;
    font-size: 1.2rem;
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.8s forwards;
    animation-delay: 0.4s;
  }

  a {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 22px;
    background: #4caf50;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.8s forwards;
    animation-delay: 0.7s;
  }

  a:hover {
    background: #43a047;
    transform: translateY(-3px) scale(1.05);
  }

  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
  }

  @keyframes slideUp {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .content { width: 90%; padding: 25px; }
    h1 { font-size: 1.8rem; }
    p { font-size: 1rem; }
  }
</style>
</head>
<body>
  <div class="content">
    <h1>Bienvenue <?= $nom ?></h1>
    <p>⚙️ Besoin de voir et télécharger ton relevé de notes ?</p>
    <a href="resultats.php">📄 Mon relevé</a><br>
    <a href="dashboard.php">← Retour</a>
  </div>
</body>
</html>
