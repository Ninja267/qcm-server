<?php

/*******************************************************
 *  public/prof/theme_new.php
 *  CRUD thème + gestion sous-thèmes
 ******************************************************/
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location: index.php?page=login');
    exit;
}

/* ----- mode : new / edit / dup ----- */
$mode = 'new';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$src  = null;

if ($id) {
    /* récupérer un thème par son id */
    $stmt = $pdo->prepare('SELECT * FROM themes WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $src  = $stmt->fetch(PDO::FETCH_ASSOC);   // ou fetch() tout court
    if ($src) {
        $mode = (($_GET['action'] ?? '') === 'dup') ? 'dup' : 'edit';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_POST['nom'] = $src['nom'];
        }
    }
}

$error = '';

/* ----- formulaire « Nom du thème » soumis ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    $nom = trim($_POST['nom']);
    if ($nom === '') {
        $error = 'Nom obligatoire.';
    } else {
        $dup = $pdo->prepare(
            'SELECT COUNT(*) FROM themes WHERE nom = :n AND id <> :i'
        );
        $dup->execute(['n' => $nom, 'i' => ($mode === 'edit' ? $id : 0)]);
        if ($dup->fetchColumn() > 0) {
            $error = 'Ce thème existe déjà.';
        } else {
            if ($mode === 'edit') {
                $pdo->prepare('UPDATE themes SET nom = :n WHERE id = :i')
                    ->execute(['n' => $nom, 'i' => $id]);
                $currentId = $id;
            } else {
                $pdo->prepare('INSERT INTO themes(nom) VALUES(:n)')
                    ->execute(['n' => $nom]);
                $currentId = $pdo->lastInsertId();

                if ($mode === 'dup' && $src) {                 // copier sous-thèmes
                    $subs = $pdo->prepare(
                        'INSERT INTO subthemes(theme_id,nom)
                         SELECT :new, nom FROM subthemes WHERE theme_id = :old'
                    );
                    $subs->execute(['new' => $currentId, 'old' => $src['id']]);
                }
            }
            /* rester sur la page pour gérer les sous-thèmes */
            header('Location: index.php?page=prof/theme_new&id=' . $currentId);
            exit;
        }
    }
}

/* ----- liste des sous-thèmes ----- */
$subthemes = [];
if (($mode === 'edit') || ($mode === 'dup' && $src)) {
    $themeIdForSubs = $mode === 'edit' ? $id : ($src['id'] ?? 0);
    $q = $pdo->prepare(
        'SELECT id, nom FROM subthemes WHERE theme_id = :t ORDER BY nom'
    );
    $q->execute(['t' => $themeIdForSubs]);
    $subthemes = $q->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title><?= $mode === 'edit' ? 'Modifier' : 'Nouveau' ?> thème</title>
    <style>
        ul.st {
            list-style: none;
            padding: 0
        }

        ul.st li {
            margin-bottom: 4px
        }
    </style>
</head>

<body>

    <h1><?= $mode === 'edit' ? 'Modifier le thème' : ($mode === 'dup' ? 'Dupliquer le thème' : 'Nouveau thème') ?></h1>

    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- formulaire principal -->
    <form method="post">
        <label>Nom du thème
            <input name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
        </label>
        <button type="submit"><?= $mode === 'edit' ? 'Mettre à jour' : 'Enregistrer' ?></button>
    </form>

    <?php if ($mode !== 'new'): ?>
        <hr>
        <h2>Sous-thèmes</h2>

        <ul class="st" id="subs">
            <?php foreach ($subthemes as $s): ?>
                <li id="sub-<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['nom']) ?>
                    <button type="button" onclick="delSubtheme(<?= $s['id'] ?>)">×</button>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- formulaire AJAX ajout sous-thème -->
        <form onsubmit="return addSubtheme();">
            <input type="hidden" id="theme_id" value="<?= $mode === 'edit' ? $id : ($src['id'] ?? 0) ?>">
            <input id="sub_nom" placeholder="Nom du sous-thème" required>
            <button type="submit">➕ Ajouter</button>
        </form>

        <script>
            function addSubtheme() {
                const tid = document.getElementById('theme_id').value;
                const nom = document.getElementById('sub_nom').value.trim();
                if (!tid) {
                    alert('Sauvegardez d’abord le thème.');
                    return false;
                }
                if (!nom) return false;
                const fd = new FormData();
                fd.append('theme_id', tid);
                fd.append('nom', nom);
                fetch('prof/ajax_add_subtheme.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(j => {
                        if (j.success) {
                            const ul = document.getElementById('subs');
                            ul.insertAdjacentHTML('beforeend',
                                `<li id="sub-${j.id}">${j.nom}
               <button type="button" onclick="delSubtheme(${j.id})">×</button></li>`);
                            document.getElementById('sub_nom').value = '';
                        } else alert(j.error || 'Erreur');
                    });
                return false;
            }

            function delSubtheme(id) {
                if (!confirm('Supprimer ce sous-thème ?')) return;
                fetch('prof/ajax_delete_subtheme.php?id=' + id)
                    .then(r => r.json())
                    .then(j => {
                        if (j.success) document.getElementById('sub-' + id).remove();
                        else alert(j.error || 'Erreur');
                    });
            }
        </script>
    <?php endif; ?>

    <p><a href="index.php?page=prof/theme_list">⬅ Retour à la liste</a></p>
</body>

</html>