<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
$page = $_GET['page'] ?? 'home';
$allowed = [
    'home', 'login', 'logout',
    'register', 'register_success', 'register_error',
    'prof/dashboard', 
    'prof/qcm_new', 
    'prof/qcm_list',
    'prof/qcm_delete',
    'prof/qcm_toggle',
    'prof/qcm_results',
    'prof/qcm_save',
    'prof/qcm_copy', 
    'prof/question_new', // création 
    'prof/question_list',     // tableau
    'prof/question_delete',   // suppression
    'prof/theme_list',        //  tableau des thèmes
    'prof/theme_new',         //  créer / modifier / dupliquer
    'prof/theme_delete',
    'eleve/dashboard',
    'eleve/qcm_list',
    'eleve/qcm_view',
    'eleve/qcm_start',
    'eleve/qcm_pass',
    'eleve/qcm_submit', 
    'eleve/qcm_result',
];
if (!in_array($page, $allowed, true)) {
    http_response_code(404);
    exit('404 - Page not found');
}
require $page . '.php';
?>
