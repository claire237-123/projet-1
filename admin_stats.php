<?php
session_start();

// Sécurité : uniquement administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: connexion.php");
    exit;
}

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

// Statistiques
$nbEtudiants = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'etudiant'")->fetchColumn();
$nbFormateurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'formateur'")->fetchColumn();
$nbAdmins = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'administrateur'")->fetchColumn();
$nbNotes = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$moyenneGenerale = $pdo->query("SELECT AVG(note) FROM notes")->fetchColumn();

// Listes complètes
$etudiants = $pdo->query("SELECT nom, email FROM utilisateurs WHERE role = 'etudiant' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$formateurs = $pdo->query("SELECT nom, email FROM utilisateurs WHERE role = 'formateur' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$admins = $pdo->query("SELECT nom, email FROM utilisateurs WHERE role = 'administrateur' ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$notes = $pdo->query("SELECT u.nom, m.nom AS matiere, n.note 
                      FROM notes n 
                      JOIN utilisateurs u ON n.etudiant_id = u.id 
                      JOIN matieres m ON n.matiere_id = m.id 
                      ORDER BY u.nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Statistiques - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0fff0;
      margin: 0;
      padding: 20px;
    }
    h1 {
      color: #388E3C;
      text-align: center;
    }
    .stats-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }
    .card {
      background-color: #ffffff;
      border: 1px solid #cceccc;
      border-radius: 10px;
      padding: 20px;
      width: 250px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      text-align: center;
      transition: 0.3s ease-in-out;
      cursor: pointer;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 10px rgba(0,0,0,0.2);
    }
    .card h2 {
      font-size: 2em;
      color: #4CAF50;
    }
    .card p {
      font-size: 1em;
      color: #333;
    }
    .return {
      text-align: center;
      margin-top: 30px;
    }
    .return a {
      text-decoration: none;
      color: #4CAF50;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      .card {
        width: 90%;
      }
    }

    /* 🎨 Style de la popup (modal) */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.6);
    }
    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 20px;
      border-radius: 10px;
      width: 90%;
      max-width: 700px;
      box-shadow: 0 6px 12px rgba(0,0,0,0.3);
      text-align: left;
      animation: fadeIn 0.3s ease-in-out;
    }
    .modal-content h2 {
      margin-top: 0;
      color: #4CAF50;
      text-align: center;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover {
      color: #000;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #4CAF50;
      color: white;
    }
  </style>
</head>
<body>
  <h1>📊 Statistiques générales</h1>

  <div class="stats-container">
    <div class="card" onclick="showDetails('👩‍🎓 Étudiants','<?= $nbEtudiants ?> étudiants inscrits','<?= base64_encode(json_encode($etudiants)) ?>')">
      <h2><?= $nbEtudiants ?></h2>
      <p>Étudiants</p>
    </div>
    <div class="card" onclick="showDetails('👨‍🏫 Formateurs','<?= $nbFormateurs ?> formateurs actifs','<?= base64_encode(json_encode($formateurs)) ?>')">
      <h2><?= $nbFormateurs ?></h2>
      <p>Formateurs</p>
    </div>
    <div class="card" onclick="showDetails('⚙ Administrateurs','<?= $nbAdmins ?> administrateurs enregistrés','<?= base64_encode(json_encode($admins)) ?>')">
      <h2><?= $nbAdmins ?></h2>
      <p>Administrateurs</p>
    </div>
    <div class="card" onclick="showDetails('📝 Notes','<?= $nbNotes ?> notes enregistrées','<?= base64_encode(json_encode($notes)) ?>')">
      <h2><?= $nbNotes ?></h2>
      <p>Total des notes</p>
    </div>
   
  </div>

  <!-- Modal (popup) -->
  <div id="myModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2 id="modal-title"></h2>
      <p id="modal-message"></p>
      <div id="modal-table"></div>
    </div>
    </div>

    <div class="continue">
   <center><p><a href="adminresultats_classes.php">RESULTATS</a></p></center>
</div>
  

  <div class="return">
    <p><a href="adminpage.php">⬅ Retour</a></p>
  </div>

  <script>
    function showDetails(title, message, data = null) {
      document.getElementById('modal-title').innerHTML = title;
      document.getElementById('modal-message').innerHTML = message;

      let tableDiv = document.getElementById('modal-table');
      tableDiv.innerHTML = "";

      if (data) {
        let decoded = JSON.parse(atob(data));
        if (decoded.length > 0) {
          let html = "<table><tr>";
          // En-têtes
          for (let key in decoded[0]) {
            html += "<th>"+key+"</th>";
          }
          html += "</tr>";
          // Lignes
          decoded.forEach(row => {
            html += "<tr>";
            for (let key in row) {
              html += "<td>"+row[key]+"</td>";
            }
            html += "</tr>";
          });
          html += "</table>";
          tableDiv.innerHTML = html;
        }
      }

      document.getElementById('myModal').style.display = "block";
    }
    function closeModal() {
      document.getElementById('myModal').style.display = "none";
    }
    window.onclick = function(event) {
      if (event.target == document.getElementById('myModal')) {
        closeModal();
      }
    }
  </script> 
</body>
</html>
