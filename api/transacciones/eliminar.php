<?php

// api/transacciones/eliminar.php
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
$id = intval($body['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false,'error' => 'ID inválido']);
    exit;
}

try {
    $st = $conn->prepare("DELETE FROM transacciones WHERE id = :id AND id_usuario = :uid");
    $st->execute([':id' => $id, ':uid' => $_SESSION['usuario_id']]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
