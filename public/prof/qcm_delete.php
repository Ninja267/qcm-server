<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['statut'] !== 'prof') {
    echo json_encode(['ok' => false, 'err' => 'auth']);
    exit;
}

$id  = (int)($_POST['id']  ?? 0);
$pwd =       ($_POST['pwd'] ?? '');

/* vérif mot de passe */
$u = $pdo->prepare('SELECT mot_de_passe FROM users WHERE id=:i');
$u->execute(['i' => $_SESSION['user_id']]);
$hash = $u->fetchColumn();
if (!$hash || !password_verify($pwd, $hash)) {
    echo json_encode(['ok' => false, 'err' => 'mdp']);
    exit;
}

/* supprimer UNIQUEMENT un QCM appartenant au prof */
$del = $pdo->prepare('DELETE FROM qcms WHERE id=:q AND auteur_id=:p');
$del->execute(['q' => $id, 'p' => $_SESSION['user_id']]);
echo json_encode(['ok' => true]);
