<?php
$pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");

// Requête : moyenne générale par classe
$sql = "
    SELECT c.id AS classe_id, c.nom AS classe,
           ROUND(AVG(moyennes.moyenne_etudiant), 2) AS moyenne_generale_classe
    FROM (
        SELECT u.id AS etudiant_id, ec.classe_id,
               SUM(n.note * m.coefficient) / SUM(m.coefficient) AS moyenne_etudiant
        FROM utilisateurs u
        JOIN etudiants_classes ec ON u.id = ec.etudiant_id
        JOIN notes n ON u.id = n.etudiant_id
        JOIN matieres m ON n.matiere_id = m.id
        WHERE u.role = 'etudiant'
        GROUP BY u.id, ec.classe_id
    ) AS moyennes
    JOIN classes c ON moyennes.classe_id = c.id
    GROUP BY c.id, c.nom
    ORDER BY moyenne_generale_classe DESC
";

$stmt = $pdo->query($sql);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Résultats par Classe</title>
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

    a.btn {
      padding: 8px 14px;
      background: #43a047;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 500;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    a.btn:hover {
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
    <h1>Résultats par Classe</h1>
    <table>
      <thead>
        <tr>
          <th>Classe</th>
          <th>Moyenne Générale</th>
          <th>Détails</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($classes as $c): ?>
        <tr>
          <td data-label="Classe"><?= htmlspecialchars($c['classe']) ?></td>
          <td data-label="Moyenne Générale"><?= $c['moyenne_generale_classe'] ?></td>
          <td data-label="Détails">
            <a class="btn" href="adminstats_results.php?id=<?= $c['classe_id'] ?>">Voir</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
