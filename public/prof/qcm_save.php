<?php

/**********************************************************************
 *  prof/qcm_save.php
 *  — Appelé en AJAX depuis qcm_list.php pour
 *      • changer la visibilité (visible 0|1)
 *      • changer le nombre de tentatives max   (1‑10)
 *  — Réponse JSON : { ok:true }   ou   { ok:false, err:"..." }
 **********************************************************************/

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
file_put_contents('/tmp/debug_qcm_save.log', json_encode($_POST), FILE_APPEND);

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

/* ---------- Sécurité : uniquement connecté en tant que prof ---------- */
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'err' => 'no_user']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

/* ---------- Lecture & validation des paramètres ---------- */
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$vis  = isset($_POST['v'])  ? (int)$_POST['v']  : 0;
$tent = isset($_POST['t'])  ? (int)$_POST['t']  : 1;

$vis  = $vis ? 1 : 0;                         // force 0 ou 1
$tent = max(1, min(10, $tent));               // borne 1‑10

if ($id <= 0) {
    http_response_code(400);                  // Bad Request
    echo json_encode(['ok' => false, 'err' => 'param']);
    exit;
}

/* ---------- Mise à jour en base ---------- */
$upd = $pdo->prepare(
    'UPDATE qcms
        SET visible       = :v,
            tentative_max = :t
        WHERE id        = :i
        AND auteur_id = :p'
);
try {
    $upd->execute([
        'v' => $vis,
        't' => $tent,
        'i' => $id,
        'p' => $_SESSION['user_id']
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
    exit;
}

if ($upd->rowCount() === 0) {                 // QCM inexistant ou pas à ce prof
    http_response_code(404);                  // Not Found
    echo json_encode(['ok' => false, 'err' => 'not_found']);
    exit;
}

/* ---------- Succès ---------- */
echo json_encode(['ok' => true]);
