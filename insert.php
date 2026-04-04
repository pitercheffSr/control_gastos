<?php

// insert.php - insertar nueva transacción (POST)
session_start();
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');

// Asegurar usuario
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "No autorizado. Inicia sesión.";
    exit;
}

$id_usuario = intval($_SESSION['usuario_id']);

// Recibir datos del formulario
$fecha = $_POST['fecha'] ?? '';
$monto = $_POST['monto'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$id_categoria = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;

// Validaciones básicas
if (!$fecha || !$monto || !$tipo) {
    $_SESSION['error'] = "Faltan datos obligatorios.";
    header("Location: dashboard.php");
    exit;
}

try {
    $sql = "INSERT INTO transacciones 
            (id_usuario, fecha, monto, tipo, descripcion, id_categoria)
            VALUES 
            (:id_usuario, :fecha, :monto, :tipo, :descripcion, :id_categoria)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':id_usuario' => $id_usuario,
        ':fecha' => $fecha,
        ':monto' => floatval($monto),
        ':tipo' => $tipo,
        ':descripcion' => $descripcion,
        ':id_categoria' => $id_categoria
    ]);

    $_SESSION['success'] = "Transacción guardada.";
    header('Location: dashboard.php');
    exit;
} catch (Exception $e) {
    error_log("insert.php error: " . $e->getMessage());
    $_SESSION['error'] = "Error guardando transacción.";
    header('Location: dashboard.php');
    exit;
}
