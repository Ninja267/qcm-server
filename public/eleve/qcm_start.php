<?php

/*******************************************************
 *  Démarre (ou reprend) une tentative d’examen
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$idEleve = $_SESSION['user_id'];
$qcmId   = (int)($_POST['id'] ?? 0);

/* 1. Vérifier que le QCM est publié */
$qcm = $pdo->prepare('SELECT * FROM qcms WHERE id = :i AND visible = 1');
$qcm->execute(['i' => $qcmId]);
$q = $qcm->fetch();
if (!$q) die('Examen indisponible');

/* 2. Existe‑t‑il déjà une tentative non terminée ? */
$cur = $pdo->prepare(
    'SELECT id FROM qcm_attempts
     WHERE qcm_id = :q AND eleve_id = :e AND finished = 0
     LIMIT 1'
);
$cur->execute(['q' => $qcmId, 'e' => $idEleve]);
$attempt = $cur->fetch();

if ($attempt) {
    $idAttempt = $attempt['id'];          /* on reprend */
} else {
    /* 3. Vérifier qu’il reste des tentatives */
    $finies = $pdo->prepare(
        'SELECT COUNT(*) FROM qcm_attempts
         WHERE qcm_id = :q AND eleve_id = :e AND finished = 1'
    );
    $finies->execute(['q' => $qcmId, 'e' => $idEleve]);
    $restantes = $q['tentative_max'] - $finies->fetchColumn();
    if ($restantes <= 0) die('Plus de tentative disponible');

    /* 4. Créer la nouvelle tentative */
    $pdo->prepare(
        'INSERT INTO qcm_attempts(qcm_id, eleve_id, start_time)
       VALUES(:q, :e, NOW())'
    )
        ->execute(['q' => $qcmId, 'e' => $idEleve]);
    $idAttempt = $pdo->lastInsertId();
}

/* 5. Rediriger vers la page de passage */
header('Location: index.php?page=eleve/qcm_pass&id=' . $idAttempt);
exit;
