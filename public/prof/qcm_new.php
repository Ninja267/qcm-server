<?php

/*******************************************************
 *  public/prof/qcm_new.php
 *  Création / édition / duplication d’un QCM
 ******************************************************/
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location:index.php?page=login');
    exit;
}

/* -------- Déterminer le mode -------- */
$mode = 'new';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $pdo->prepare(
        'SELECT * FROM qcms
          WHERE id = :i
            AND auteur_id = :p'
    );
    $stmt->execute(['i' => $id, 'p' => $_SESSION['user_id']]);
    if ($src = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mode = (($_GET['action'] ?? '') === 'dup') ? 'dup' : 'edit';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {         // pré-remplir
            $_POST['titre'] = $src['titre'];
            $_POST['date']  = $src['date_examen'];
            $_POST['duree'] = $src['duree_min'];

            $ids = $pdo->prepare(
                'SELECT question_id FROM qcm_questions
                WHERE qcm_id = :q ORDER BY ordre'
            );
            $ids->execute(['q' => $id]);
            $_POST['ids'] = array_column($ids->fetchAll(), 'question_id');
        }
    } else $mode = 'new';           // id invalide
}

/* -------- Enregistrer -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $date  = $_POST['date'] ?? '';
    $duree = (int)($_POST['duree'] ?? 0);
    $ids   = json_decode($_POST['ids'] ?? '[]', true);
    $tent = (int)($_POST['tent'] ?? 1);

    if ($titre === '' || !$date || $duree <= 0 || !is_array($ids) || !count($ids)) {
        $err = 'Nom, date, durée et au moins 1 question sont requis.';
    } else {
        if ($mode === 'edit') {
            $pdo->prepare(
                'UPDATE qcms
                   SET titre=:t,date_examen=:d,duree_min=:m,tentative_max=:x
                 WHERE id=:i'
            )
                ->execute(['t' => $titre, 'd' => $date, 'm' => $duree, 'x' => $tent, 'i' => $id]);
            $qcmId = $id;
        } else {
            $pdo->prepare(
                'INSERT INTO qcms(titre,date_examen,duree_min,tentative_max,auteur_id)
                VALUES(:t,:d,:m,:x,:a)'
            )
                ->execute(['t' => $titre, 'd' => $date, 'm' => $duree, 'x' => $tent, 'a' => $_SESSION['user_id']]);
            $qcmId = $pdo->lastInsertId();  
        }
        /* ré-insérer les questions */
        $ins = $pdo->prepare(
            'INSERT INTO qcm_questions(qcm_id,question_id,ordre)
            VALUES(:q,:id,:o)'
        );
        $o = 1;
        foreach ($ids as $qid) $ins->execute(['q' => $qcmId, 'id' => $qid, 'o' => $o++]);

        header('Location:index.php?page=prof/qcm_list&msg=ok');
        exit;
    }
}

/* -------- Données pour filtres -------- */
$themes = $pdo->query('SELECT id, nom FROM themes ORDER BY nom')->fetchAll();

/* -------- Toutes les questions -------- */
$qs = $pdo->query(
    'SELECT q.id, q.texte_question, q.reponses,
         q.theme_id, q.subtheme_id,
         t.nom AS theme, s.nom AS sub
    FROM questions q
    LEFT JOIN themes    t ON t.id=q.theme_id
    LEFT JOIN subthemes s ON s.id=q.subtheme_id
 ORDER BY q.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta charset="UTF-8">
    <title><?= $mode === 'edit' ? 'Modifier' : 'Nouveau' ?> QCM</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif
        }

        .panel {
            border: 1px solid #ccc;
            padding: 8px;
            margin-bottom: 12px
        }

        .qrow {
            border-bottom: 1px solid #eee;
            padding: 4px 0;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .drag {
            cursor: move;
            margin-right: 6px
        }

        #selected .qrow {
            background: #f9f9f9
        }

        small {
            color: #666;
            margin-left: 4px
        }
    </style>
</head>

