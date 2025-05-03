<?php

/*******************************************************
 *  qcm_result.php — historique ou détail d’une copie
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$idEleve = $_SESSION['user_id'] ?? 0;
$attId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ------- 0. Historique ------- */
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
<?php exit;
}

/* ------- 1. Détail d’une tentative ------- */
$att = $pdo->prepare(
    'SELECT a.*, q.titre
       FROM qcm_attempts a
       JOIN qcms q ON q.id = a.qcm_id
      WHERE a.id = :i AND a.eleve_id = :e'
);
$att->execute(['i' => $attId, 'e' => $idEleve]);
$att = $att->fetch(PDO::FETCH_ASSOC) or die('Introuvable');

/* autres copies de ce QCM pour navigation */
$all = $pdo->prepare(
    'SELECT id, good, total, start_time
       FROM qcm_attempts
      WHERE qcm_id = :q AND eleve_id = :e AND finished = 1
   ORDER BY start_time DESC'
);
$all->execute(['q' => $att['qcm_id'], 'e' => $idEleve]);

/* réponses */
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
        <?php foreach ($all as $r): ?>
            <tr<?= $r['id'] === $attId ? ' style="background:#eef"' : '' ?>>
                <td><?= $r['id'] ?></td>
                <td><?= $r['good'] . ' / ' . $r['total'] ?></td>
                <td><?= $r['start_time'] ?></td>
                <td><?= $r['id'] === $attId ? '← cette copie' :
                        '<a href="index.php?page=eleve/qcm_result&id=' . $r['id'] . '">voir</a>' ?></td>
                </tr>
            <?php endforeach; ?>
    </table>

    <h3>Copie #<?= $attId ?> — note : <?= $att['good'] . ' / ' . $att['total'] ?></h3>

    <?php foreach ($qRows as $row):

        $defs        = json_decode($row['reponses'], true);               // définitions
        $picked      = json_decode($row['selected'], true);
        $picked      = is_array($picked) ? $picked : ($picked ? [$picked] : []);

        /* ensembles triés pour comparaison */
        $goodLabels  = array_column(array_filter($defs, fn($c) => !empty($c['correct'])), 'label');
        sort($goodLabels);
        $pickedUniq  = array_values(array_unique($picked));
        sort($pickedUniq);

        $ok          = ($goodLabels === $pickedUniq);                     // ✓ ou ✗
    ?>
        <p><strong><?= htmlspecialchars($row['texte_question']) ?></strong></p>

        <table border="1" cellpadding="4" cellspacing="0">
            <tr>
                <th>Réponse correcte</th>
                <th>Votre réponse</th>
                <th>✓ / ✗</th>
            </tr>
            <tr>
                <td>
                    <?php foreach ($defs as $c)
                        if (!empty($c['correct']))
                            echo $c['label'] . ') ' . htmlspecialchars($c['texte']) . '<br>'; ?>
                </td>
                <td>
                    <?php
                    if (!$picked) {
                        echo '—';
                    } else
                        foreach ($defs as $c)
                            if (in_array($c['label'], $picked))
                                echo $c['label'] . ') ' . htmlspecialchars($c['texte']) . '<br>';
                    ?>
                </td>
                <td style="text-align:center;font-size:24px"><?= $ok ? '✅' : '❌' ?></td>
            </tr>
        </table>
        <hr>
    <?php endforeach; ?>

    <p><a href="index.php?page=eleve/qcm_result">⬅ Retour à l’historique</a></p>
</body>

</html>