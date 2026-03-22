<?php
$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");

// Vérifie que l’ID de la classe est passé
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Classe invalide.");
}
$classe_id = (int) $_GET['id'];

// Récupération du nom de la classe
$stmt = $pdo->prepare("SELECT nom FROM classes WHERE id = ?");
$stmt->execute([$classe_id]);
$classe = $stmt->fetchColumn();
if (!$classe) {
    die("Classe introuvable.");
}

// Récupération des étudiants et de leur moyenne
$sql = "
    SELECT u.id AS etudiant_id, u.nom AS etudiant,
           ROUND(SUM(n.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 2) AS moyenne
    FROM utilisateurs u
    JOIN etudiants_classes ec ON u.id = ec.etudiant_id
    LEFT JOIN notes n ON u.id = n.etudiant_id
    LEFT JOIN matieres m ON n.matiere_id = m.id
    WHERE ec.classe_id = ?
      AND u.role = 'etudiant'
    GROUP BY u.id, u.nom
    ORDER BY moyenne DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$classe_id]);
$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Résultats - <?= htmlspecialchars($classe) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: #f5fff7;
      color: #333;
    }

    .container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 20px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #2e7d32;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      overflow: hidden;
      border-radius: 10px;
    }

    thead {
      background: #43a047;
      color: #fff;
    }

    th, td {
      padding: 14px;
      text-align: center;
      font-size: 1rem;
    }

    tbody tr {
      transition: background 0.2s ease-in-out;
    }

    tbody tr:nth-child(even) {
      background: #f9fdf9;
    }

    tbody tr:hover {
      background: #e8f5e9;
    }

    .btn {
      display: inline-block;
      padding: 10px 16px;
      margin-top: 20px;
      background: #43a047;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 500;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    .btn:hover {
      background: #2e7d32;
      transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }
      thead {
        display: none;
      }
      tbody tr {
        margin-bottom: 15px;
        background: #f9fdf9;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        padding: 12px;
      }
      td {
        display: flex;
        justify-content: space-between;
        padding: 8px 6px;
      }
      td:before {
        content: attr(data-label);
        font-weight: 600;
        color: #2e7d32;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Résultats - Classe <?= htmlspecialchars($classe) ?></h1>
    <table>
      <thead>
        <tr>
          <th>Étudiant</th>
          <th>Moyenne Générale</th>
        </tr>
      </thead>
      <tbody>
  <?php foreach ($etudiants as $e): ?>
  <tr>
    <td data-label="Étudiant"><?= htmlspecialchars($e['etudiant']) ?></td>
    <td data-label="Moyenne Générale">
      <?= $e['moyenne'] !== null ? htmlspecialchars($e['moyenne']) : '-' ?>
    </td>
  </tr>
  <?php endforeach; ?>
</tbody>
 </table>
    <a href="adminresultats_classes.php" class="btn">← Retour aux classes</a>
  </div>
</body>
</html>
