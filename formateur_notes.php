<?php
session_start();

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'formateur') {
    header("Location: connexion.php");
    exit;
}

$formateur_id = (int)$_SESSION['utilisateur_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$message = "";

/** Helpers **/
function fetchOneValue($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $val = $st->fetchColumn();
    return ($val === false) ? null : $val;
}

/* ========= AJOUT OU MISE À JOUR D'UNE NOTE ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'ajouter_note') {
    $etudiant_id = (int)$_POST['etudiant_id'];
    $matiere_id  = (int)$_POST['matiere_id'];
    $note        = (float)$_POST['note'];

    if ($note < 0 || $note > 20) {
        $message = "❌ La note doit être entre 0 et 20.";
    } else {
        $affecte = fetchOneValue($pdo,
            "SELECT 1 FROM formateurs_etudiants WHERE formateur_id = ? AND etudiant_id = ? LIMIT 1",
            [$formateur_id, $etudiant_id]
        );

        if (!$affecte) {
            $message = "❌ Cet étudiant n'est pas affecté à vous.";
        } else {
            // Récupérer nom + coefficient de la matière
            $matiere_info = $pdo->prepare("SELECT nom, coefficient FROM matieres WHERE id = ? AND formateur_id = ? LIMIT 1");
            $matiere_info->execute([$matiere_id, $formateur_id]);
            $matiere_info = $matiere_info->fetch(PDO::FETCH_ASSOC);

            if (!$matiere_info) {
                $message = "❌ Cette matière ne vous appartient pas.";
            } else {
                $note_id = fetchOneValue($pdo,
                    "SELECT id FROM notes WHERE etudiant_id = ? AND matiere_id = ? LIMIT 1",
                    [$etudiant_id, $matiere_id]
                );

                if ($note_id) {
                    $upd = $pdo->prepare("UPDATE notes SET note = ?, date_ajout = NOW() WHERE id = ?");
                    $upd->execute([$note, $note_id]);
                    $message = "✏️ Note mise à jour.";
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO notes (etudiant_id, matiere, note, matiere_id, statut, date_ajout)
                        VALUES (?, ?, ?, ?, 'en_attente', NOW())
                    ");
                    $ins->execute([$etudiant_id, $matiere_info['nom'], $note, $matiere_id]);
                    $message = "✅ Note ajoutée (en attente de validation).";
                }
            }
        }
    }
}

/* ========= MODIFIER NOTE ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'modifier_note') {
    $note_id   = (int)$_POST['note_id'];
    $note_edit = (float)$_POST['note_edit'];

    if ($note_edit < 0 || $note_edit > 20) {
        $message = "❌ La note doit être entre 0 et 20.";
    } else {
        $autorise = fetchOneValue($pdo, "
            SELECT 1
            FROM notes n
            JOIN formateurs_etudiants fe ON n.etudiant_id = fe.etudiant_id
            JOIN matieres m ON n.matiere_id = m.id
            WHERE n.id = ? AND fe.formateur_id = ? AND m.formateur_id = ?
            LIMIT 1
        ", [$note_id, $formateur_id, $formateur_id]);

        if ($autorise) {
            $pdo->prepare("UPDATE notes SET note = ?, date_ajout = NOW() WHERE id = ?")
                ->execute([$note_edit, $note_id]);
            $message = "✅ Note modifiée.";
        } else {
            $message = "❌ Accès refusé.";
        }
    }
}

/* ========= SUPPRIMER NOTE ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === 'supprimer_note') {
    $note_id = (int)$_POST['note_id'];

    $del = $pdo->prepare("
        DELETE n FROM notes n
        JOIN formateurs_etudiants fe ON n.etudiant_id = fe.etudiant_id
        JOIN matieres m ON n.matiere_id = m.id
        WHERE n.id = ? AND fe.formateur_id = ? AND m.formateur_id = ?
    ");
    $del->execute([$note_id, $formateur_id, $formateur_id]);

    $message = $del->rowCount() > 0 ? "🗑️ Note supprimée." : "❌ Suppression impossible.";
}

/* ========= LISTE DES ÉTUDIANTS ========= */
$etudiants = $pdo->prepare("
    SELECT u.id, u.nom
    FROM utilisateurs u
    JOIN formateurs_etudiants fe ON u.id = fe.etudiant_id
    WHERE fe.formateur_id = ?
    ORDER BY u.nom
");
$etudiants->execute([$formateur_id]);
$etudiants = $etudiants->fetchAll(PDO::FETCH_ASSOC);

/* ========= LISTE DES MATIÈRES ========= */
$matieres = $pdo->prepare("SELECT id, nom, coefficient FROM matieres WHERE formateur_id = ? ORDER BY nom");
$matieres->execute([$formateur_id]);
$matieres = $matieres->fetchAll(PDO::FETCH_ASSOC);

/* ========= NOTES D'UN ÉTUDIANT ========= */
$notes = [];
$selected_id = isset($_GET['etudiant_id']) ? (int)$_GET['etudiant_id'] : 0;
if ($selected_id) {
    $stmt = $pdo->prepare("
        SELECT n.id, m.nom AS matiere, m.coefficient, n.note, n.date_ajout, n.statut
        FROM notes n
        JOIN matieres m ON m.id = n.matiere_id
        WHERE n.etudiant_id = ? AND m.formateur_id = ?
        ORDER BY n.date_ajout DESC
    ");
    $stmt->execute([$selected_id, $formateur_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des notes - Formateur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      --green-1:#2e7d32; --green-2:#4CAF50; --green-3:#388E3C;
      --bg-1:#e8f5e9; --bg-2:#ffffff; --border:#ddd;
    }
    body{font-family:system-ui,Arial;background:linear-gradient(to right,var(--bg-1),var(--bg-2));margin:0;padding:20px}
    .container{max-width:1000px;margin:auto;background:#fff;padding:24px;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,0.08)}
    h1,h2{color:var(--green-1);margin:0 0 14px}
    select,input[type="number"]{padding:10px;width:100%;max-width:420px;border:1px solid var(--border);border-radius:8px;font-size:15px;background:#fff}
    button{background:var(--green-2);color:white;padding:10px 16px;border:none;border-radius:10px;cursor:pointer;font-weight:600}
    button:hover{background:var(--green-3)}
    .message{margin:14px 0;padding:12px 14px;border-left:5px solid var(--green-2);border-radius:8px;background:#e8f5f1}
    table{width:100%;border-collapse:collapse;margin-top:16px;border-radius:10px;overflow:hidden}
    th,td{border:1px solid var(--border);padding:10px;text-align:left}
    th{background:#a5d6a7}
    td input[type="number"]{max-width:110px}
    .inline{display:inline}
    @media (max-width:640px){th:nth-child(3),td:nth-child(3){display:none}}
  </style>
</head>
<body>
<div class="container">
<h1>📚 Gestion des notes</h1>
<p> <a href="formateurpage.php">Retour</a></p>
<p><a href="resultats_examens_formateur.php">Examens en ligne</a></p>

<?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<h2>Ajouter / Mettre à jour une note</h2>
<form method="POST">
  <input type="hidden" name="action" value="ajouter_note">
  <label>Étudiant</label>
  <select name="etudiant_id" required>
    <option value="">-- Choisir un étudiant --</option>
    <?php foreach ($etudiants as $e): ?>
      <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Matière (avec coefficient)</label>
  <select name="matiere_id" required>
    <option value="">-- Choisir une matière --</option>
    <?php foreach ($matieres as $m): ?>
      <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom'])." (Coef ".$m['coefficient'].")" ?></option>
    <?php endforeach; ?>
  </select><br><br>

  <label>Note</label>
  <input type="number" name="note" step="0.01" min="0" max="20" required><br><br>

  <button type="submit">Enregistrer</button>
</form>

<hr>

<h2>Voir les notes</h2>
<form method="GET">
  <label>Étudiant</label>
  <select name="etudiant_id" onchange="this.form.submit()">
    <option value="">-- Sélectionner --</option>
    <?php foreach ($etudiants as $e): ?>
      <option value="<?= $e['id'] ?>" <?= ($selected_id == $e['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($e['nom']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if ($selected_id && $notes): ?>
  <table>
    <tr>
      <th>Matière</th>
      <th>Coefficient</th>
      <th>Note</th>
      <th>Date</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($notes as $n): ?>
    <tr>
      <form method="POST" class="inline">
        <td><?= htmlspecialchars($n['matiere']) ?></td>
        <td><?= (int)$n['coefficient'] ?></td>
        <td><input type="number" name="note_edit" value="<?= htmlspecialchars($n['note']) ?>" step="0.01" min="0" max="20" required></td>
        <td><?= htmlspecialchars($n['date_ajout']) ?></td>
        <td><?= htmlspecialchars($n['statut']) ?></td>
        <td>
          <input type="hidden" name="note_id" value="<?= (int)$n['id'] ?>">
          <input type="hidden" name="action" value="modifier_note">
          <button type="submit">💾</button>
      </form>
      <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette note ?')">
        <input type="hidden" name="note_id" value="<?= (int)$n['id'] ?>">
        <input type="hidden" name="action" value="supprimer_note">
        <button type="submit">🗑️</button>
      </form>
        </td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php elseif ($selected_id): ?>
  <p>Aucune note pour cet étudiant.</p>
<?php endif; ?>
</div>
</body>
</html>
