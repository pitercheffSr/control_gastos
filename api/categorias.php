<?php

require_once "../config.php";
require_once "../db.php";

header("Content-Type: application/json; charset=utf-8");

// Validar sesión
if (!isset($_SESSION["usuario_id"])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
    $sql = "SELECT id, nombre, parent_id, tipo
            FROM categorias
            ORDER BY parent_id, nombre";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
