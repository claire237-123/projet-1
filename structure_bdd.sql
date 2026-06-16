<?php
session_start();

/* =========================
   PROTECTION ÉTUDIANT
========================= */
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: connexion.php");
    exit;
}

/* =========================
   CONNEXION BD
========================= */
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_notes;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur BD : " . $e->getMessage());
}

$etudiant_id = $_SESSION['utilisateur_id'];
$message = "";

/* =========================
   PASSAGE EXAMEN
========================= */
if (isset($_POST['passer_examen'])) {

    $examen_id = $_POST['examen_id'];

    // Vérifier si déjà passé
    $check = $pdo->prepare("
        SELECT id 
        FROM resultats_examens 
        WHERE etudiant_id = ? AND examen_id = ?
    ");
    $check->execute([$etudiant_id, $examen_id]);

    if ($check->fetch()) {
        $message = "⚠️ Vous avez déjà passé cet examen.";
    } else {

        // Récupérer les questions (IMPORTANT : pas de matiere_id ici)
        $stmt = $pdo->prepare("
            SELECT * 
            FROM questions 
            WHERE examen_id = ?
        ");
        $stmt->execute([$examen_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $score = 0;
        $total = count($questions);

        foreach ($questions as $q) {

            $reponse = $_POST['q_'.$q['id']] ?? '';

            // Correction simple
            if (trim(strtolower($reponse)) == trim(strtolower($q['bonne_reponse']))) {
                $score++;
            }

            // Sauvegarde réponse étudiant
            $save = $pdo->prepare("
                INSERT INTO reponses_etudiants 
                (etudiant_id, question_id, reponse, note)
                VALUES (?, ?, ?, ?)
            ");

            $save->execute([
                $etudiant_id,
                $q['id'],
                $reponse,
                ($reponse == $q['bonne_reponse']) ? 1 : 0
            ]);
        }

        // Calcul note finale sur 20
        $note_finale = $total > 0 ? ($score / $total) * 20 : 0;

        // Sauvegarde résultat final
        $insert = $pdo->prepare("
            INSERT INTO resultats_examens 
            (etudiant_id, examen_id, note_finale)
            VALUES (?, ?, ?)
        ");

        $insert->execute([
            $etudiant_id,
            $examen_id,
            $note_finale
        ]);

        $message = "✅ Examen terminé ! Note : " . round($note_finale, 2) . "/20";
    }
}

/* =========================
   LISTE EXAMENS
========================= */
$examens = $pdo->query("
    SELECT * 
    FROM examens 
    ORDER BY date_creation DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Examens</title>

<style>
body {
    font-family: Arial;
    background: #f1f8e9;
    padding: 20px;
}

h2 { color: #2e7d32; }

.box {
    background: white;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.question {
    margin: 10px 0;
    padding: 10px;
    border-left: 4px solid #4CAF50;
}

button {
    padding: 8px 12px;
    background: #4CAF50;
    color: white;
    border: none;
    cursor: pointer;
}

.msg {
    background: #fff;
    padding: 10px;
    border-left: 5px solid #4CAF50;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<h2>📚 Examens disponibles</h2>

<p><a href="etudiantpage.php">← Retour</a></p>

<?php if ($message): ?>
<div class="msg"><?= $message ?></div>
<?php endif; ?>

<?php foreach ($examens as $examen): ?>

<div class="box">

    <h3><?= htmlspecialchars($examen['titre']) ?></h3>
    <p><?= htmlspecialchars($examen['description']) ?></p>

    <?php
    // Récupération questions (CORRECTE)
    $stmt = $pdo->prepare("
        SELECT * 
        FROM questions 
        WHERE examen_id = ?
    ");
    $stmt->execute([$examen['id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <form method="POST">

        <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">

        <?php foreach ($questions as $q): ?>
            <div class="question">
                <strong><?= htmlspecialchars($q['question']) ?></strong><br>

                <?php if ($q['type'] == 'qcm'): ?>
                    <input type="text" name="q_<?= $q['id'] ?>" placeholder="Réponse">
                <?php elseif ($q['type'] == 'vf'): ?>
                    <select name="q_<?= $q['id'] ?>">
                        <option value="vrai">Vrai</option>
                        <option value="faux">Faux</option>
                    </select>
                <?php else: ?>
                    <textarea name="q_<?= $q['id'] ?>"></textarea>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <?php if (count($questions) > 0): ?>
            <button type="submit" name="passer_examen">Terminer</button>
        <?php else: ?>
            <p>Aucune question disponible</p>
        <?php endif; ?>

    </form>

</div>

<?php endforeach; ?>

</body>
</html>