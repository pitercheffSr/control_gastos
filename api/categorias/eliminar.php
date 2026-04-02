<?php

// api/categorias/eliminar.php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? json_decode(file_get_contents('php://input'), true)['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false,'error' => 'ID inválido']);
    exit;
}

function borrarRamaPDO($db, $id)
{
    $st = $db->prepare("SELECT id FROM categorias WHERE parent_id = :p");
    $st->execute([':p' => $id]);
    $h = $st->fetchAll(PDO::FETCH_COLUMN);
    foreach ($h as $c) {
        borrarRamaPDO($db, $c);
    }
    $db->prepare("DELETE FROM categorias WHERE id = :id")->execute([':id' => $id]);
}

try {
    borrarRamaPDO($conn, $id);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
