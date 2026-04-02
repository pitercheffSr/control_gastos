<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluye el archivo de conexión a la base de datos
include_once 'includes/conexion.php';
// Incluye el archivo de conexión a la base de datos
include_once 'includes/conexion.php';

// Inicia una sesión para manejar mensajes de estado
session_start();

// Comprueba si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoge los datos del formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validación básica de los datos
    if (empty($nombre) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: views/registro.php");
        exit;
    }

    // Hashear la contraseña para mayor seguridad
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepara la consulta SQL para evitar inyección SQL
    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);

    // Enlaza los parámetros
    $stmt->bind_param("sss", $nombre, $email, $hashed_password);

    // Ejecuta la consulta
    if ($stmt->execute()) {
        $_SESSION['success'] = "¡Registro exitoso! Ya puedes iniciar sesión.";
        header("Location: index.php");
        exit;
    } else {
        // Maneja el caso de error en la ejecución
        $_SESSION['error'] = "Error al registrar el usuario: " . $stmt->error;
        header("Location: views/registro.php");
        exit;
    }

    // Cierra la conexión
    $stmt->close();
    $conexion->close();
} else {
    // Redirige si se intenta acceder directamente a este archivo
    header("Location: views/registro.php");
    exit;
}
