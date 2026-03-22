<?php
session_start();

// Protection : uniquement pour formateurs
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'formateur') {
    header("Location: connexion.php");
    exit;
}

$nom = htmlspecialchars($_SESSION['nom']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Espace Formateur</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    body {
      background: url('form.jpg') no-repeat center center/cover;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: flex-start; /* aligne à gauche */
      align-items: center;         /* centre verticalement */
      font-family: 'Poppins', sans-serif;
      color: #fff;
      position: relative;
    }

    /* Overlay sombre pour lisibilité */
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1;
    }

    /* Bloc principal */
    .content {
      position: relative;
      z-index: 2; /* passe au-dessus du voile */
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      padding: 40px;
      border-radius: 20px;
      max-width: 700px;
      margin-left: 22%; /* décale légèrement du bord gauche */
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h1 {
      font-size: 2.5rem;
      margin-bottom: 20px;
    }

    p {
      margin: 10px 0;
      font-size: 1.1rem;
    }

    a {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 18px;
      background: #4CAF50;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 500;
      transition: 0.3s;
    }

    a:hover {
      background: #43a047;
      transform: translateY(-3px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .content { padding: 20px; margin-left: 2%; }
      h1 { font-size: 1.8rem; }
      p { font-size: 1rem; }
    }
  </style>
</head>
<body>
  <div class="content">
    <h1>Bienvenue Formateur <?= $nom ?></h1>
    <p>Vous pouvez gérer les notes et les étudiants.</p>
    <p><a href="formateur_notes.php">📘 Gérer les notes</a></p>
    <p><a href="dashboard.php">🏠 Retour au tableau de bord</a></p>
  </div>
</body>
</html>
