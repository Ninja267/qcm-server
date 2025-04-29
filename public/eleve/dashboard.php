<?php
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'eleve') {
    header('Location: /qcm-server/public/index.php?page=login');
    exit;
}
?>
<h1>Tableau de bord Élève</h1>
<p>Liste des QCM disponibles prochainement…</p>
