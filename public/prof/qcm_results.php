<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$qid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$qid) die('ID manquant');

$qok = $pdo->prepare(
    'SELECT 1 FROM qcms
      WHERE id = :q
        AND auteur_id = :p'
);
$qok->execute(['q' => $qid, 'p' => $_SESSION['user_id']]);

if (!$qok->fetchColumn()) die('Accès refusé');
$rows = $pdo->prepare(
    'SELECT a.id, u.nom,
            CONCAT(a.good," / ",a.total) AS score,
            a.start_time, a.end_time
       FROM qcm_attempts a
       JOIN users u ON u.id = a.eleve_id
      WHERE a.qcm_id = :q AND a.finished = 1
   ORDER BY a.start_time'
);
$rows->execute(['q' => $qid]);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Copies rendues</title>
    <style>
        table {
            border-collapse: collapse
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 4px 6px
        }
    </style>
</head>

<body>
    <h2>Copies rendues (QCM #<?= $qid ?>)</h2>
    <table>
        <tr>
            <th>Élève</th>
            <th>Score</th>
            <th>Début</th>
            <th>Fin</th>
            <th></th>
        </tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['nom']) ?></td>
                <td><?= $r['score'] ?></td>
                <td><?= $r['start_time'] ?></td>
                <td><?= $r['end_time'] ?></td>
                <td>
                    <a href="index.php?page=eleve/qcm_result&id=<?= $r['id'] ?>" target="_blank">
                        Voir copie
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php?page=prof/qcm_list">⬅ Retour QCM</a></p>
</body>

</html>