<?php
session_start();

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: connexion.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");

$sql = "
SELECT *
FROM examens
WHERE statut = 'publié'
ORDER BY id DESC
";

$examens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Examens disponibles</h2>

<?php foreach ($examens as $e): ?>
    <div>
        <h3><?= htmlspecialchars($e['titre']) ?></h3>
        <a href="passer_evaluation.php?id=<?= $e['id'] ?>">Passer</a>
    </div>
<?php endforeach; ?>