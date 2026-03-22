<?php
session_start();

// Connexion à la base de données
$host = 'localhost';
$dbname = 'gestion_notes';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$error = "";

// Si l'utilisateur est déjà connecté, rediriger selon son rôle
if (isset($_SESSION['utilisateur_id'])) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    switch ($role) {
        case 'administrateur':
            header("Location: adminpage.php");
            exit;
        case 'formateur':
            header("Location: formateurpage.php");
            exit;
        case 'etudiant':
            header("Location: etudiantpage.php");
            exit;
        default:
            header("Location: dashboard.php");
            exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $mot_de_passe = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($mot_de_passe)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            // Stocker les infos en session
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = trim(strtolower($user['role']));

            // Redirection selon le rôle
            switch ($_SESSION['role']) {
                case 'administrateur':
                    $redirect = "adminpage.php";
                    break;
                case 'formateur':
                    $redirect = "formateurpage.php";
                    break;
                case 'etudiant':
                    $redirect = "etudiantpage.php";
                    break;
                default:
                    $redirect = "dashboard.php";
                    break;
            }

            // Vérifier si les headers sont déjà envoyés
            if (!headers_sent()) {
                header("Location: $redirect");
                exit;
            } else {
                // Affichage en HTML si redirection impossible
                $error = "Connexion réussie, mais impossible de rediriger. <a href='$redirect'>Cliquez ici</a> pour continuer.";
            }
        } else {
            $error = "Identifiants invalides.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion</title>
  <style>
    body { background-color: #A8E6CF; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin:0; }
    .login-form { background-color: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    h2 { text-align: center; color: #4CAF50; }
    input { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #ccc; border-radius: 8px; }
    button { width: 100%; padding: 12px; background-color: #4CAF50; border: none; border-radius: 8px; font-size: 16px; margin-top: 20px; cursor: pointer; }
    .error { color: red; text-align: center; margin-top: 10px; }
  </style>
</head>
<body>
  <form class="login-form" method="POST" action="">
    <h2>Connexion</h2>
    <input type="email" name="email" placeholder="Adresse e-mail" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Se connecter</button>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p style="text-align: center; margin-top: 10px;">
      Pas encore de compte ? <a href="register.php">Créer un compte</a>
    </p>
  </form>
</body>
</html>