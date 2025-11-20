<?php
// load_categorias.php — devuelve categorías en JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/conexion.php'; // crea $conexion (mysqli)

$nivel = $_GET['nivel'] ?? 'categorias';

if ($nivel === 'categorias') {
	$res = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
	$rows = [];
	while ($r = $res->fetch_assoc()) $rows[] = $r;
	echo json_encode($rows);
	exit;
}

// futuros niveles (subcategorias/subsubcategorias) pueden delegarse a load_categorias.php?nivel=...
echo json_encode([]);
