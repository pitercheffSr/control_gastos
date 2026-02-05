<?php
/**
 * config.php
 * Configuración global y credenciales de base de datos.
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la Base de Datos (Linux Mint / MariaDB)
define('DB_HOST', 'localhost');
define('DB_NAME', 'control_gastos');
define('DB_USER', 'admin_gastos');
define('DB_PASS', 'Password123!');
define('DB_CHAR', 'utf8mb4');

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ruta base del proyecto (opcional para redirecciones)
define('BASE_URL', 'http://localhost/control_gastos/');