<?php
require_once "config.php";
require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["ok" => false, "error" => "No autenticado"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$id = $input["id"] ?? null;

if (!$id) {
    echo json_encode(["ok" => false, "error" => "ID faltante"]);
    exit;
}

$sql = "DELETE FROM transacciones 
        WHERE id = :id AND id_usuario = :uid LIMIT 1";

$stmt = $conn->prepare($sql);
$result = $stmt->execute([
    "id" => $id,
    "uid" => $_SESSION["usuario_id"]
]);

if ($result) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "error" => "No se pudo eliminar"]);
}
