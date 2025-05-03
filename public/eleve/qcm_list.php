<?php

/*******************************************************
 *  Liste des QCM disponibles pour l’élève
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$idEleve = $_SESSION['user_id'];

/* QCM visibles + nb tentatives terminées + éventuelle tentative en cours */
$sql = '
SELECT q.id, q.titre, q.date_examen, q.tentative_max,
       COALESCE(fin.done,0) AS finies,
       enc.current_id       AS enCoursId
  FROM qcms q
  /* copies terminées */
  LEFT JOIN (
        SELECT qcm_id, COUNT(*) AS done
          FROM qcm_attempts
         WHERE eleve_id = :e1
           AND finished  = 1
         GROUP BY qcm_id
  ) fin ON fin.qcm_id = q.id
  /* tentative non terminée (0 ou 1) */
  LEFT JOIN (
        SELECT qcm_id, id AS current_id
          FROM qcm_attempts
         WHERE eleve_id = :e2
           AND finished  = 0
  ) enc ON enc.qcm_id = q.id
 WHERE q.visible = 1
 ORDER BY q.date_examen DESC, q.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute(['e1' => $idEleve, 'e2' => $idEleve]);
$qcms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mes examens</title>
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
    <h1>Examens disponibles</h1>

    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Date</th>
                <th>Tentatives restantes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($qcms as $q):
                $rest = $q['tentative_max'] - $q['finies']; ?>
                <tr>
                    <td><?= htmlspecialchars($q['titre']) ?></td>
                    <td><?= $q['date_examen'] ?></td>
                    <td><?= $rest ?></td>
                    <td>
                        <?php if ($q['enCoursId']): ?>
                            <a href="index.php?page=eleve/qcm_pass&id=<?= $q['enCoursId'] ?>">Continuer</a>
                        <?php elseif ($rest > 0): ?>
                            <a href="index.php?page=eleve/qcm_view&id=<?= $q['id'] ?>">Commencer</a>
                        <?php else: ?>
                            épuisé
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>