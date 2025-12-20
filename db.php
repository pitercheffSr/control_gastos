<?php

// db.php — Conexión PDO correcta para tu sistema

$DB_HOST = 'localhost';
$DB_NAME = 'bd_503020';
$DB_USER = 'cg_user';
$DB_PASS = 'cg_pass';

$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

try {
    $conn = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    if (isset($_GET['debug'])) {
        die(json_encode(['db_error' => $e->getMessage()]));
    }
    die("Error de conexión a la base de datos.");
}

$pdo = $conn;   //  ← ← ← AÑADIR ESTA LÍNEA
