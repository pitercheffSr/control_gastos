<?php
// load_categorias.php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$nivel = $_GET['nivel'] ?? '';
$padre = $_GET['padre'] ?? null;
$tipo  = $_GET['tipo'] ?? null;

try {
    if ($nivel === 'nivel1') {
        // Raíces: parent_id IS NULL y (si tipo pedido) tipo = 'gasto'|'ingreso'
        if ($tipo) {
            $stmt = $conn->prepare("SELECT id, nombre FROM categorias WHERE parent_id IS NULL AND tipo = :tipo ORDER BY nombre");
            $stmt->execute(['tipo' => $tipo]);
        } else {
            $stmt = $conn->query("SELECT id, nombre FROM categorias WHERE parent_id IS NULL ORDER BY nombre");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($nivel === 'nivel2' || $nivel === 'nivel3') {
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
