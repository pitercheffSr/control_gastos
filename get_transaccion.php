<?php
require_once "config.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

// 1. Validar login
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// 2. Validar ID recibido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID inválido"]);
    exit;
}

$id = intval($_GET['id']);

// 3. Obtener transacción
$sql = "SELECT
            id,
            fecha,
            descripcion,
            monto,
            tipo,
            id_categoria,
            id_subcategoria,
            id_subsubcategoria
        FROM transacciones
        WHERE id = :id AND id_usuario = :uid
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id'  => $id,
    ':uid' => $_SESSION['usuario_id']
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Si no existe → error
if (!$data) {
    http_response_code(404);
    echo json_encode(["error" => "Transacción no encontrada"]);
    exit;
}

// 5. Devolver datos en JSON
echo json_encode($data);
