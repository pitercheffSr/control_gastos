<?php
include_once 'includes/conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, nombre, password FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header("Location: dashboard.php"); // Redirige al dashboard
            exit;
        } else {
            $_SESSION['error_login'] = "Contraseña incorrecta.";
            header("Location: views/login.php");
            exit;
        }
    } else {
        $_SESSION['error_login'] = "Correo electrónico no encontrado.";
        header("Location: views/login.php");
        exit;
    }

    $stmt->close();
    $conexion->close();
} else {
    header("Location: views/login.php");
    exit;
}
?>
