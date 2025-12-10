<?php

require_once "db.php";

header('Content-Type: application/json');

$id_categoria = $_GET['id_categoria'] ?? null;

if (!$id_categoria) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id, nombre FROM subcategorias WHERE id_categoria = :cat ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute([":cat" => $id_categoria]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
