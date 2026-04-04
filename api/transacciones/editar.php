<?php

// api/transacciones/editar.php
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

$id = intval($body['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false,'error' => 'ID inválido']);
    exit;
}

$fecha = $body['fecha'] ?? null;
$monto = $body['monto'] ?? 0;
$tipo  = $body['tipo'] ?? null;
$descripcion = $body['descripcion'] ?? '';
$cat = $body['categoria'] ?? null;
$subcat = $body['subcategoria'] ?? null;
$subsub = $body['subsub'] ?? null;

try {
    $sql = "UPDATE transacciones SET fecha=:fecha, descripcion=:descripcion, monto=:monto, tipo=:tipo,
            id_categoria=:cat, id_subcategoria=:subcat, id_subsubcategoria=:subsub
            WHERE id=:id AND id_usuario=:uid";
    $st = $conn->prepare($sql);
    $st->execute([
        ':fecha' => $fecha, ':descripcion' => $descripcion, ':monto' => $monto, ':tipo' => $tipo,
        ':cat' => $cat, ':subcat' => $subcat, ':subsub' => $subsub,
        ':id' => $id, ':uid' => $_SESSION['usuario_id']
    ]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
