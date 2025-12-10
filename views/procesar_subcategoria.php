<?php

session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_categoria = (int)$_POST['id_categoria'];
    $id_usuario = $_SESSION['usuario_id'];
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

    // Validar que la categoría existe y es fija
    $stmt = $conexion->prepare("SELECT id FROM categorias WHERE id = ? AND id_usuario = 0");
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 0) {
        $_SESSION['error'] = "Categoría no válida";
        header('Location: gestion_conceptos.php');
        exit();
    }

    // Si hay parent_id, validar que exista y pertenezca a la misma categoría
    if ($parent_id) {
        $stmt = $conexion->prepare("SELECT id FROM subcategorias WHERE id = ? AND id_categoria = ? AND id_usuario = ?");
        $stmt->bind_param("iii", $parent_id, $id_categoria, $id_usuario);
        $stmt->execute();
        $res_padre = $stmt->get_result();
        if ($res_padre->num_rows === 0) {
            $_SESSION['error'] = "Subcategoría padre no válida";
            header('Location: gestion_conceptos.php');
            exit();
        }
    }

    // Insertar la nueva subcategoría
    $stmt = $conexion->prepare("INSERT INTO subcategorias (nombre, id_categoria, id_usuario, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $nombre, $id_categoria, $id_usuario, $parent_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Subcategoría creada exitosamente";
    } else {
        $_SESSION['error'] = "Error al crear la subcategoría";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Método no permitido";
}

header('Location: gestion_conceptos.php');
