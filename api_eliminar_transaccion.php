<?php

require_once "config.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["ok" => false, "error" => "No autenticado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(["ok" => false, "error" => "ID inválido"]);
    exit;
}

$sql = "DELETE FROM transacciones WHERE id = :id AND id_usuario = :uid";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id"  => $id,
    ":uid" => $_SESSION['usuario_id']
]);

echo json_encode(["ok" => true]);
