<?php
require_once "config.php";
require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

$sql = "
SELECT 
    t.id,
    t.fecha,
    t.descripcion,
    t.monto,
    t.tipo,
    c1.nombre AS categoria,
    c2.nombre AS subcategoria,
    c3.nombre AS subsubcategoria,
    t.id_categoria,
    t.id_subcategoria,
    t.id_subsubcategoria
FROM transacciones t
LEFT JOIN categorias c1 ON c1.id = t.id_categoria
LEFT JOIN categorias c2 ON c2.id = t.id_subcategoria
LEFT JOIN categorias c3 ON c3.id = t.id_subsubcategoria
WHERE t.id_usuario = :uid
ORDER BY t.fecha DESC, t.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute(["uid" => $_SESSION['usuario_id']]);

$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Asegurar que los campos numéricos sean números para JSON correcto
foreach ($transacciones as &$t) {
    $t['monto'] = (float)$t['monto'];
    $t['id'] = (int)$t['id'];
    $t['id_categoria'] = $t['id_categoria'] ? (int)$t['id_categoria'] : null;
    $t['id_subcategoria'] = $t['id_subcategoria'] ? (int)$t['id_subcategoria'] : null;
    $t['id_subsubcategoria'] = $t['id_subsubcategoria'] ? (int)$t['id_subsubcategoria'] : null;
}

echo json_encode($transacciones);
