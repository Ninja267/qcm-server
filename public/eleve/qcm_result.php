<?php

/*******************************************************
 *  qcm_result.php
 *  – sans paramètre  : liste de mes tentatives terminées
 *  – avec  paramètre : détail d’une copie + navigation
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$idEleve = $_SESSION['user_id'] ?? 0;
$attId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* -------------------------------------------------- */
/* 0.  Historique si aucun id                         */
/* -------------------------------------------------- */
if (!$attId) {
    $rows = $pdo->prepare(
        'SELECT a.id, q.titre, a.good, a.total,
                a.start_time, a.end_time
           FROM qcm_attempts a
           JOIN qcms q ON q.id = a.qcm_id
          WHERE a.eleve_id = :e AND a.finished = 1
       ORDER BY a.start_time DESC'
    );
    $rows->execute(['e' => $idEleve]);
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Mes résultats</title>
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
        <p style="text-align:right"><a href="logout.php">Déconnexion</a></p>
        <h1>Historique de mes copies</h1>
        <table>
            <tr>
                <th>ID</th>
                <th>Examen</th>
                <th>Note</th>
                <th>Démarré</th>
                <th>Fini</th>
                <th></th>
            </tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td><?= $r['good'] . ' / ' . $r['total'] ?></td>
                    <td><?= $r['start_time'] ?></td>
                    <td><?= $r['end_time'] ?></td>
                    <td><a href="index.php?page=eleve/qcm_result&id=<?= $r['id'] ?>">voir</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p><a href="index.php?page=eleve/dashboard">⬅ Tableau de bord</a></p>
    </body>

    </html>
<?php
    exit;
}

/* -------------------------------------------------- */
/* 1.  Chargement de la tentative demandée            */
/* -------------------------------------------------- */
$sql = 'SELECT a.*, q.titre
          FROM qcm_attempts a
          JOIN qcms q ON q.id = a.qcm_id
         WHERE a.id = :i AND a.eleve_id = :e';
$sth = $pdo->prepare($sql);
$sth->execute(['i' => $attId, 'e' => $idEleve]);
$att = $sth->fetch(PDO::FETCH_ASSOC) or die('Introuvable');

/* 2.  Liste de toutes les copies de ce QCM pour nav  */
$all = $pdo->prepare(
    'SELECT id, good, total, start_time
       FROM qcm_attempts
      WHERE qcm_id = :q AND eleve_id = :e AND finished = 1
   ORDER BY start_time DESC'
);
$all->execute(['q' => $att['qcm_id'], 'e' => $idEleve]);

/* 3.  Réponses de cette tentative                   */
$qRows = $pdo->prepare(
    'SELECT qu.texte_question, qu.reponses, ans.selected
       FROM qcm_answers ans
       JOIN questions qu ON qu.id = ans.question_id
      WHERE ans.attempt_id = :a
   ORDER BY qu.id'
);
$qRows->execute(['a' => $attId]);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Résultat – <?= htmlspecialchars($att['titre']) ?></title>
    <style>
        table {
            border-collapse: collapse;
            margin-bottom: 1rem
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 4px 6px
        }

        .ok {
            color: green
        }

        .ko {
            color: red
        }
    </style>
</head>

<body>
    <p style="text-align:right"><a href="logout.php">Déconnexion</a></p>

    <h2><?= htmlspecialchars($att['titre']) ?> – mes copies</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Note</th>
            <th>Démarré</th>
            <th></th>
        </tr>
        <?php foreach ($all as $row): ?>
            <tr<?= $row['id'] == $attId ? ' style="background:#eef"' : '' ?>>
                <td><?= $row['id'] ?></td>
                <td><?= $row['good'] . ' / ' . $row['total'] ?></td>
                <td><?= $row['start_time'] ?></td>
                <td>
                    <?php if ($row['id'] != $attId): ?>
                        <a href="index.php?page=eleve/qcm_result&id=<?= $row['id'] ?>">voir</a>
                        <?php else: ?>← cette copie<?php endif; ?>
                </td>
                </tr>
            <?php endforeach; ?>
    </table>

    <h3>Copie #<?= $attId ?> — note : <?= $att['good'] . ' / ' . $att['total'] ?></h3>

    <?php foreach ($qRows as $row):
        $choices = json_decode($row['reponses'], true); ?>
        <p><strong><?= htmlspecialchars($row['texte_question']) ?></strong></p>
        <ul>
            <?php foreach ($choices as $c):
                $good = !empty($c['correct']);
                $picked = $row['selected'] === $c['label']; ?>
                <li class="<?= $good ? 'ok' : ($picked ? 'ko' : '') ?>">
                    <?= $c['label'] ?>) <?= htmlspecialchars($c['texte']) ?>
                    <?php if ($good)  echo ' ✅';
                    elseif ($picked) echo ' ❌'; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <hr>
    <?php endforeach; ?>

    <p><a href="index.php?page=eleve/qcm_result">⬅ Retour à l’historique</a></p>
</body>

</html>