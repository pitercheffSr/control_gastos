<?php
// config.php
// Inicia la sesión de usuario automáticamente en todo el sitio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- CREDENCIALES DE BASE DE DATOS ---
// Cambia esto por tu usuario y contraseña de MySQL (normalmente 'root' y vacío o 'root')
define('DB_HOST', 'localhost');
define('DB_NAME', 'control_gastos');
define('DB_USER', 'admin_gastos'); // <-- PON TU USUARIO AQUÍ (ej: root)
define('DB_PASS', 'Password123!'); // <-- PON TU CONTRASEÑA AQUÍ
try {
    // Conexión PDO segura
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    
    // Configuración de errores: lanza excepciones si algo falla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Por defecto, los datos vienen como array asociativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si falla la conexión, mostramos el error y detenemos todo
    die("❌ Error de conexión a la Base de Datos: " . $e->getMessage());
}

// --- FUNCIONES AYUDANTES ---

// Redirige a otra página y detiene la ejecución
function redirect($url) {
    header("Location: $url");
    exit;
}

// Verifica si el usuario está logueado. Si no, lo manda al login.
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        redirect('index.php');
    }
}


