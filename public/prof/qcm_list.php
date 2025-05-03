<?php

/*******************************************************
 *  public/prof/qcm_list.php   (v3 — fix route ../index.php)
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

if (($_SESSION['statut'] ?? '') !== 'prof') {
    header('Location: ../index.php?page=login');
    exit;
}
$prof = $_SESSION['user_id'];

/* ---- données ---- */
$stmt = $pdo->prepare(
    'SELECT q.id, q.titre, q.date_examen, q.duree_min,
            q.visible, q.tentative_max,
            (SELECT COUNT(*) FROM qcm_questions WHERE qcm_id=q.id) AS nbq
    FROM qcms q
    WHERE q.auteur_id = :p
    ORDER BY q.id DESC'
);
$stmt->execute(['p' => $prof]);
$qcms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mes QCM</title>
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

        .dirty {
            background: #fff9c4
        }
    </style>
</head>

<body>
    <h1>Mes QCM</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Date</th>
                <th>Durée</th>
                <th>Q°</th>
                <th>Dispo.</th>
                <th>Tent. max</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($qcms as $q): ?>
                <tr data-id="<?= $q['id'] ?>">
                    <td><?= $q['id'] ?></td>
                    <td><?= htmlspecialchars($q['titre']) ?></td>
                    <td><?= $q['date_examen'] ?></td>
                    <td><?= $q['duree_min'] ?> min</td>
                    <td><?= $q['nbq'] ?></td>

                    <td style="text-align:center">
                        <input type="checkbox" <?= $q['visible'] ? 'checked' : '' ?> onchange="markDirty(this)">
                    </td>
                    <td>
                        <input type="number" min="1" max="10" style="width:60px"
                            value="<?= $q['tentative_max'] ?>" onchange="markDirty(this)">
                    </td>

                    <td>
                        <a href="index.php?page=prof/qcm_new&id=<?= $q['id'] ?>">Modifier</a> |
                        <a href="index.php?page=prof/qcm_new&action=dup&id=<?= $q['id'] ?>">Dupliquer</a> |
                        <a href="index.php?page=prof/qcm_results&id=<?= $q['id'] ?>">Copies</a> |
                        <button onclick="saveRow(<?= $q['id'] ?>)">Enregistrer</button> |
                        <button onclick="askDelete(<?= $q['id'] ?>)"
                            title="Supprime aussi DÉFINITIVEMENT toutes les copies liées">Supprimer</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        <a href="index.php?page=prof/qcm_new">➕ Nouveau QCM</a> |
        <a href="index.php?page=prof/dashboard">⬅ Tableau de bord</a>
    </p>

    <script>
        /* Préfixe unique vers le routeur racine */
        const BASE = 'index.php?page=';
        

        /* ---- marquer une ligne modifiée ---- */
        function markDirty(el) {
            el.closest('tr').classList.add('dirty');
        }

        /* ---- enregistrement ---- */
        function saveRow(id) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) {
                alert('Ligne introuvable');
                return;
            }
            const visible = row.querySelector('input[type=checkbox]').checked ? 1 : 0;
            const tent = row.querySelector('input[type=number]').value;

            fetch(BASE + 'prof/qcm_save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}&v=${visible}&t=${tent}`
                })
                .then(r => r.json())
                .then(j => {
                    console.log('Réponse AJAX:', j);
                    if (!j.ok) {
                        alert(j.err || 'Erreur serveur');
                        return;
                    }
                    row.classList.remove('dirty');
                    alert('Enregistré !');
                })
                .catch(err => {
                    console.error('Erreur réseau / serveur:', err);
                    alert('Erreur réseau ou serveur : ' + err);
                });
        }

        /* ---- protection navigation ---- */
        window.addEventListener('beforeunload', e => {
            if (document.querySelector('tr.dirty')) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        /* ---- suppression ---- */
        function askDelete(id) {
            const pwd = prompt(
                'ATTENTION ! Cela effacera aussi toutes les copies.\n' +
                'Entrez votre mot de passe pour confirmer :'
            );
            if (!pwd) return;
            fetch(BASE + 'prof/qcm_delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}&pwd=${encodeURIComponent(pwd)}`
                })
                .then(r => r.json())
                .then(j => {
                    if (j.ok) location.reload();
                    else alert(j.err || 'Erreur');
                })
                .catch(() => alert('Erreur réseau'));
        }
    </script>
</body>

</html>