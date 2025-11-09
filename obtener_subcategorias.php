<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

$id_categoria = intval($_GET['id_categoria'] ?? 0);
if ($id_categoria <= 0) {
    echo json_encode([]);
    exit;
}

// Obtener subcategorías
$stmt = $conexion->prepare("SELECT id, nombre FROM subcategorias WHERE id_categoria = ?");
$stmt->bind_param('i', $id_categoria);
$stmt->execute();
$res = $stmt->get_result();
$subcategorias = [];
while($row = $res->fetch_assoc()) {
    $subcategorias[] = $row;
}
$stmt->close();

echo json_encode($subcategorias);
