<?php

/** supprime un sous-thème – réponse JSON */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    echo json_encode(['success' => false, 'error' => 'unauth']);
    exit;
}
require_once __DIR__ . '/../../config/db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $pdo->prepare('DELETE FROM subthemes WHERE id=:i')->execute(['i' => $id]);
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'id']);
