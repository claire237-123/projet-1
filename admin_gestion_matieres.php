<?php
session_start();

// ✅ Vérification rôle administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

$message = "";

// ✅ Ajouter une matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = trim($_POST['nom']);
    $formateur_id = intval($_POST['formateur_id']);
    $coefficient = intval($_POST['coefficient']);

    if (!empty($nom) && $formateur_id > 0 && $coefficient > 0) {
        $stmt = $pdo->prepare("INSERT INTO matieres (nom, formateur_id, coefficient) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $formateur_id, $coefficient]);
        $message = "✅ Matière ajoutée avec succès.";
    } else {
        $message = "❌ Veuillez remplir tous les champs correctement.";
    }
}

// ✅ Modifier une matière
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id = intval($_POST['matiere_id']);
    $nouveau_nom = trim($_POST['nouveau_nom']);
    $nouveau_coeff = intval($_POST['nouveau_coeff']);

    if (!empty($nouveau_nom) && $nouveau_coeff > 0) {
        $stmt = $pdo->prepare("UPDATE matieres SET nom = ?, coefficient = ? WHERE id = ?");
        $stmt->execute([$nouveau_nom, $nouveau_coeff, $id]);
        $message = "✅ Matière modifiée.";
    } else {
        $message = "❌ Nom ou coefficient invalide.";
    }
}

// ✅ Supprimer une matière
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id = intval($_POST['matiere_id']);

    // ⚠️ Vérification si la matière est utilisée dans notes
    $check = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE matiere_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $message = "❌ Impossible de supprimer : matière liée à des notes.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM matieres WHERE id = ?");
        $stmt->execute([$id]);
        $message = "🗑️ Matière supprimée.";
    }
}

// ✅ Récupérer formateurs
$formateurs = $pdo->query("SELECT id, nom FROM utilisateurs WHERE role = 'formateur'")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Récupérer matières avec formateur et coefficient
$matieres = $pdo->query("
    SELECT m.id, m.nom AS matiere_nom, m.coefficient, u.nom AS formateur_nom
    FROM matieres m
    JOIN utilisateurs u ON m.formateur_id = u.id
    ORDER BY u.nom, m.nom
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des matières - Admin</title>
    <style>
        body {
            font-family: Arial;
            background-color: #d0f0c0;
            padding: 20px;
        }
        h1 { color: #4CAF50; }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background: white;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 10px;
            text-align: left;
        }
        input, select {
            padding: 5px;
            width: 200px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background-color: #388E3C; }
        .message {
            margin-top: 15px;
            background-color: #e1fbe1;
            padding: 10px;
            border-left: 5px solid #4CAF50;
        }
    </style>
</head>
<body>
    <h1>Gestion des matières</h1>
    <p>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['nom']) ?></strong> (Administrateur) — <a href="adminpage.php">Retour</a></p>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>Ajouter une nouvelle matière</h2>
    <form method="POST">
        <input type="hidden" name="action" value="ajouter">
        <label>Nom de la matière :</label><br>
        <input type="text" name="nom" required><br><br>

        <label>Coefficient :</label><br>
        <input type="number" name="coefficient" value="1" min="1" required><br><br>

        <label>Attribuer à un formateur :</label><br>
        <select name="formateur_id" required>
            <option value="">-- Sélectionner un formateur --</option>
            <?php foreach ($formateurs as $f): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Ajouter la matière</button>
    </form>

    <h2>Liste des matières</h2>
    <table>
        <tr>
            <th>Matière</th>
            <th>Coefficient</th>
            <th>Formateur</th>
            <th>Modifier</th>
            <th>Supprimer</th>
        </tr>
        <?php foreach ($matieres as $m): ?>
            <tr>
                <form method="POST">
                    <td>
                        <input type="text" name="nouveau_nom" value="<?= htmlspecialchars($m['matiere_nom']) ?>" required>
                    </td>
                    <td>
                        <input type="number" name="nouveau_coeff" value="<?= $m['coefficient'] ?>" min="1" required>
                    </td>
                    <td><?= htmlspecialchars($m['formateur_nom']) ?></td>
                    <td>
                        <input type="hidden" name="matiere_id" value="<?= $m['id'] ?>">
                        <input type="hidden" name="action" value="modifier">
                        <button type="submit">💾 Modifier</button>
                    </td>
                </form>
                <td>
                    <form method="POST" onsubmit="return confirm('Confirmer la suppression ?')">
                        <input type="hidden" name="matiere_id" value="<?= $m['id'] ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <button type="submit">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <ul>
        <li><p><a href="affecter_etudiant.php">Affecter chaque étudiant à ses formateurs</a></p></li>
    </ul>
</body>
</html>
