<?php 
session_start();

// Vérification admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrateur') {
    die("Accès refusé : seuls les administrateurs ont accès à cette page.");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Création table paiements si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS paiements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            etudiant_id INT NOT NULL,
            montant DECIMAL(10,2) NOT NULL,
            date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
        )
    ");

} catch(PDOException $e){
    die("Erreur de connexion : ".$e->getMessage());
}

# ==================== GESTION DES PAIEMENTS ====================

// Ajouter un paiement
if (isset($_POST['ajouter_paiement'])) {
    $etudiant_id = intval($_POST['etudiant_id']);
    $montant = floatval($_POST['montant']);
    if ($montant > 0) {
        $stmt = $pdo->prepare("INSERT INTO paiements (etudiant_id, montant) VALUES (?, ?)");
        $stmt->execute([$etudiant_id, $montant]);
    }
}

// Modifier un paiement
if (isset($_POST['modifier_paiement'])) {
    $paiement_id = intval($_POST['paiement_id']);
    $montant = floatval($_POST['montant']);
    if ($montant > 0) {
        $stmt = $pdo->prepare("UPDATE paiements SET montant = ? WHERE id = ?");
        $stmt->execute([$montant, $paiement_id]);
    }
}

// Supprimer un paiement
if (isset($_POST['supprimer_paiement'])) {
    $paiement_id = intval($_POST['paiement_id']);
    $stmt = $pdo->prepare("DELETE FROM paiements WHERE id = ?");
    $stmt->execute([$paiement_id]);
}

# ==================== RECUPERATION DES DONNEES ====================

// Liste des étudiants avec leurs classes et scolarité
$stmt = $pdo->query("
    SELECT u.id, u.nom, u.statut_scolarite,
           COALESCE(SUM(DISTINCT c.scolarite),0) AS scolarite_totale,
           GROUP_CONCAT(DISTINCT c.nom SEPARATOR ', ') AS classes
    FROM utilisateurs u
    LEFT JOIN etudiants_classes ec ON u.id = ec.etudiant_id
    LEFT JOIN classes c ON ec.classe_id = c.id
    WHERE u.role = 'etudiant'
    GROUP BY u.id, u.nom, u.statut_scolarite
    ORDER BY u.nom ASC
");

$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historique + sommes
$historique = [];
$sommes = [];
foreach ($etudiants as $e) {
    // Historique paiements
    $stmt = $pdo->prepare("SELECT * FROM paiements WHERE etudiant_id = ? ORDER BY date_paiement DESC");
    $stmt->execute([$e['id']]);
    $historique[$e['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Somme totale payée
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant),0) as total FROM paiements WHERE etudiant_id = ?");
    $stmt->execute([$e['id']]);
    $total_paye = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $sommes[$e['id']] = $total_paye;

    // 🔥 Mise à jour automatique du statut_scolarite
    $scolarite = !empty($e['scolarite_totale']) ? $e['scolarite_totale'] : 0;
    if ($scolarite > 0 && $total_paye >= $scolarite) {
        $pdo->prepare("UPDATE utilisateurs SET statut_scolarite = 'termine' WHERE id = ?")
            ->execute([$e['id']]);
    } else {
        $pdo->prepare("UPDATE utilisateurs SET statut_scolarite = 'en_cours' WHERE id = ?")
            ->execute([$e['id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion scolarité - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        .container { width: 95%; margin: 20px auto; background: white; padding: 20px;
                     border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #4CAF50; color: white; }
        button { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; }
        .ajout-btn { background: #16a085; color: white; }
        .historique-btn { background: #2980b9; color: white; }
        .close { float:right; color:red; font-size:20px; cursor:pointer; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                 background:rgba(0,0,0,0.6); padding-top:60px; }
        .modal-content { background:white; margin:auto; padding:20px; border-radius:10px; width:60%; }
        /* ✅ Statuts colorés */
        .status-termine { background: #27ae60; color: white; font-weight: bold; padding:5px 10px; border-radius:5px; }
        .status-encours { background: #e67e22; color: white; font-weight: bold; padding:5px 10px; border-radius:5px; }
    </style>
</head>
<body>
<div class="container">
    <h2>📘 Gestion scolarité</h2>
    <table>
        <tr>
            <th>Étudiant</th>
            <th>Classe(s)</th>
            <th>Scolarité totale</th>
            <th>Total payé</th>
            <th>Reste</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($etudiants as $e): 
            $total = !empty($e['scolarite_totale']) ? $e['scolarite_totale'] : 0;
            $verse = $sommes[$e['id']];
            $reste = max(0, $total - $verse);
            $termine = ($total > 0 && $verse >= $total);
        ?>
        <tr>
            <td><?= htmlspecialchars($e['nom']) ?></td>
            <td><?= htmlspecialchars($e['classes']     ) ?></td>
            <td><?= number_format($total, 0, ',', ' ') ?> FCFA</td>
            <td><?= number_format($verse, 0, ',', ' ') ?> FCFA</td>
            <td><?= number_format($reste, 0, ',', ' ') ?> FCFA</td>
            <td>
                <?php if ($termine): ?>
                    <span class="status-termine">✅ Terminée</span>
                <?php else: ?>
                    <span class="status-encours">⏳ En cours</span>
                <?php endif; ?>
            </td>
            <td>
                <!-- Ajout paiement -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="etudiant_id" value="<?= $e['id'] ?>">
                    <input type="number" name="montant" min="1" placeholder="Montant" required>
                    <button type="submit" name="ajouter_paiement" class="ajout-btn">Ajouter</button>
                </form>
                <!-- Historique -->
                <button onclick="document.getElementById('modal-<?= $e['id'] ?>').style.display='block'" class="historique-btn">Historique</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Modals historique -->
<?php foreach ($etudiants as $e): ?>
<div id="modal-<?= $e['id'] ?>" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('modal-<?= $e['id'] ?>').style.display='none'">&times;</span>
    <h3>Paiements - <?= htmlspecialchars($e['nom']) ?></h3>
    <?php if (!empty($historique[$e['id']])): ?>
    <table style="width:100%;">
        <tr><th>Date</th><th>Montant</th><th>Actions</th></tr>
        <?php foreach ($historique[$e['id']] as $p): ?>
        <tr>
            <td><?= $p['date_paiement'] ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="paiement_id" value="<?= $p['id'] ?>">
                    <input type="number" name="montant" value="<?= $p['montant'] ?>" min="1">
                    <button type="submit" name="modifier_paiement" style="background:#2980b9;color:white;">Modifier</button>
                </form>
            </td>
            <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce paiement ?');">
                    <input type="hidden" name="paiement_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="supprimer_paiement" style="background:#e74c3c;color:white;">Supprimer</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>Aucun paiement enregistré.</p>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
 <a href="classegestion.php">Retour</a>
</body>
</html>
