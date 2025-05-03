<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    echo json_encode(['ok' => false]);
    exit;
}
$id = (int)($_GET['id'] ?? 0);
$val = max(1, min(10, (int)$_GET['v']));      // borne 1‑10
$pdo->prepare('UPDATE qcms SET tentative_max=:v WHERE id=:i')
    ->execute(['v' => $val, 'i' => $id]);
echo json_encode(['ok' => true]);
