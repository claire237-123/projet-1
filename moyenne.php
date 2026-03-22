<?php
session_start();

// ✅ Autorisation pour admin ou formateur
if (!isset($_SESSION['utilisateur_id']) || !in_array($_SESSION['role'], ['administrateur', 'formateur'])) {
    header("Location: connexion.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// ✅ Calculer la moyenne globale et par matière
$sql = "
SELECT u.id AS etudiant_id, u.nom AS etudiant_nom,
       m.nom AS matiere_nom,
       ROUND(AVG(n.note), 2) AS moyenne
FROM notes n
JOIN utilisateurs u ON n.etudiant_id = u.id
LEFT JOIN matieres m ON n.matiere_id = m.id
WHERE u.role = 'etudiant'
GROUP BY u.id, m.id
ORDER BY u.nom, m.nom
";
$stmt = $pdo->query($sql);
$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Regrouper les résultats par étudiant
$moyennes_par_etudiant = [];
foreach ($resultats as $ligne) {
    $id = $ligne['etudiant_id'];
    $moyennes_par_etudiant[$id]['nom'] = $ligne['etudiant_nom'];
    $moyennes_par_etudiant[$id]['matieres'][] = [
        'matiere' => $ligne['matiere_nom'] ?: 'Non spécifiée',
        'moyenne' => $ligne['moyenne']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Moyennes des étudiants</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #eaffea;
        padding: 20px;
    }
    h1 { color: #4CAF50; }
    .card {
        background: white;
        padding: 15px;
        margin: 15px 0;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .matiere {
        margin-left: 15px;
        padding: 3px 0;
    }
    @media (max-width: 600px) {
        .card { padding: 10px; font-size: 15px; }
    }
    a {
        color: #4CAF50;
        text-decoration: none;
    }
  </style>
</head>
<body>

<h1>📊 Moyennes des étudiants</h1>
<p><a href="dashboard.php">⬅ Retour</a></p>

<?php if (empty($moyennes_par_etudiant)): ?>
    <p>Aucune note trouvée.</p>
<?php else: ?>
    <?php foreach ($moyennes_par_etudiant as $etudiant): ?>
        <div class="card">
            <strong><?= htmlspecialchars($etudiant['nom']) ?></strong>
            <ul>
                <?php
                    $somme = 0; $count = 0;
                    foreach ($etudiant['matieres'] as $matiere):
                        $somme += $matiere['moyenne'];
                        $count++;
                ?>
                    <li class="matiere">
                        <?= htmlspecialchars($matiere['matiere']) ?> : 
                        <strong><?= number_format($matiere['moyenne'], 2) ?>/20</strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>🎯 Moyenne générale : <strong><?= number_format($somme / $count, 2) ?>/20</strong></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
