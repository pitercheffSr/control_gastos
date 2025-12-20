<?php

// api/transacciones/obtener.php
session_start();
require_once __DIR__ . '/../../../db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $sql = "SELECT * FROM transacciones WHERE id = :id AND id_usuario = :uid LIMIT 1";
    $st = $conn->prepare($sql);
    $st->execute([':id' => $id, ':uid' => $_SESSION['usuario_id']]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'No encontrado']);
        exit;
    }
    echo json_encode($row);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
