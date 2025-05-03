<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
$idAtt = (int)$_GET['id'];
$idEleve = $_SESSION['user_id'];
$attempt = $pdo->prepare(
    'SELECT a.*, q.duree_min
    FROM qcm_attempts a
    JOIN qcms q ON q.id=a.qcm_id
   WHERE a.id=:i AND a.eleve_id=:e'
);
$attempt->execute(['i' => $idAtt, 'e' => $idEleve]);
$att = $attempt->fetch();
if (!$att) die('Tentative inconnue');

$deadline = strtotime($att['start_time'] . ' +' . $att['duree_min'] . ' minutes');
$remain   = $deadline - time();
if ($remain <= 0) {
    header('Location:index.php?page=eleve/qcm_submit&id=' . $idAtt);
    exit;
}

/* questions + réponses déjà cochées */
$rows = $pdo->prepare(
    'SELECT qq.question_id, qu.texte_question, qu.reponses,
         (SELECT selected FROM qcm_answers
           WHERE attempt_id=:a AND question_id=qq.question_id) AS chosen
    FROM qcm_questions qq
    JOIN questions qu ON qu.id=qq.question_id
   WHERE qq.qcm_id = :q
ORDER BY qq.ordre'
);
$rows->execute(['a' => $idAtt, 'q' => $att['qcm_id']]);
?>
<script>
    let remain = <?= $remain ?>;

    function tick() {
        if (--remain <= 0) location = 'index.php?page=eleve/qcm_submit&id=<?= $idAtt ?>';
        document.getElementById('timer').textContent = Math.floor(remain / 60) + ':' + String(remain % 60).padStart(2, '0');
    }
    setInterval(tick, 1000);
</script>
<h3 id="timer"></h3>
<form method="post" action="index.php?page=eleve/qcm_submit">
    <input type="hidden" name="id" value="<?= $idAtt ?>">
    <?php foreach ($rows as $r):
        $choices = json_decode($r['reponses'], true);
    ?>
        <p><strong><?= htmlspecialchars($r['texte_question']) ?></strong></p>
        <?php foreach ($choices as $c): ?>
            <label>
                <input type="radio" name="q<?= $r['question_id'] ?>"
                    value="<?= $c['label'] ?>"
                    <?= $r['chosen'] === $c['label'] ? 'checked' : '' ?>>
                <?= $c['label'] ?>) <?= htmlspecialchars($c['texte']) ?>
            </label><br>
        <?php endforeach; ?>
        <hr>
    <?php endforeach; ?>
    <button id="submitBtn">Soumettre</button>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function(e) {
            const unanswered = [...document.querySelectorAll('input[type=radio]')]
                .reduce((m, r) => {
                    m[r.name] = m[r.name] || false;
                    if (r.checked) m[r.name] = true;
                    return m;
                }, {});
            const blank = Object.values(unanswered).filter(v => !v).length;
            if (!confirm('Il reste ' + blank + ' question(s) sans réponse. Soumettre ?')) {
                e.preventDefault();
                return false;
            }
            /* lock back navigation */
            window.onbeforeunload = () => '';
        });
    </script>
</form>