<body>
    <h1><?= $mode === 'edit' ? 'Modifier le QCM' : ($mode === 'dup' ? 'Dupliquer le QCM' : 'Créer un QCM') ?></h1>

    <?php if (!empty($err)): ?><p style="color:red"><?= htmlspecialchars($err) ?></p><?php endif; ?>

    <form method="post" onsubmit="return beforeSave();">
        <!-- =========== Méta =========== -->
        <fieldset class="panel">
            <legend>Méta-données</legend>
            <label>Nom du QCM
                <input name="titre" value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required>
            </label><br><br>
            <label>Date de l’examen
                <input type="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required>
            </label><br><br>
            <label>Durée (minutes)
                <input type="number" name="duree" min="1" value="<?= htmlspecialchars($_POST['duree'] ?? '') ?>" required>
            </label><br><br>
            <label>Tentatives autorisées
                <input type="number" name="tent" min="1" max="10"
                    value="<?= htmlspecialchars($_POST['tent'] ?? ($src['tentative_max'] ?? 1)) ?>"
                    required>
            </label>
        </fieldset>

        <div style="display:flex;gap:16px">
            <!-- ====== Feuille d’examen ====== -->
            <div style="flex:1" class="panel">
                <h3>Feuille d’examen <small>(drag-and-drop)</small></h3>
                <div id="selected"></div>
            </div>

            <!-- ====== Liste questions ====== -->
            <div style="flex:2" class="panel">
                <h3>Questions disponibles</h3>
                <label>Thème :
                    <select id="f_theme" onchange="loadSubFilter();applyFilter();">
                        <option value="">-- Tous --</option>
                        <?php foreach ($themes as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nom']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label>Sous-thème :
                    <select id="f_sub" onchange="applyFilter()">
                        <option value="">-- Tous --</option>
                    </select>
                </label>
                <input id="search" placeholder="Recherche…" oninput="applyFilter()">

                <div id="qtable">
                    <?php
                    foreach ($qs as $q) {
                        $ch = json_decode($q['reponses'], true) ?: [];
                        $repr = $good = [];
                        foreach ($ch as $c) {
                            $repr[] = $c['label'] . ') ' . htmlspecialchars($c['texte']);
                            if (!empty($c['correct'])) $good[] = $c['label'];
                        }
                        $choicesStr = implode(' | ', $repr);
                        $goodStr    = implode(', ', $good);

                        echo '<div class="qrow"
          data-id="' . $q['id'] . '"
          data-theme-id="' . $q['theme_id'] . '"
          data-sub-id="' . $q['subtheme_id'] . '"
          data-theme="' . htmlspecialchars($q['theme']) . '"
          data-sub="' . htmlspecialchars($q['sub'] ?? '') . '"
          data-choices="' . htmlspecialchars($choicesStr) . '"
          data-good="' . htmlspecialchars($goodStr) . '">
          <span class="qtxt">' . htmlspecialchars(mb_strimwidth($q['texte_question'], 0, 60, '…')) . '</span>
          <small>(' . htmlspecialchars($q['theme']) . ' › ' . ($q['sub'] ?: '-') . ')</small><br>
          <small>' . $choicesStr . ' — <strong>' . $goodStr . '</strong></small>
          <button type="button" onclick="addQ(' . $q['id'] . ',this)">➕</button>
        </div>';
                    }
                    ?>
                </div><!-- qtable -->
            </div><!-- liste -->
        </div><!-- flex -->

        <input type="hidden" name="ids" id="ids">
        <br><button type="submit"><?= $mode === 'edit' ? 'Mettre à jour' : 'Enregistrer le QCM' ?></button>
    </form>

    <script>
        /* --- Sous-thème filtrable --- */
        function loadSubFilter() {
            const tid = document.getElementById('f_theme').value;
            const sel = document.getElementById('f_sub');
            sel.innerHTML = '<option value="">-- Tous --</option>';
            if (!tid) {
                applyFilter();
                return;
            }
            fetch('prof/ajax_get_subthemes.php?theme_id=' + tid)
                .then(r => r.json())
                .then(list => {
                    list.forEach(s => sel.insertAdjacentHTML('beforeend',
                        `<option value="${s.id}">${s.nom}</option>`));
                    applyFilter();
                });
        }

        /* --- Ajouter / retirer --- */
        function addQ(id, btn) {
            const row = btn.parentNode;
            const theme = row.dataset.theme;
            const sub = row.dataset.sub || '-';
            const txt = row.querySelector('.qtxt').textContent;
            const choices = row.dataset.choices;
            const good = row.dataset.good;

            document.getElementById('selected').insertAdjacentHTML('beforeend',
                `<div class="qrow" data-id="${id}">
      <span class="drag">⇅</span>
      <span>${txt}<small> (${theme} › ${sub}) • ${choices} — <strong>${good}</strong></small></span>
      <button type="button" onclick="removeQ(${id})">×</button>
    </div>`);
            row.dataset.picked = 'yes';
            row.style.display = 'none';
            refreshSortable();
        }

        function removeQ(id) {
            document.querySelector('#selected .qrow[data-id="' + id + '"]').remove();
            const src = document.querySelector('#qtable .qrow[data-id="' + id + '"]');
            src.style.display = '';
            delete src.dataset.picked;
        }

        /* --- Drag --- */
        let sortable;

        function refreshSortable() {
            if (sortable) sortable.destroy();
            sortable = new Sortable(document.getElementById('selected'), {
                animation: 150,
                handle: '.drag'
            });
        }
        refreshSortable();

        /* --- Filtre --- */
        function applyFilter() {
            const th = document.getElementById('f_theme').value;
            const su = document.getElementById('f_sub').value;
            const kw = document.getElementById('search').value.toLowerCase();

            document.querySelectorAll('#qtable .qrow').forEach(r => {
                if (r.dataset.picked === 'yes') return;
                const txt = r.querySelector('.qtxt').textContent.toLowerCase();
                const ok = (!th || r.dataset.themeId === th) &&
                    (!su || r.dataset.subId === su) &&
                    (!kw || txt.includes(kw));
                r.style.display = ok ? '' : 'none';
            });
        }

        /* --- Pré-chargement (edit / dup) --- */
        const preIds = <?= json_encode($_POST['ids'] ?? []) ?>; // tableau
        if (Array.isArray(preIds) && preIds.length) {
            preIds.forEach(id => {
                const btn = document.querySelector('#qtable .qrow[data-id="' + id + '"] button');
                if (btn) addQ(id, btn);
            });
        }

        /* --- Avant submit --- */
        function beforeSave() {
            const ids = [];
            document.querySelectorAll('#selected .qrow')
                .forEach(r => ids.push(parseInt(r.dataset.id)));
            if (ids.length === 0) {
                alert('Ajoutez au moins une question');
                return false;
            }
            document.getElementById('ids').value = JSON.stringify(ids);
            return true;
        }
    </script>
</body>

</html>