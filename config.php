<?php
/**
 * Configuración Global y Conexión a la Base de Datos
 */

// 1. Configuración de errores
// En fase de desarrollo está bien tenerlo en 1 para ver por qué fallan las cosas.
// Cuando la web sea pública y definitiva, se recomienda ponerlos a 0 por seguridad.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

<<<<<<< HEAD
// 2. Parámetros de conexión a la base de datos
$host    = 'localhost';
$db_name = 'control_gastos';    // Nombre de tu base de datos
$user    = 'admin_gastos';      // Tu usuario de MySQL
$pass    = 'Password123!';      // Tu contraseña de MySQL
$charset = 'utf8mb4';           // Formato de texto (vital para que funcionen bien las ñ y los acentos)

// 3. Opciones de seguridad y formato para PDO
=======
// 2. Parámetros de conexión
$host    = 'localhost'; // Cambia esto si tu base de datos no está en el mismo servidor
$db_name = 'control_gastos'; // <-- ASEGÚRATE DE QUE ESTE NOMBRE SEA CORRECTO
$user    = 'admin_gastos';               // Tu usuario de MySQL
$pass    = 'Password123!';               // Tu contraseña de MySQL
$charset = 'utf8mb4';
// 3. Opciones de PDO
>>>>>>> d212475b210a81035c62b4da7115053514e222ad
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Obliga a mostrar los errores de la BD
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Nos devuelve los datos organizados por el nombre de la columna
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Apaga la emulación para evitar inyecciones de código SQL (Seguridad)
];

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    // Creamos la conexión global $pdo con la base de datos
    $pdo = new PDO($dsn, $user, $pass, $options);
<<<<<<< HEAD
    
    // También creamos una copia llamada $db por si algún archivo antiguo busca ese nombre
    $db = $pdo; 
=======

    // También creamos $db por si algunos de tus controladores viejos usan ese nombre
    $db = $pdo;
>>>>>>> d212475b210a81035c62b4da7115053514e222ad

} catch (\PDOException $e) {
    // Si la contraseña o el usuario fallan, paraliza la web y muestra este mensaje
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// 4. Funciones auxiliares globales
if (!function_exists('redirect')) {
    // Pequeño atajo para redirigir a los usuarios de una página a otra rápidamente
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// 5. GESTIÓN DE SESIONES Y CRONÓMETRO DE SEGURIDAD
// Comprobamos si la sesión ya está abierta, si no, la iniciamos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
<<<<<<< HEAD

// Generamos un token CSRF si no existe en la sesión para proteger contra ataques
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Definimos el tiempo máximo de inactividad (15 minutos = 900 segundos)
$timeout_duration = 900; 

// Verificamos si hay un usuario dentro de la plataforma (logueado)
if (isset($_SESSION['usuario_id'])) {
    
    // Si existe un registro de su último clic, calculamos cuánto tiempo ha pasado
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        
        // ¡Tiempo agotado! (Ha pasado más de 15 minutos sin hacer nada)
        // Destruimos todas sus variables y cerramos su sesión por completo
        session_unset();
        session_destroy();
        
        // Si no está ya en la pantalla de login, lo mandamos allí con un aviso (timeout=1)
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header("Location: login.php?timeout=1");
            exit;
        }
    }
    
    // Si acaba de hacer clic o de cambiar de página, reseteamos el cronómetro a cero
    $_SESSION['last_activity'] = time();
}
?>
=======
>>>>>>> d212475b210a81035c62b4da7115053514e222ad
