<?php

/*******************************************************
 *  public/prof/theme_list.php
 *  Liste des thèmes : Modifier · Dupliquer · Supprimer
 ******************************************************/
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location:index.php?page=login');
    exit;
}

$themes = $pdo->query('SELECT id, nom FROM themes ORDER BY nom')->fetchAll();

/* sous-thèmes groupés */
$subsBy = [];
foreach ($pdo->query('SELECT theme_id, nom FROM subthemes') as $s) {
    $subsBy[$s['theme_id']][] = $s['nom'];
}
?>
<?php if (isset($_GET['err']) && $_GET['err'] === 'used'): ?>
    <p style="color:red">Impossible : des questions utilisent encore ce thème.</p>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'del_ok'): ?>
    <p style="color:green">Thème supprimé.</p>
<?php endif; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Thèmes</title>
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
    <h1>Thèmes</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Sous-thèmes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($themes as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><?= htmlspecialchars($t['nom']) ?></td>
                    <td><?= htmlspecialchars(implode(' · ', $subsBy[$t['id']] ?? [])) ?></td>
                    <td>
                        <a href="index.php?page=prof/theme_new&id=<?= $t['id'] ?>">Modif.</a> |
                        <a href="index.php?page=prof/theme_new&action=dup&id=<?= $t['id'] ?>">Dup.</a> |
                        <a href="index.php?page=prof/theme_delete&id=<?= $t['id'] ?>"
                            onclick="return confirm('Supprimer ce thème et ses sous-thèmes ?')">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <p>
        <a href="index.php?page=prof/theme_new">➕ Nouveau thème</a> |
        <a href="index.php?page=prof/dashboard">⬅ Tableau de bord</a>
    </p>
</body>

</html>