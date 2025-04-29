<?php
// public/prof/theme_delete.php
require_once __DIR__ . '/../../config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location:index.php?page=login');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    /* y a-t-il des questions qui pointent vers ce thème ? */
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE theme_id = :t');
    $cnt->execute(['t' => $id]);
    if ($cnt->fetchColumn() == 0) {
        /* OK : suppression -> supprime aussi les sous-thèmes (ON CASCADE) */
        $pdo->prepare('DELETE FROM themes WHERE id = :i')->execute(['i' => $id]);
        header('Location: index.php?page=prof/theme_list&msg=del_ok');
    } else {
        /* blocage : redirige avec message d’erreur */
        header('Location: index.php?page=prof/theme_list&err=used');
    }
    exit;
}
header('Location: index.php?page=prof/theme_list');
