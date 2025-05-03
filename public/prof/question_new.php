<?php

/*******************************************************
 *  public/prof/question_new.php
 *  CRUD question avec Thème + Sous-thème dynamique
 ******************************************************/
require_once __DIR__ . '/../../config/db.php';

/* ---- sécurité ---- */
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
  header('Location: index.php?page=login');
  exit;
}

/* ---- déterminer mode : new | edit | dup ---- */
$mode = 'new';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$src  = null;

// récupérer une question par son id
if ($id > 0) {
  $st = $pdo->prepare(
    'SELECT * FROM questions
      WHERE id = :i
        AND auteur_id = :p'
  );
  $st->execute(['i' => $id, 'p' => $_SESSION['user_id']]);
  // si oui, la stocker 
  $src = $st->fetch();

  // si oui, déterminer le mode
  if ($src) {
    // mode : edit ou dup
    $mode = (($_GET['action'] ?? '') === 'dup') ? 'dup' : 'edit';

    /* pré-remplir lors du 1er affichage */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $_POST['theme_id']      = $src['theme_id'];
      $_POST['subtheme_id']   = $src['subtheme_id'];
      $_POST['texte_question'] = $src['texte_question'];
      $_POST['reponses_json'] = $src['reponses'];
    }
  }
}

/* ---- validation / enregistrement ---- */
$error = '';
// si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // récupérer les données du formulaire
  $theme_id = (int)($_POST['theme_id'] ?? 0);
  $sub_id   = (int)($_POST['subtheme_id'] ?? 0);
  $texte    = trim($_POST['texte_question'] ?? '');
  $jsonStr  = $_POST['reponses_json'] ?? '[]';
  $reps     = json_decode($jsonStr, true);
  $data = [
    't'  => $theme_id,
    's'  => $sub_id ?: null,
    'm'  => empty($_POST['is_multiple']) ? 0 : 1,
    'q'  => $texte,
    'r'  => json_encode($reps, JSON_UNESCAPED_UNICODE)
  ];

  // vérifier les réponses
  $good = array_reduce($reps ?: [], fn($c, $r) => $c + (!empty($r['correct']) ? 1 : 0), 0);

  // valider les données
  // thème, énoncé, au moins 2 choix et au moins 1 bonne réponse
  if (!$theme_id || $texte === '' || !is_array($reps) || count($reps) < 2 || $good === 0) {
    $error = '❌ Veuillez choisir un thème, écrire l’énoncé, proposer au moins 2 choix et cocher une bonne réponse.';
  } else {
    if ($mode === 'edit') {
      $data['i'] = $id;
      $pdo->prepare(
        'UPDATE questions
                SET theme_id=:t, subtheme_id=:s, is_multiple=:m,
                    texte_question=:q, reponses=:r
              WHERE id=:i'
      )->execute($data);
    } else {
      $data['a'] = $_SESSION['user_id'];
      $pdo->prepare(
        'INSERT INTO questions(theme_id,subtheme_id,auteur_id,is_multiple,texte_question,reponses)
                  VALUES(:t,:s,:a,:m,:q,:r)'
      )->execute($data);
    }
    header('Location: index.php?page=prof/question_list');
    exit;
  }
}

/* ---- données pour le formulaire ---- */
$themes = $pdo->query('SELECT id, nom FROM themes ORDER BY nom')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title><?= $mode === 'edit' ? 'Modifier' : 'Nouvelle' ?> question</title>
  <style>
    .choice {
      margin-bottom: 4px
    }

    .choice input[type=text] {
      width: 60%
    }
  </style>
</head>

