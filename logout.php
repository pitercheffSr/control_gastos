<?php
/**
 * Finalización de sesión segura
 * Ruta: /var/www/html/control_gastos/logout.php
 */

// Iniciar sesión si no lo está para poder destruirla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la sesión completamente, borramos también la cookie de sesión.
// Nota: Esto destruye la sesión, no solo los datos de la sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruir la sesión
session_destroy();

// 4. Redirigir al usuario al login (index.php)
header("Location: index.php");
exit;