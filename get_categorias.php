<?php
// get_categorias.php — Devuelve categorías, subcategorías y subsubcategorías
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    $categorias = [];

    // Obtener todas las categorías
    $stmtCat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las subcategorías
    $stmtSub = $conn->query("SELECT id, nombre, id_categoria FROM subcategorias ORDER BY nombre ASC");
    $subcategorias = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las sub-subcategorías
    $stmtSubSub = $conn->query("SELECT id, nombre, id_subcategoria FROM subsubcategorias ORDER BY nombre ASC");
    $subsubcategorias = $stmtSubSub->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'categorias' => $categorias,
        'subcategorias' => $subcategorias,
        'subsubcategorias' => $subsubcategorias
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
