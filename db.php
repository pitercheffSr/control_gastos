<?php
/**
 * db.php
 * Conexión centralizada mediante PDO.
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza errores como excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Seguridad ante inyecciones SQL
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // En producción, es mejor registrar el error y no mostrarlo
    die("❌ Error de conexión al servidor de base de datos: " . $e->getMessage());
}