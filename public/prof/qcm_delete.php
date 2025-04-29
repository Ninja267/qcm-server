<?php
session_start();
require_once __DIR__.'/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut']!=='prof') {
    header('Location:index.php?page=login'); exit;
}
$id=(int)($_GET['id']??0);
if($id){
   $pdo->prepare('DELETE FROM qcms WHERE id=:i')->execute(['i'=>$id]);
}
header('Location: index.php?page=prof/qcm_list');