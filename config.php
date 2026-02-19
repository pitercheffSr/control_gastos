<?php
/**
 * Configuración Global y Conexión a la Base de Datos
 */

// 1. Configuración de errores (Desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Parámetros de conexión
$host    = 'localhost';
$db_name = 'control_gastos'; // <-- ASEGÚRATE DE QUE ESTE NOMBRE SEA CORRECTO
$user    = 'admin_gastos';               // Tu usuario de MySQL
$pass    = 'Password123!';               // Tu contraseña de MySQL
$charset = 'utf8mb4';
// 3. Opciones de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    // Creamos la conexión global $pdo
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // También creamos $db por si algunos de tus controladores viejos usan ese nombre
    $db = $pdo; 

} catch (\PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// 4. Funciones auxiliares globales
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// 5. Asegurar inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}