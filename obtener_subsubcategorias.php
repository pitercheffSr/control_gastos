<?php
require_once "db.php";

header('Content-Type: application/json');

$id_subcategoria = $_GET['id_subcategoria'] ?? null;

if (!$id_subcategoria) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id, nombre FROM subsubcategorias WHERE id_subcategoria = :sub ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([":sub" => $id_subcategoria]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
