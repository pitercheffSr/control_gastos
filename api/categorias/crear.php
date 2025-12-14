<?php

// api/categorias/crear.php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
}

$nombre = trim($body['nombre'] ?? '');
$parent = isset($body['parent_id']) ? intval($body['parent_id']) : null;
$tipo = $body['tipo'] ?? null;

if ($nombre === '') {
    echo json_encode(['ok' => false,'error' => 'Nombre requerido']);
    exit;
}

try {
    if ($parent) {
        // inherit type from parent if not provided
        $st = $conn->prepare("SELECT tipo FROM categorias WHERE id = :p LIMIT 1");
        $st->execute([':p' => $parent]);
        $t = $st->fetchColumn();
        if (!$t) {
            echo json_encode(['ok' => false,'error' => 'Padre no encontrado']);
            exit;
        }
        $tipo = $t;
    }
    $sql = "INSERT INTO categorias (nombre,parent_id,tipo) VALUES (:n, :p, :t)";
    $st = $conn->prepare($sql);
    $st->execute([':n' => $nombre, ':p' => $parent, ':t' => $tipo]);
    echo json_encode(['ok' => true,'id' => $conn->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
