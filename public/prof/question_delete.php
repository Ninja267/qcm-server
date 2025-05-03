<?php

/**************************************************************
 *  public/prof/question_delete.php
 *  ───────────────────────────────────────────────────────────
 *      • Affiche d’abord la liste des QCM impactés et demande
 *        confirmation.
 *      • Si ?confirm=1  →  supprime la question (cascade).
 **************************************************************/

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Sécurité : uniquement prof ---------- */
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/db.php';

/* ---------- Paramètres ---------- */
$qid     = isset($_GET['id'])      ? (int)$_GET['id']      : 0;
$confirm = isset($_GET['confirm']) ? (int)$_GET['confirm'] : 0;

if ($qid <= 0) {
    header('Location: index.php?page=prof/question_list');
    exit;
}

/* ---------- Étape 2 : suppression effective ---------- */
if ($confirm === 1) {
    $del = $pdo->prepare(
        'DELETE FROM questions
          WHERE id        = :id
            AND auteur_id = :p'
    );
    $del->execute([
        'id' => $qid,
        'p'  => $_SESSION['user_id']
    ]);

    /* rowCount() == 0  → question inexistante ou n’appartient pas à ce prof */
    header('Location: index.php?page=prof/question_list&msg=' .
        ($del->rowCount() ? 'del_ok' : 'del_fail'));
    exit;
}

/* ---------- Étape 1 : page de confirmation ---------- */

/* Récupérer la question (titre + énoncé court) */
$q = $pdo->prepare(
    'SELECT texte_question
       FROM questions
      WHERE id = :id
        AND auteur_id = :p'
);
$q->execute(['id' => $qid, 'p' => $_SESSION['user_id']]);
$question = $q->fetch(PDO::FETCH_ASSOC);

if (!$question) {                       // rien à soi ou id invalide
    header('Location: index.php?page=prof/question_list&msg=not_found');
    exit;
}

/* Quels QCM contiennent encore cette question ? */
$sth = $pdo->prepare(
    'SELECT q.id, q.titre
       FROM qcm_questions   qq
       JOIN qcms            q  ON q.id = qq.qcm_id
      WHERE qq.question_id = :q'
);
$sth->execute(['q' => $qid]);
$qcms = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Supprimer la question <?= $qid ?></title>
    <style>
        ul {
            margin-top: 0
        }

        li {
            margin-left: 18px
        }
    </style>
</head>

<body>
    <h1>Supprimer la question #<?= $qid ?></h1>

    <p style="color:red;font-weight:bold">
        Cette action est <u>définitive</u> !<br>
        La question sera retirée des QCM listés ci‑dessous et toutes les
        réponses associées seront supprimées.
    </p>

    <p><strong>Énoncé :</strong><br>
        <?= htmlspecialchars(mb_strimwidth($question['texte_question'], 0, 120, '…')) ?></p>

    <?php if ($qcms): ?>
        <p>Elle apparaît actuellement dans
            <?= count($qcms) ?> QCM :</p>
        <ul>
            <?php foreach ($qcms as $qc): ?>
                <li>#<?= $qc['id'] ?> —
                    <?= htmlspecialchars($qc['titre']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Cette question n’est plus utilisée dans aucun QCM.</p>
    <?php endif; ?>

    <p>
        <a href="index.php?page=prof/question_delete&id=<?= $qid ?>&confirm=1"
            onclick="return confirm('Confirmer la suppression définitive ?');">
            ✅ Oui, supprimer définitivement
        </a>
        &nbsp;|&nbsp;
        <a href="index.php?page=prof/question_list">Annuler</a>
    </p>
</body>

</html>