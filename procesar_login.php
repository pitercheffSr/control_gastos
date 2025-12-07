<?php
include_once "config.php";
include_once "includes/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, nombre, password FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $usuario = $resultado->fetch_assoc();

        if (password_verify($password, $usuario['password'])) {

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];

            header("Location: dashboard.php");
            exit;
        }
    }

    // Si llegamos aquí: email inválido o contraseña incorrecta
    $_SESSION['error_login'] = "Datos de acceso incorrectos.";
    header("Location: login.php");
    exit;
}
?>
