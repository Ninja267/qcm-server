<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__.'/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['statut']!=='prof') {
    echo json_encode(['success'=>false]); exit;
}

$id = (int)($_GET['id'] ?? 0);
$v  = (int)($_GET['v']  ?? 0);
$pdo->prepare('UPDATE qcms SET visible = :v WHERE id = :i')
    ->execute(['v'=>$v,'i'=>$id]);
echo json_encode(['success'=>true]);