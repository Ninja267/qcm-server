<?php
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    header('Location: /qcm-server/public/index.php?page=login');
    exit;
}
?>
<h1>Tableau de bord Professeur</h1>
<ul>
    <li><a href="index.php?page=prof/theme_new">Nouveau thème</a></li> <!-- AJOUT -->
    <li><a href="index.php?page=prof/question_new">Nouvelle question</a></li>
    <li><a href="index.php?page=prof/question_list">Liste des questions</a></li>
    <li><a href="index.php?page=prof/theme_list">Liste des thèmes</a></li>
    <li><a href="index.php?page=prof/qcm_new">Créer un QCM</a></li>
    <li><a href="index.php?page=prof/qcm_results">Résultats QCM</a></li>
</ul>