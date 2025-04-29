<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
try {
    $dsn = 'mysql:host=' . $_ENV['DB_HOST'] .
        ';dbname='   . $_ENV['DB_NAME'] .
        ';charset=utf8mb4';
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    if ($_ENV['APP_ENV'] === 'dev') {
        exit('Erreur connexion DB : ' . $e->getMessage());
    }
    exit('Erreur serveur.');
}
