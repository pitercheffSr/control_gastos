<?php

session_start();
include_once 'includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_transaccion = $_GET['id'];
    $id_usuario = $_SESSION['usuario_id'];

    $sql = "DELETE FROM transacciones WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_transaccion, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['success_transaccion'] = "Transacción eliminada con éxito.";
    } else {
        $_SESSION['error_transaccion'] = "Error al eliminar la transacción.";
    }

    $stmt->close();
    $conexion->close();
}
header("Location: dashboard.php");
exit;
