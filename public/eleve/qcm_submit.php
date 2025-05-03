<?php

/*******************************************************
 *  Soumission (ou auto‑soumission) d’un QCM
 *  Accepte POST (clic) ou GET (fin du temps)
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$idAtt = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!$idAtt) die('ID manquant');

$idEleve = $_SESSION['user_id'];

/* 1. Récupérer la tentative et le QCM */
$sql = 'SELECT a.*, q.duree_min
          FROM qcm_attempts a
          JOIN qcms q ON q.id = a.qcm_id
         WHERE a.id = :i AND a.eleve_id = :e';
$sth = $pdo->prepare($sql);
$sth->execute(['i' => $idAtt, 'e' => $idEleve]);
$att = $sth->fetch(PDO::FETCH_ASSOC) or die('Tentative inconnue');

/* 2. Empêcher double soumission */
if ($att['finished']) {
    header('Location:index.php?page=eleve/qcm_result&id=' . $idAtt);
    exit;
}

/* 3. Vérifier limite de temps */
$deadline = strtotime($att['start_time'] . ' +' . $att['duree_min'] . ' minutes');
if (time() > $deadline) {
    $_POST = [];                                     // aucune réponse supplémentaire
}

/* 4. Parcourir les questions de ce QCM */
$qRows = $pdo->prepare(
    'SELECT qu.id, qu.reponses
       FROM qcm_questions qq
       JOIN questions qu ON qu.id = qq.question_id
      WHERE qq.qcm_id = :q
   ORDER BY qq.ordre'
);
$qRows->execute(['q' => $att['qcm_id']]);

$good = 0;
$total = 0;

foreach ($qRows as $q) {
    $total++;
    $selected = $_POST['q' . $q['id']] ?? null;

    if ($selected !== null) {
        /* enregistrer la réponse */
        $pdo->prepare(
            'REPLACE INTO qcm_answers (attempt_id, question_id, selected)
           VALUES (:a, :q, :s)'
        )
            ->execute(['a' => $idAtt, 'q' => $q['id'], 's' => $selected]);

        /* vérifier si correcte */
        foreach (json_decode($q['reponses'], true) as $c) {
            if ($c['label'] === $selected && !empty($c['correct'])) {
                $good++;
                break;
            }
        }
    }
}

/* 5. Clôturer la tentative */
$pdo->prepare(
    'UPDATE qcm_attempts
      SET finished = 1,
          end_time = NOW(),
          good     = :g,
          total    = :t
    WHERE id = :i'
)
    ->execute(['g' => $good, 't' => $total, 'i' => $idAtt]);

/* 6. Rediriger vers la page résultat */
header('Location:index.php?page=eleve/qcm_result&id=' . $idAtt);
exit;
