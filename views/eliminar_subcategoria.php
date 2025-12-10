<?php

session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id_subcategoria = (int)$_GET['id'];
    $id_usuario = $_SESSION['usuario_id'];

    // Verificar que la subcategoría pertenezca al usuario
    $stmt = $conexion->prepare("SELECT id FROM subcategorias WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_subcategoria, $id_usuario);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        // Eliminar la subcategoría
        $stmt = $conexion->prepare("DELETE FROM subcategorias WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_subcategoria, $id_usuario);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Subcategoría eliminada exitosamente";
        } else {
            $_SESSION['error'] = "Error al eliminar la subcategoría";
        }
    } else {
        $_SESSION['error'] = "Subcategoría no encontrada o no tienes permiso para eliminarla";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "ID de subcategoría no proporcionado";
}

header('Location: gestion_conceptos.php');
