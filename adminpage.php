<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des utilisateurs</title>
    <style>
        body {
            background-image: url('gestion.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Utilisation de flexbox pour centrer la section */
        .content {
            background: rgba(0, 0, 0, 0.5); /* Fond légèrement sombre mais moins intense */
            color: white;
            padding: 20px;
            border-radius: 15px;
            width: 100%;
            max-width: 700px; /* Largeur maximale pour les écrans larges */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            height: auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Titre de la page */
        h1 {
            color: #f8f9fa;
            margin-bottom: 15px;
            font-size: 2rem; /* Taille du texte ajustée */
        }

        a {
            color: #f8f9fa;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        a:hover {
            color: #007bff;
            text-decoration: underline;
            transform: scale(1.05); /* Effet de zoom au survol */
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        ul li {
            margin: 15px 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            .content {
                padding: 15px;
                width: 90%; /* Réduction de la largeur pour les petits écrans */
                top: 10px;
                max-width: 95%;
            }

            h1 {
                font-size: 1.5rem; /* Taille du texte plus petite pour mobile */
            }

            ul li {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();

    // Protection : uniquement pour administrateurs
    if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
        header("Location: connexion.php");
        exit;
    }

    $nom = isset($_SESSION['nom']) ? htmlspecialchars($_SESSION['nom']) : 'Nom non défini';
    ?>
    <div class="content">
        <h1>Bienvenue Admin <?= htmlspecialchars($nom) ?></h1>
        <p>Vous avez accès à toutes les fonctionnalités d'administration.</p>
        <ul>
            <li><a href="admin_utilisateurs.php">🧑‍💼 Gérer les utilisateurs</a></li>
            <li><a href="admin_stats.php">📊 Consulter les statistiques</a></li>
            <li><a href="admin_gestion_matieres.php">Organiser les unités d'enseignement</a></li>
            <li><a href="note.php">valider une note</a></li>
            <li><a href="classegestion.php">Modalités</a></li>
            <li><a href="admin_classes.php">affecter des étudiants à leurs filières</a></li>
        </ul>
        <a href="dashboard.php">Retour au tableau de bord</a>
    </div>
</body>
</html>

