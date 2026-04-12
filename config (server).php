<?php
/**
 * =================================================================
 *  ARCHIVO DE CONFIGURACIÓN PARA EL SERVIDOR (PRODUCCIÓN)
 * =================================================================
 * 
 * Este archivo contiene la configuración específica para que tu 
 * aplicación funcione en el servidor de hosting (ej. InfinityFree).
 * 
 * Cuando subas tu proyecto al servidor, debes renombrar este archivo
 * a 'config.php' para que reemplace la configuración local.
 */

// -----------------------------------------------------------------
// 1. CONFIGURACIÓN DE ERRORES EN PRODUCCIÓN
// -----------------------------------------------------------------
// Para un entorno de producción (un sitio web público), es CRUCIAL
// ocultar los errores detallados de PHP y de la base de datos.
// Mostrarlos puede dar pistas a atacantes sobre cómo funciona tu web.
//
// RECOMENDACIÓN: Cambia '1' por '0' para desactivar la visualización de errores.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
// NOTA: Si algo falla en el servidor y no sabes por qué, puedes volver a 
// ponerlos en '1' temporalmente para depurar, pero no olvides volver a 
// ponerlos en '0' cuando termines.

// -----------------------------------------------------------------
// 2. PARÁMETROS DE CONEXIÓN A LA BASE DE DATOS DEL HOSTING
// -----------------------------------------------------------------
// Estos son los datos que te proporciona tu proveedor de hosting
// (en este caso, InfinityFree) para conectar con la base de datos.
// NO son los mismos que usas en tu computadora local (localhost).

// ADVERTENCIA DE SEGURIDAD: Almacenar contraseñas y otros secretos directamente
// en el código fuente es una mala práctica, especialmente si el código está
// en un repositorio (ej. GitHub). Un atacante podría encontrarlo.
// La solución recomendada es usar "variables de entorno" que se configuran
// directamente en el servidor de hosting y se leen en PHP con `getenv('DB_PASSWORD')`.
$host    = getenv('DB_HOST') ?: 'sql108.infinityfree.com';
$db_name = getenv('DB_NAME') ?: 'epiz_34303348_control_gastos';
$user    = getenv('DB_USER') ?: 'epiz_34303348';
$pass    = getenv('DB_PASS') ?: '4lQL2gC4MJAV1'; // Es mejor que falle si no está la variable de entorno.

$charset = 'utf8mb4';                       // El formato de texto. Esencial para 'ñ', tildes y emojis.

// -----------------------------------------------------------------
// 3. CONFIGURACIÓN DE LA CONEXIÓN (PDO)
// -----------------------------------------------------------------
// Data Source Name (DSN): Es la "dirección" completa de la base de datos.
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// Opciones de seguridad y formato para PDO (PHP Data Objects).
$options = [
    // Obliga a PDO a lanzar excepciones (errores) que podemos capturar.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    
    // Pide que los resultados de las consultas vengan como un array asociativo 
    // (ej: $fila['nombre_columna']) en lugar de un array numérico.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Desactiva la emulación de sentencias preparadas. Esto hace que se usen
    // las sentencias preparadas nativas de MySQL, lo que es más seguro
    // contra ataques de inyección SQL. ¡Muy importante para la seguridad!
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Se intenta crear la conexión a la base de datos usando los datos de arriba.
    // Esta variable $pdo será la que usarás en toda tu aplicación para hacer consultas.
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Creamos un alias $db por si algún script antiguo usa ese nombre.
    // Es una buena práctica para mantener la compatibilidad.
    $db = $pdo; 

} catch (\PDOException $e) {
    // Si algo falla en la conexión (ej. contraseña incorrecta, servidor caído),
    // el script se detendrá y mostrará un mensaje genérico.
    // Gracias a que desactivamos 'display_errors', el usuario no verá el error
    // detallado ($e->getMessage()), solo nuestro mensaje personalizado.
    // En un entorno real, podrías registrar el error en un archivo de log.
    // error_log("Error de conexión a BD: " . $e->getMessage()); // <-- Ejemplo de cómo registrarías el error.
    die("Error: No se pudo establecer la conexión con el servidor. Por favor, inténtelo más tarde.");
}

// -----------------------------------------------------------------
// 4. FUNCIONES AUXILIARES GLOBALES
// -----------------------------------------------------------------
if (!function_exists('redirect')) {
    /**
     * Redirige al usuario a una URL específica y detiene la ejecución del script.
     * @param string $url La URL a la que se va a redirigir.
     */
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

// -----------------------------------------------------------------
// 5. GESTIÓN DE SESIONES Y CIERRE POR INACTIVIDAD
// -----------------------------------------------------------------
// Se asegura de que la sesión esté iniciada en todas las páginas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tiempo máximo de inactividad permitido en segundos (900s = 15 minutos).
$timeout_duration = 900; 

// Este bloque solo se ejecuta si el usuario ha iniciado sesión.
if (isset($_SESSION['usuario_id'])) {
    
    // Comprueba si ha pasado más tiempo del permitido desde la última acción.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        
        // Si el tiempo se ha agotado, se destruye la sesión.
        session_unset();     // Limpia todas las variables de sesión.
        session_destroy();   // Destruye la sesión por completo.
        
        // Detectar si la petición es de tipo AJAX o Fetch API
        $isAjax = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401); // 401 Unauthorized
            echo json_encode(['error' => 'Tu sesión ha expirado por inactividad. Actualiza la página.', 'timeout' => true]);
            exit;
        } elseif (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            // Si es navegación normal, redirige a la página de login con el aviso.
            redirect("login.php?timeout=1");
        }
    }
    
    // Si el usuario está activo, actualiza la marca de tiempo de su última actividad.
    // Esto "resetea" el contador de inactividad.
    $_SESSION['last_activity'] = time();
}
?>
