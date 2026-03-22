<?php  
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    die("Accès refusé : vous n'êtes pas un étudiant.");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("Erreur de connexion : ".$e->getMessage());
}

$etudiant_id = $_SESSION['utilisateur_id'];
$etudiant_nom = isset($_SESSION['nom']) ? $_SESSION['nom'] : 'Étudiant';

// 🔹 Vérifier statut scolarité
$stmtStatut = $pdo->prepare("SELECT statut_scolarite FROM utilisateurs WHERE id = ?");
$stmtStatut->execute([$etudiant_id]);
$statut = $stmtStatut->fetchColumn();

if ($statut !== 'termine') {
    die("<h2 style='color:red; text-align:center;'>
        ⚠ Vous n'avez pas encore terminé votre scolarité.<br>
        L'accès à vos résultats est bloqué.
    </h2>");
}

// 🔹 Récupérer la classe via etudiants_classes
$stmtClasse = $pdo->prepare("
    SELECT c.id, c.nom, c.scolarite
    FROM etudiants_classes ec
    JOIN classes c ON ec.classe_id = c.id
    WHERE ec.etudiant_id = ?
    LIMIT 1
");
$stmtClasse->execute([$etudiant_id]);
$classe = $stmtClasse->fetch(PDO::FETCH_ASSOC);

if (!$classe) {
    die("Aucune classe associée. Contactez l'administration.");
}

// 🔹 Récupérer notes validées (tolérance sur statut)
$stmt = $pdo->prepare("
    SELECT m.nom AS matiere, 
           COALESCE(n.note, 0) AS note, 
           COALESCE(m.coefficient, 1) AS coefficient
    FROM notes n
    INNER JOIN matieres m ON n.matiere_id = m.id
    WHERE n.etudiant_id = ?
      AND n.statut IN ('valide','validee','validé')
      AND m.classe_id = ?
    ORDER BY m.nom ASC
");
$stmt->execute([$etudiant_id, $classe['id']]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Calcul moyenne pondérée
$total = 0;
$totalCoeff = 0;
if ($notes) {
    foreach ($notes as $n) {
        $note = floatval($n['note']);
        $coeff = intval($n['coefficient']);
        $total += $note * $coeff;
        $totalCoeff += $coeff;
    }
}
$moyenneGenerale = ($totalCoeff > 0) ? round($total / $totalCoeff, 2) : 0;

// 🔹 TCPDF
require_once('tcpdf_include.php');

$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Gestion des notes');
$pdf->SetTitle('Bulletin - '.$etudiant_nom);
$pdf->AddPage();

// 🔹 Mets le chemin absolu vers ton logo
$logoPath = __DIR__ . '/logo.jpg'; 
$nomEtablissement = "EPFPSA.FONDATION TCHUENTE"; 

// 🔹 Styles + HTML
$html = '
<style>
    body { font-family: helvetica; font-size: 12px; }
    .bulletin-container {
        border: 2px solid #4a44b7ff;
        border-radius: 12px;
        padding: 20px;
        background-color: #fdfdfd;
        box-shadow: 0px 0px 8px rgba(0,0,0,0.15);
    }
    .header { display: flex; align-items: center; margin-bottom: 15px; }
    .header img { height: 70px; margin-right: 15px; }
    .header .school-name { font-size: 18px; font-weight: bold; color: #2c3e50; text-align: center; }
    h1 { text-align: center; color: #44b757ff; font-size: 22px; text-transform: uppercase; margin-bottom: 5px; }
    h3 { text-align: center; margin: 0; color: #44b757ff; }
    table { border-collapse: collapse; width: 100%; margin-top: 15px; border-radius: 8px; overflow: hidden; }
    th { background-color: #44b757ff; color: white; padding: 8px; font-size: 13px; }
    td { padding: 8px; border: 1px solid #ddd; font-size: 12px; text-align: center; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .moyenne { margin-top: 15px; text-align: center; font-size: 14px; font-weight: bold; color: white; background-color: #2ecc55ff; padding: 8px; border-radius: 8px; }
</style>

<div class="bulletin-container">
    <div class="header">
        <img src="'.$logoPath.'" alt="Logo">
        <div class="school-name">'.$nomEtablissement.'</div>
    </div>

    <h1>Relevé de notes</h1>
    <h3>'.$etudiant_nom.' - Classe : '.$classe['nom'].'</h3>

    <table>
        <tr>
            <th>Matière</th>
            <th>Coefficient</th>
            <th>Note / 20</th>
            <th>Note pondérée</th>
        </tr>';

if ($notes && count($notes) > 0) {
    foreach ($notes as $n) {
        $notePonderee = $n['note'] * $n['coefficient'];
        $html .= '<tr>
                    <td>'.htmlspecialchars($n['matiere']).'</td>
                    <td>'.$n['coefficient'].'</td>
                    <td>'.number_format($n['note'], 2).'</td>
                    <td>'.number_format($notePonderee, 2).'</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="4">⚠ Aucune note validée trouvée</td></tr>';
}

$html .= '</table>
    <div class="moyenne">Moyenne annuelle : '.number_format($moyenneGenerale, 2).' / 20</div>
</div>
';

// 🔹 Affichage dans PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('releve_'.$etudiant_id.'.pdf', 'I');
