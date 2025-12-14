<?php

// api/transacciones/crear.php
session_start();
require_once __DIR__ . '/../../../db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false,'error' => 'No autorizado']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
}

$fecha = $body['fecha'] ?? null;
$monto = $body['monto'] ?? 0;
$tipo  = $body['tipo'] ?? null;
$descripcion = $body['descripcion'] ?? '';
$cat = $body['categoria'] ?? null;
$subcat = $body['subcategoria'] ?? null;
$subsub = $body['subsub'] ?? null;

if (!$fecha || !$monto || !$tipo) {
    echo json_encode(['ok' => false,'error' => 'Datos incompletos']);
    exit;
}

try {
    $sql = "INSERT INTO transacciones
        (id_usuario, fecha, descripcion, monto, tipo, id_categoria, id_subcategoria, id_subsubcategoria)
        VALUES (:uid,:fecha,:descripcion,:monto,:tipo,:cat,:subcat,:subsub)";
    $st = $conn->prepare($sql);
    $st->execute([
        ':uid' => $_SESSION['usuario_id'],
        ':fecha' => $fecha,
        ':descripcion' => $descripcion,
        ':monto' => $monto,
        ':tipo' => $tipo,
        ':cat' => $cat,
        ':subcat' => $subcat,
        ':subsub' => $subsub
    ]);
    echo json_encode(['ok' => true,'id' => $conn->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
