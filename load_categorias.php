<?php
// load_categorias.php — Devuelve categorías, subcategorías y sub-subcategorías en formato JSON
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$nivel = $_GET['nivel'] ?? '';
$padre = $_GET['padre'] ?? null;

try {
    switch ($nivel) {
        case 'categorias':
            $stmt = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'subcategorias':
            if ($padre) {
                $stmt = $conn->prepare("SELECT id, nombre FROM subcategorias WHERE id_categoria = :id ORDER BY nombre");
                $stmt->execute(['id' => $padre]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $data = [];
            }
            break;

        case 'subsubcategorias':
            if ($padre) {
                $stmt = $conn->prepare("SELECT id, nombre FROM subsubcategorias WHERE id_subcategoria = :id ORDER BY nombre");
                $stmt->execute(['id' => $padre]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $data = [];
            }
            break;

        default:
            $data = [];
    }

    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
