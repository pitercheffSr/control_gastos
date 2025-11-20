<?php
// obtener_subcategorias.php — devuelve subcategorías (nivel 1) o hijos por parent_id (nivel 2)
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
$nivel = intval($_GET['nivel'] ?? 1);
$result = [];

if ($nivel === 1) {
    $id_categoria = intval($_GET['id_categoria'] ?? 0);
    if ($id_categoria <= 0) { echo json_encode([]); exit; }
    $stmt = $conexion->prepare("SELECT id, nombre FROM subcategorias WHERE id_categoria = ? AND parent_id IS NULL ORDER BY nombre");
    $stmt->bind_param('i', $id_categoria);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $result[] = $row;
    $stmt->close();

} elseif ($nivel === 2) {
    $parent_id = intval($_GET['parent_id'] ?? 0);
    if ($parent_id <= 0) { echo json_encode([]); exit; }
    $stmt = $conexion->prepare("SELECT id, nombre FROM subcategorias WHERE parent_id = ? ORDER BY nombre");
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $result[] = $row;
    $stmt->close();

} else {
    // nivel no soportado
}

echo json_encode($result);
