<?php

/**  Ajoute un sous-thème et renvoie {success:true,id,nom}.
 *   Paramètres POST : theme_id, nom
 */
session_start();
header('Content-Type: application/json');

/* — Sécurité minimale — */
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    echo json_encode(['success' => false, 'error' => 'unauth']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$themeId = (int)($_POST['theme_id'] ?? 0);
$nom     = trim($_POST['nom'] ?? '');

if ($themeId === 0 || $nom === '') {
    echo json_encode(['success'=>false,'error'=>'data']); 
    exit;
}

/* Unicité : pas deux sous-thèmes du même nom dans un thème */
$dup = $pdo->prepare('SELECT COUNT(*) FROM subthemes WHERE theme_id = :t AND nom = :n');
$dup->execute(['t' => $themeId, 'n' => $nom]);
if ($dup->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'exists']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO subthemes(theme_id, nom) VALUES(:t, :n)');
$stmt->execute(['t' => $themeId, 'n' => $nom]);

echo json_encode([
    'success' => true,
    'id'      => $pdo->lastInsertId(),
    'nom'     => $nom
]);
