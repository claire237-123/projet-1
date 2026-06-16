<?php
session_start();
$host = "localhost";
$dbname = "gestion_notes";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$error = "";
$success = "";

/*
==================================================
CODE SECRET FORMATEUR
==================================================
*/

$CODE_FORMATEUR = "FORM2026";

/*
==================================================
TRAITEMENT FORMULAIRE
==================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"]);
    $sexe = trim($_POST["sexe"]);
    $email = strtolower(trim($_POST["email"]));
    $mot_de_passe = $_POST["password"];
    $code_formateur = trim($_POST["code_formateur"]);

    /*
    ==============================================
    VALIDATIONS
    ==============================================
    */

    if (
        empty($nom) ||
        empty($sexe) ||
        empty($email) ||
        empty($mot_de_passe)
    ) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    }

    else {

        /*
        ==============================================
        DÉTERMINATION DU RÔLE
        ==============================================
        */

        $role = "etudiant";

        if ($code_formateur === $CODE_FORMATEUR) {
            $role = "formateur";
        }

        /*
        ==============================================
        VÉRIFIER SI EMAIL EXISTE DÉJÀ
        ==============================================
        */

        $check = $pdo->prepare("
            SELECT id
            FROM utilisateurs
            WHERE email = ?
        ");

        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Cet email est déjà utilisé.";
        }

        else {

            /*
            ==============================================
            HASH DU MOT DE PASSE
            ==============================================
            */

            $mot_de_passe_hache = password_hash(
                $mot_de_passe,
                PASSWORD_DEFAULT
            );

            /*
            ==============================================
            INSERTION UTILISATEUR
            ==============================================
            */

            $insert = $pdo->prepare("
                INSERT INTO utilisateurs
                (
                    nom,
                    sexe,
                    email,
                    mot_de_passe,
                    role,
                    date_inscription
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            $insert->execute([
                $nom,
                $sexe,
                $email,
                $mot_de_passe_hache,
                $role
            ]);

            /*
            ==============================================
            REDIRECTION
            ==============================================
            */

            header("Location: connexion.php?success=1");
            exit();
        }
    }
}
?>
