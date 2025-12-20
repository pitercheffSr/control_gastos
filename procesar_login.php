<?php
/**
 * ------------------------------------------------------------
 * procesar_login.php
 * ------------------------------------------------------------
 * Procesa el login de usuario.
 *
 * Migrado a PDO para unificar el sistema de conexión
 * y eliminar dependencias de mysqli / root.
 *
 * Usa:
 *   - db.php  → conexión PDO ($pdo / $conn)
 *   - password_verify() para validar contraseñas
 *
 * ------------------------------------------------------------
 */

// Iniciar sesión (solo aquí)
session_start();

// Conexión PDO
require_once __DIR__ . '/db.php';

// Configuración general (cookies, sesión, etc.)
require_once __DIR__ . '/config.php';

// Procesar solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Obtener datos del formulario
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validación mínima
if ($email === '' || $password === '') {
    $_SESSION['error_login'] = 'Datos de acceso incorrectos.';
    header('Location: login.php');
    exit;
}

try {
    // Consulta PDO preparada
    $sql = "
        SELECT id, nombre, password
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $usuario = $stmt->fetch();

    // Verificar usuario y contraseña
    if ($usuario && password_verify($password, $usuario['password'])) {

        // Login correcto → crear sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];

        header('Location: dashboard.php');
        exit;
    }

} catch (PDOException $e) {
    // En producción no mostramos detalles
    // En desarrollo podrías loguear el error
}

// Si llegamos aquí → login incorrecto
$_SESSION['error_login'] = 'Datos de acceso incorrectos.';
header('Location: login.php');
exit;
