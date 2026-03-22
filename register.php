<?php
// register.php - Version corrigée
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

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom          = isset($_POST["nom"]) ? trim($_POST["nom"]) : "";
    $sexe_raw     = isset($_POST["sexe"]) ? trim($_POST["sexe"]) : "";
    $email        = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
    $mot_de_passe = isset($_POST["password"]) ? $_POST["password"] : "";
    $role         = isset($_POST["role"]) ? strtolower(trim($_POST["role"])) : "";

    // Normalisation du sexe pour l'ENUM SQL
    $sexe = (in_array(mb_strtolower($sexe_raw), ['f','féminin','feminin','femme'])) ? 'féminin' : 'masculin';

    $allowed_roles = ['etudiant', 'formateur', 'administrateur'];

    if ($nom === "" || $email === "" || $mot_de_passe === "" || $role === "") {
        $error = "⚠️ Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Email invalide.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "⚠️ Rôle invalide.";
    } else {
        try {
            // ✅ CORRECTION LIGNE 46 : On utilise 'id' au lieu de 'utilisateur_id'
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "⚠️ Cet email est déjà utilisé.";
            } else {
                $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $role_normalized = strtolower(trim($role)); // Normaliser le rôle

                $ins = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, sexe, email, mot_de_passe, role, date_inscription)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute(array($nom, $sexe, $email, $mot_de_passe_hache, $role_normalized));

                header("Location: connexion.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "⚠️ Erreur SQL : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Inscription - Gestion Notes</title>
  <style>
    body { background:#E8F5E9; font-family:Arial, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .card { background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); width:100%; max-width:400px; }
    h2 { color:#2e7d32; text-align:center; }
    label { display:block; margin-top:15px; font-weight:bold; color:#555; }
    input, select { width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box; }
    button { width:100%; padding:12px; margin-top:20px; background:#43A047; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:16px; font-weight:bold; }
    button:hover { background:#388E3C; }
    .error { color:#c0392b; background:#f9d9d9; padding:10px; border-radius:5px; margin-top:15px; text-align:center; }
    .login-link { text-align:center; margin-top:15px; font-size:14px; }
    .login-link a { color:#43A047; text-decoration:none; font-weight:bold; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Créer un compte</h2>
    <form method="POST">
      <label>Nom complet</label>
      <input name="nom" type="text" placeholder="Jean Dupont" required>

      <label>Sexe</label>
      <select name="sexe" required>
        <option value="masculin">Masculin</option>
        <option value="féminin">Féminin</option>
      </select>

      <label>Email</label>
      <input name="email" type="email" placeholder="exemple@mail.com" required>

      <label>Mot de passe</label>
      <input name="password" type="password" required>

      <label>Rôle</label>
      <select name="role" required>
        <option value="etudiant">Étudiant</option>
        <option value="formateur">Formateur</option>
        <option value="administrateur">Administrateur</option>
      </select>

      <button type="submit">S'inscrire</button>
    </form>
    <?php if ($error): ?> <div class="error"><?= $error ?></div> <?php endif; ?>
    <div class="login-link">Déjà inscrit ? <a href="connexion.php">Se connecter</a></div>
  </div>
</body>
</html>