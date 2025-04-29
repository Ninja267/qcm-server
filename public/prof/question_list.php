<?php

/*******************************************************
 *  public/prof/question_list.php
 *  Liste complète des questions (Thème + Sous‑thème),
 *  avec actions Modifier / Dupliquer / Supprimer.
 *  Filtrage facultatif par thème ou sous‑thème.
 ******************************************************/
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location:index.php?page=login');
    exit;
}

/* ----------  filtres optionnels (GET) ---------- */
$themeFilter = isset($_GET['theme_id']) ? (int)$_GET['theme_id'] : 0;
$subFilter   = isset($_GET['sub_id'])   ? (int)$_GET['sub_id']   : 0;

/* Listes déroulantes */
$themes = $pdo->query('SELECT id, nom FROM themes ORDER BY nom')->fetchAll();
$subs   = [];
if ($themeFilter) {
    $stmt = $pdo->prepare('SELECT id, nom FROM subthemes WHERE theme_id=:tid ORDER BY nom');
    $stmt->execute(['tid' => $themeFilter]);
    $subs = $stmt->fetchAll();
}

/* ----------  Requête principale ---------- */
$sql = 'SELECT q.id, q.texte_question, q.reponses,
               t.nom  AS theme,
               s.nom  AS subtheme
          FROM questions q
          LEFT JOIN themes     t ON t.id = q.theme_id
          LEFT JOIN subthemes  s ON s.id = q.subtheme_id
         WHERE 1 ';
$params = [];
if ($themeFilter) {
    $sql .= ' AND q.theme_id = :tf ';
    $params['tf'] = $themeFilter;
}
if ($subFilter) {
    $sql .= ' AND q.subtheme_id = :sf ';
    $params['sf'] = $subFilter;
}
$sql .= 'ORDER BY q.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Questions</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 4px 6px;
            vertical-align: top
        }
    </style>
</head>

<body>

    <h1>Questions</h1>

    <!-- Filtres -->
    <form method="get" action="index.php">
        <input type="hidden" name="page" value="prof/question_list">
        <label>Thème
            <select name="theme_id" onchange="this.form.submit()">
                <option value="0">-- TOUS --</option>
                <?php foreach ($themes as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] === $themeFilter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nom']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </label>

        <?php if ($themeFilter): ?>
            <label>Sous-thème
                <select name="sub_id" onchange="this.form.submit()">
                    <option value="0">-- TOUS --</option>
                    <?php foreach ($subs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] === $subFilter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nom']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label>
        <?php endif ?>
        <noscript><button type="submit">OK</button></noscript>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Thème</th>
                <th>Sous‑thème</th>
                <th>Énoncé</th>
                <th>Réponses</th>
                <th>Bonne(s)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($questions as $q):
                $choices = json_decode($q['reponses'], true) ?: [];
                $repr = [];
                $good = [];
                foreach ($choices as $c) {
                    $repr[] = $c['label'] . ') ' . htmlspecialchars($c['texte']);
                    if (!empty($c['correct'])) $good[] = $c['label'];
                }
            ?>
                <tr>
                    <td><?= $q['id'] ?></td>
                    <td><?= htmlspecialchars($q['theme']) ?></td>
                    <td><?= htmlspecialchars($q['subtheme'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($q['texte_question'], 0, 60, '…')) ?></td>
                    <td><?= implode(' | ', $repr) ?></td>
                    <td style="text-align:center"><?= implode(', ', $good) ?></td>
                    <td>
                        <a href="index.php?page=prof/question_new&id=<?= $q['id'] ?>">Modif.</a> |
                        <a href="index.php?page=prof/question_new&action=dup&id=<?= $q['id'] ?>">Dup.</a> |
                        <a href="index.php?page=prof/question_delete&id=<?= $q['id'] ?>"
                            onclick="return confirm('Supprimer définitivement ?')">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <p>
        <a href="index.php?page=prof/question_new">➕ Nouvelle question</a> |
        <a href="index.php?page=prof/dashboard">⬅ Tableau de bord</a>
    </p>
</body>

</html>