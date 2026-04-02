<?php
/**
 * PLANTILLA DE CONFIGURACIÓN
 * Instrucciones: Copia este archivo, renómbralo a 'config.php' 
 * y pon los datos de tu base de datos local.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pon aquí los datos de tu entorno local
$host    = 'localhost';
$db_name = 'nombre_de_tu_bd'; 
$user    = 'root';               
$pass    = '';               
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $db = $pdo; 
} catch (\PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}
?>