<body>
  <h1><?= $mode === 'edit' ? 'Modifier la question' : ($mode === 'dup' ? 'Dupliquer la question' : 'Nouvelle question') ?></h1>

  <?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form id="qForm" method="post" onsubmit="return beforeSubmit();">

    <!-- thème -->
    <label>Thème
      <select name="theme_id" id="theme" required onchange="loadSub(this.value)">
        <option value="">-- Choisir --</option>
        <?php foreach ($themes as $t): ?>
          <option value="<?= $t['id'] ?>"
            <?= (($_POST['theme_id'] ?? 0) == $t['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nom']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </label>
    <br><br>

    <!-- sous-thème -->
    <label>Sous-thème
      <select name="subtheme_id" id="subtheme">
        <option value="">-- Aucun --</option>
      </select>
      <button type="button" onclick="addSubtheme()">➕</button>
    </label>
    <br><br>

    <!-- énoncé -->
    <label>Énoncé<br>
      <textarea name="texte_question" rows="4" cols="80" required><?= htmlspecialchars($_POST['texte_question'] ?? '') ?></textarea>
    </label>
    <br><br>

    <!-- choix -->
    <fieldset id="choices">
      <legend>Choix</legend>
    </fieldset>
    <button type="button" onclick="addChoice()">+ Choix</button>
    <br><br>

    <!-- type de question -->
    <label>
      <input type="checkbox" name="is_multiple"
        <?= !empty($_POST['is_multiple']) ? 'checked' : '' ?>>
      Plusieurs réponses possibles
    </label>
    <br><br>

    <input type="hidden" name="reponses_json" id="reponses_json">
    <button type="submit"><?= $mode === 'edit' ? 'Mettre à jour' : 'Enregistrer' ?></button>
  </form>

  <p><a href="index.php?page=prof/question_list">⬅ Retour liste</a></p>

  <script>
    /* ---------- sous-thèmes dynamiques ---------- */
    function loadSub(themeId) {
      const st = document.getElementById('subtheme');
      st.innerHTML = '<option value="">-- Aucun --</option>';
      if (!themeId) return;

      fetch('prof/ajax_get_subthemes.php?theme_id=' + themeId)
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(list => {
          list.forEach(o => {
            st.insertAdjacentHTML('beforeend',
              `<option value="${o.id}">${o.nom}</option>`);
          });
          const sel = '<?= $_POST['subtheme_id'] ?? '' ?>';
          if (sel) st.value = sel;
        })
        .catch(() => alert('Erreur réseau ou JSON'));
    }

    function addSubtheme() {
      const th = document.getElementById('theme').value;
      if (!th) {
        alert('Choisissez d’abord un thème.');
        return;
      }
      const nom = prompt('Nom du sous-thème ?');
      if (!nom) return;

      const fd = new FormData();
      fd.append('theme_id', th);
      fd.append('nom', nom);

      fetch('prof/ajax_add_subtheme.php', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(j => {
          if (j.success) {
            loadSub(th);
            setTimeout(() => {
              document.getElementById('subtheme').value = j.id;
            }, 300);
          } else {
            alert(j.error || 'Erreur');
          }
        })
        .catch(() => alert('Erreur réseau'));
    }

    /* ---------- gestion des choix dynamiques ---------- */
    let nextLabel = 'A';

    function addChoice(text = '', correct = false) {
      const wrap = document.createElement('div');
      wrap.className = 'choice';

      const lab = document.createElement('span');
      lab.textContent = nextLabel + ') ';

      const inp = document.createElement('input');
      inp.type = 'text';
      inp.value = text;

      const chk = document.createElement('input');
      chk.type = 'checkbox';
      chk.checked = correct;
      chk.title = 'Bonne réponse';

      const del = document.createElement('button');
      del.type = 'button';
      del.textContent = '×';
      del.onclick = () => {
        wrap.remove();
        renumber();
      };

      wrap.append(lab, inp, chk, del);
      document.getElementById('choices').append(wrap);

      increment();
    }

    function increment() {
      nextLabel = String.fromCharCode(nextLabel.charCodeAt(0) + 1);
    }

    function renumber() {
      nextLabel = 'A';
      document.querySelectorAll('#choices .choice').forEach(c => {
        c.querySelector('span').textContent = nextLabel + ') ';
        increment();
      });
    }

    function beforeSubmit() {
      const arr = [];
      nextLabel = 'A';
      document.querySelectorAll('#choices .choice').forEach(c => {
        const txt = c.querySelector('input[type=text]').value.trim();
        const ok = c.querySelector('input[type=checkbox]').checked;
        if (txt !== '') arr.push({
          label: nextLabel,
          texte: txt,
          correct: ok
        });
        increment();
      });
      document.getElementById('reponses_json').value = JSON.stringify(arr);
      return true;
    }

    /* ---------- reconstruction à l’ouverture ---------- */
    loadSub(document.getElementById('theme').value);

    const savedChoices = JSON.parse(
      <?= json_encode($_POST['reponses_json'] ?? '[]') ?>
    );

    if (Array.isArray(savedChoices) && savedChoices.length) {
      savedChoices.forEach(c => addChoice(c.texte, c.correct));
    } else {
      addChoice();
      addChoice();
    }
  </script>
</body>

</html>