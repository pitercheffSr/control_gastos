<?php

// api/transacciones/listar.php
session_start();
require_once __DIR__ . '/../../../db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$uid = intval($_SESSION['usuario_id']);

try {
    $sql = "SELECT t.id, t.fecha, t.descripcion, t.monto, t.tipo,
                   t.id_categoria, t.id_subcategoria, t.id_subsubcategoria,
                   c.nombre as categoria, sc.nombre as subcategoria, ssc.nombre as subsubcategoria
            FROM transacciones t
            LEFT JOIN categorias c ON c.id = t.id_categoria
            LEFT JOIN categorias sc ON sc.id = t.id_subcategoria
            LEFT JOIN categorias ssc ON ssc.id = t.id_subsubcategoria
            WHERE t.id_usuario = :uid
            ORDER BY t.fecha DESC, t.id DESC
            LIMIT 1000";
    $st = $conn->prepare($sql);
    $st->execute([':uid' => $uid]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
