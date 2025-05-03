<?php
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'eleve') {
    header('Location: /qcm-server/public/index.php?page=login');
    exit;
}
?>
<h1>Tableau de bord Élève</h1>
<p style="text-align:right">
    <a href="logout.php">Déconnexion</a>
</p>
<ul>
    <li><a href="index.php?page=eleve/qcm_list">Examens disponibles</a></li>
    <li><a href="index.php?page=eleve/qcm_result">Mes résultats</a></li>
</ul>