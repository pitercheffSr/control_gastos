<?php

session_start();
include '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_categoria = $_GET['id'];
    $id_usuario = $_SESSION['usuario_id'];

    // Asegurarse de que solo se borren las categorías personalizadas
    $sql = "DELETE FROM categorias WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_categoria, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Categoría eliminada con éxito.";
    } else {
        $_SESSION['error'] = "Error al eliminar la categoría. Asegúrate de que no tenga conceptos asociados.";
    }

    $stmt->close();
    $conexion->close();
}
header("Location: gestion_conceptos.php");
exit;
