<?php
session_start();
include '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['usuario_id'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $clasificacion = $_POST['clasificacion'];

    $sql = "INSERT INTO categorias (id_usuario, nombre, tipo, clasificacion) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isss", $id_usuario, $nombre, $tipo, $clasificacion);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Categoría guardada con éxito.";
    } else {
        $_SESSION['error'] = "Error al guardar la categoría.";
    }

    $stmt->close();
    $conexion->close();
}
header("Location: gestion_conceptos.php");
exit;
?>
