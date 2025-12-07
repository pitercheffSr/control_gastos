<?php
include_once "config.php";
include_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["ok" => false, "error" => "No autenticado"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$fecha = $input["fecha"] ?? null;
$descripcion = $input["descripcion"] ?? "";
$monto = $input["monto"] ?? 0;
$tipo = $input["tipo"] ?? null;
$categoria = $input["categoria"] ?? null;
$subcategoria = $input["subcategoria"] ?? null;
$subsub = $input["subsub"] ?? null;

if (!$fecha || !$monto || !$tipo) {
    echo json_encode(["ok" => false, "error" => "Datos incompletos"]);
    exit;
}

$sql = "
INSERT INTO transacciones
(id_usuario, fecha, descripcion, monto, tipo, id_categoria, id_subcategoria, id_subsubcategoria)
VALUES (:uid, :fecha, :descripcion, :monto, :tipo, :cat, :subcat, :subsub)
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":uid" => $_SESSION["usuario_id"],
    ":fecha" => $fecha,
    ":descripcion" => $descripcion,
    ":monto" => $monto,
    ":tipo" => $tipo,
    ":cat" => $categoria,
    ":subcat" => $subcategoria,
    ":subsub" => $subsub
]);

echo json_encode(["ok" => true]);
