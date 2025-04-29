<?php

/**  Renvoie en JSON la liste des sous-thèmes d’un thème donné.
 *   Paramètre GET : theme_id
 *   Réponse : [ { id: 3, nom: "Leçon 1" }, ... ]
 */
session_start();
header('Content-Type: application/json');

/* — Sécurité minimale — */
if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    echo json_encode(['success' => false, 'error' => 'unauth']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$themeId = (int)($_GET['theme_id'] ?? 0);
if ($themeId === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, nom FROM subthemes WHERE theme_id = :t ORDER BY nom');
$stmt->execute(['t' => $themeId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
