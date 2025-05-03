<?php

/************************************************************
 *  public/prof/qcm_copy.php
 *  ─────────────────────────────────────────────────────────
 *      Affiche le détail d’une copie (tentative terminée)
 *      – Paramètre GET : id   (id de qcm_attempts)
 *      – Accès : uniquement l’auteur du QCM concerné
 ************************************************************/

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

/* ----------- Sécurité ----------- */
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location: index.php?page=login');
    exit;
}

/* ----------- Paramètre ----------- */
$attId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($attId <= 0) die('ID manquant');

/* ----------- Vérifier & charger la tentative ----------- */
$sql = '
SELECT a.*, q.titre, u.nom AS eleve_nom
  FROM qcm_attempts a
  JOIN qcms  q ON q.id = a.qcm_id
  JOIN users u ON u.id = a.eleve_id
 WHERE a.id = :id
   AND q.auteur_id = :prof    /* s’assure que le QCM est bien à ce prof */
   AND a.finished = 1         /* copie clôturée */
LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $attId, 'prof' => $_SESSION['user_id']]);
$att = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$att) die('Introuvable ou accès refusé');

/* ----------- Questions / réponses ----------- */
$qRowsStmt = $pdo->prepare(
    'SELECT qu.texte_question, qu.reponses, ans.selected
       FROM qcm_answers ans
       JOIN questions qu ON qu.id = ans.question_id
      WHERE ans.attempt_id = :a
   ORDER BY qu.id'
);
$qRowsStmt->execute(['a' => $attId]);
$qRows = $qRowsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Copie #<?= $attId ?> – <?= htmlspecialchars($att['titre']) ?></title>
    <style>
        table {
            border-collapse: collapse;
            margin-bottom: 1rem;
            width: 100%
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 4px 6px;
            vertical-align: top
        }

        th {
            background: #f0f0f0
        }

        .big {
            text-align: center;
            font-size: 24px
        }
    </style>
</head>

<body>
    <p style="text-align:right"><a href="index.php?page=prof/qcm_results&id=<?= $att['qcm_id'] ?>">⬅ Retour aux copies</a></p>

    <h2>Copie #<?= $attId ?> — élève : <?= htmlspecialchars($att['eleve_nom']) ?></h2>
    <p><strong>Note&nbsp;:</strong> <?= $att['good'] . ' / ' . $att['total'] ?>
        &nbsp;|&nbsp; <strong>Démarré&nbsp;:</strong> <?= $att['start_time'] ?>
        &nbsp;|&nbsp; <strong>Fin&nbsp;:</strong> <?= $att['end_time'] ?></p>
    <hr>

    <?php foreach ($qRows as $row):

        /* ---------- préparation des ensembles ---------- */
        $defs        = json_decode($row['reponses'], true) ?: [];
        $pickedRaw   = json_decode($row['selected'], true);
        $pickedArr   = is_array($pickedRaw) ? $pickedRaw : ($pickedRaw ? [$pickedRaw] : []);

        $goodLabels  = array_column(array_filter($defs, fn($c) => !empty($c['correct'])), 'label');
        sort($goodLabels);
        $pickedUniq  = array_values(array_unique($pickedArr));
        sort($pickedUniq);

        $ok = ($goodLabels === $pickedUniq);   // comparaison globale
    ?>
        <p><strong><?= htmlspecialchars($row['texte_question']) ?></strong></p>

        <table>
            <tr>
                <th>Réponse correcte</th>
                <th>Réponse de l’élève</th>
                <th>✓ / ✗</th>
            </tr>
            <tr>
                <!-- Colonne bonnes réponses -->
                <td>
                    <?php foreach ($defs as $c)
                        if (!empty($c['correct']))
                            echo $c['label'] . ') ' . htmlspecialchars($c['texte']) . '<br>'; ?>
                </td>

                <!-- Colonne choisies -->
                <td>
                    <?php
                    if (!$pickedArr) echo '—';
                    else foreach ($defs as $c)
                        if (in_array($c['label'], $pickedArr))
                            echo $c['label'] . ') ' . htmlspecialchars($c['texte']) . '<br>';
                    ?>
                </td>

                <!-- ✓ ou ✗ -->
                <td class="big"><?= $ok ? '✅' : '❌' ?></td>
            </tr>
        </table>
    <?php endforeach; ?>
</body>

</html>