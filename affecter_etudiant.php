<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrateur') {
    die("Accès refusé");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$message = "";

/* =======================
   SUPPRESSION D'AFFECTATION
   ======================= */
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM formateurs_etudiants WHERE id = ?")->execute([$_GET['supprimer']]);
    $message = "✅ Affectation supprimée.";
}

/* =======================
   AFFECTATION ÉTUDIANT -> FORMATEUR
   ======================= */
if (isset($_POST['affecter'])) {
    $etudiant_id  = (int)$_POST['etudiant_id'];
    $formateur_id = (int)$_POST['formateur_id'];

    // Vérifier si la liaison existe déjà
    $check = $pdo->prepare("SELECT COUNT(*) FROM formateurs_etudiants WHERE etudiant_id = ? AND formateur_id = ?");
    $check->execute([$etudiant_id, $formateur_id]);
    if ($check->fetchColumn() > 0) {
        $message = "⚠️ Cet étudiant est déjà lié à ce formateur.";
    } else {
        $pdo->prepare("INSERT INTO formateurs_etudiants (formateur_id, etudiant_id) VALUES (?, ?)")
            ->execute([$formateur_id, $etudiant_id]);
        $message = "✅ Étudiant affecté au formateur.";
    }
}

/* =======================
   LISTES
   ======================= */
$etudiants = $pdo->query("SELECT id, nom FROM utilisateurs WHERE role = 'etudiant' ORDER BY nom ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

$formateurs = $pdo->query("SELECT id, nom FROM utilisateurs WHERE role = 'formateur' ORDER BY nom ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

$affectations = $pdo->query("
    SELECT fe.id, u.nom AS etudiant, f.nom AS formateur
    FROM formateurs_etudiants fe
    JOIN utilisateurs u ON fe.etudiant_id = u.id
    JOIN utilisateurs f ON fe.formateur_id = f.id
    ORDER BY f.nom, u.nom
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affecter un étudiant à un formateur</title>
</head>
<body>
<h1>Affecter un étudiant à un formateur</h1>

<?php if (!empty($message)) echo "<p><strong>$message</strong></p>"; ?>

<form method="post">
    Étudiant :
    <select name="etudiant_id" required>
        <option value="">-- Sélectionner --</option>
        <?php foreach ($etudiants as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    Formateur :
    <select name="formateur_id" required>
        <option value="">-- Sélectionner --</option>
        <?php foreach ($formateurs as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="affecter">Affecter</button>
</form>

<h2>Affectations existantes</h2>
<table border="1" cellpadding="5">
<tr>
    <th>Formateur</th>
    <th>Étudiant</th>
    <th>Action</th>
</tr>
<?php foreach ($affectations as $a): ?>
<tr>
    <td><?= htmlspecialchars($a['formateur']) ?></td>
    <td><?= htmlspecialchars($a['etudiant']) ?></td>
    <td>
        <a href="?supprimer=<?= $a['id'] ?>" onclick="return confirm('Supprimer cette affectation ?')">🗑️ Supprimer</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
<a href="admin_gestion_matieres.php">Retour</a>
</body>
</html>
