<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$qid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- Liste des QCM si aucun id ---------- */
if ($qid === 0) {
    $list = $pdo->prepare(
        'SELECT id, titre, date_examen
           FROM qcms
          WHERE auteur_id = :p
       ORDER BY date_examen DESC'
    );
    $list->execute(['p' => $_SESSION['user_id']]);
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Résultats QCM</title>
    </head>

    <body>
        <h1>Choisissez un QCM</h1>
        <ul>
            <?php foreach ($list as $l): ?>
                <li><a href="index.php?page=prof/qcm_results&id=<?= $l['id'] ?>">
                        #<?= $l['id'] ?> — <?= htmlspecialchars($l['titre']) ?>
                        (<?= $l['date_examen'] ?>)
                    </a></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="index.php?page=prof/dashboard">⬅ Tableau de bord</a></p>
    </body>

    </html>
<?php exit;
}

/* ---------- Vérification d’accès ---------- */
$ok = $pdo->prepare(
    'SELECT 1 FROM qcms WHERE id=:q AND auteur_id=:p'
);
$ok->execute(['q' => $qid, 'p' => $_SESSION['user_id']]);
if (!$ok->fetchColumn()) die('Accès refusé');

/* ---------- Copies rendues ---------- */
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
                <td><a href="index.php?page=prof/qcm_copy&id=<?= $r['id'] ?>" target="_blank">
                        Voir copie
                    </a></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php?page=prof/qcm_list">⬅ Retour QCM</a></p>
</body>

</html>