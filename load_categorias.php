<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$nivel = $_GET['nivel'] ?? '';
$padre = $_GET['padre'] ?? null;

try {
    if ($nivel === 'nivel1') {
        // Categorías raíz (parent_id = NULL)
        $stmt = $conn->query("SELECT id, nombre FROM categorias WHERE parent_id IS NULL ORDER BY nombre");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($nivel === 'nivel2') {
        if (!$padre) { echo json_encode([]); exit; }
        $stmt = $conn->prepare("SELECT id, nombre FROM categorias WHERE parent_id = :p ORDER BY nombre");
        $stmt->execute(['p' => $padre]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($nivel === 'nivel3') {
        if (!$padre) { echo json_encode([]); exit; }
        $stmt = $conn->prepare("SELECT id, nombre FROM categorias WHERE parent_id = :p ORDER BY nombre");
        $stmt->execute(['p' => $padre]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    echo json_encode(["error" => "nivel inválido"]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
