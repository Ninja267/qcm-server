<?php

/*******************************************************
 *  public/prof/qcm_list.php
 *  Tableau des QCM : dispo, modifier, dupliquer, supprimer
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location:index.php?page=login');
    exit;
}

$qcms = $pdo->query(
    'SELECT q.id, q.titre, q.date_examen, q.duree_min, q.visible,
          (SELECT COUNT(*) FROM qcm_questions WHERE qcm_id = q.id) AS nbq
     FROM qcms q
 ORDER BY q.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>QCM</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 4px 6px
        }

        th {
            text-align: left
        }
    </style>
</head>

<body>
    <h1>Liste des QCM</h1>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
        <p style="color:green">QCM enregistré.</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Date</th>
                <th>Durée</th>
                <th>Questions</th>
                <th>Disponible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($qcms as $q): ?>
                <tr>
                    <td><?= $q['id'] ?></td>
                    <td><?= htmlspecialchars($q['titre']) ?></td>
                    <td><?= $q['date_examen'] ?></td>
                    <td><?= $q['duree_min'] ?> min</td>
                    <td><?= $q['nbq'] ?></td>
                    <td style="text-align:center">
                        <input type="checkbox"
                            <?= $q['visible'] ? 'checked' : '' ?>
                            onchange="toggleVis(<?= $q['id'] ?>, this.checked)">
                    </td>
                    <td>
                        <a href="index.php?page=prof/qcm_new&id=<?= $q['id'] ?>">Modif.</a> |
                        <a href="index.php?page=prof/qcm_new&action=dup&id=<?= $q['id'] ?>">Dup.</a> |
                        <a href="prof/qcm_delete.php?id=<?= $q['id'] ?>"
                            onclick="return confirm('Supprimer ce QCM ?')">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <p>
        <a href="index.php?page=prof/qcm_new">➕ Nouveau QCM</a> |
        <a href="index.php?page=prof/dashboard">⬅ Tableau de bord</a>
    </p>

    <script>
        function toggleVis(id, val) {
            fetch('index.php?page=prof/qcm_toggle&id=' + id + '&v=' + (val ? 1 : 0), {
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(j => {
                    if (!j.success) {
                        alert('Erreur');
                        location.reload();
                    }
                })
                .catch(() => {
                    alert('Erreur réseau');
                    location.reload();
                });
        }
    </script>
</body>

</html>