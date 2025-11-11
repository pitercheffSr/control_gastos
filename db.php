<?php
// db.php - Cargar credenciales desde /home/pedro/.env_control_gastos usando vlucas/phpdotenv
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$envPath = '/home/pedro';
$envFile = '.env_control_gastos';

// Carga segura (no lanza excepción si falta .env)
$dotenv = Dotenv::createImmutable($envPath, $envFile);
$dotenv->safeLoad();

$DB_HOST = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
$DB_NAME = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'bd_503020';
$DB_USER = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root';
$DB_PASS = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '';
$DB_CHARSET = $_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $conn = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
    if ($appEnv === 'development' || php_sapi_name() === 'cli') {
        die('Error de conexión: ' . $e->getMessage());
    }
    error_log('DB connection error: ' . $e->getMessage());
    die('Error de conexión a la base de datos.');
}
