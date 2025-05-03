<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
$idEleve = $_SESSION['user_id'];
$id = (int)$_GET['id'];
$q = $pdo->prepare('SELECT * FROM qcms WHERE id=:i AND visible=1');
$q->execute(['i' => $id]);
$qcm = $q->fetch();
if (!$qcm) die('Examen indisponible');
/* tentatives restantes */
$fait = $pdo->prepare('SELECT COUNT(*) FROM qcm_attempts WHERE qcm_id=:q AND eleve_id=:e');
$fait->execute(['q' => $id, 'e' => $idEleve]);
$left = $qcm['tentative_max'] - $fait->fetchColumn();
if ($left <= 0) die('Aucune tentative restante');
?>
<h2><?= htmlspecialchars($qcm['titre']) ?></h2>
<p>Date : <?= $qcm['date_examen'] ?> &nbsp;|&nbsp; Durée : <?= $qcm['duree_min'] ?> min</p>
<p>Tentatives restantes : <?= $left ?></p>
<form method="post" action="index.php?page=eleve/qcm_start">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button>Commencer</button>
</form>