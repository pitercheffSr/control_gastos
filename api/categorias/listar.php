<?php

// api/categorias/listar.php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $st = $conn->query("SELECT id, nombre, parent_id, tipo FROM categorias ORDER BY parent_id, nombre");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
