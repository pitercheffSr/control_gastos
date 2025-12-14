<?php

// api/categorias/editar.php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
}

$id = intval($body['id'] ?? 0);
$nombre = trim($body['nombre'] ?? '');
if ($id <= 0 || $nombre === '') {
    echo json_encode(['ok' => false,'error' => 'Datos inválidos']);
    exit;
}

try {
    $st = $conn->prepare("UPDATE categorias SET nombre = :n WHERE id = :id");
    $st->execute([':n' => $nombre, ':id' => $id]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
