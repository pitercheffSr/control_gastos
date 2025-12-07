<?php
include_once "config.php";
include_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["error" => "no_auth"]);
    exit;
}

$uid = intval($_SESSION["usuario_id"]);

// Parámetros
$page     = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$per_page = isset($_GET["per_page"]) ? intval($_GET["per_page"]) : 20;

$buscar = $_GET["buscar"] ?? "";
$tipo   = $_GET["tipo"] ?? "";
$f_desde = $_GET["f_desde"] ?? "";
$f_hasta = $_GET["f_hasta"] ?? "";

$offset = ($page - 1) * $per_page;

// Construcción dinámica del WHERE
$where = " WHERE t.id_usuario = :uid ";
$params = [":uid" => $uid];

// Buscar por texto
if ($buscar !== "") {
    $where .= " AND t.descripcion LIKE :buscar ";
    $params[":buscar"] = "%$buscar%";
}

// Filtrar tipo
if ($tipo !== "") {
    $where .= " AND t.tipo = :tipo ";
    $params[":tipo"] = $tipo;
}

// Fechas
if ($f_desde !== "") {
    $where .= " AND t.fecha >= :f_desde ";
    $params[":f_desde"] = $f_desde;
}

if ($f_hasta !== "") {
    $where .= " AND t.fecha <= :f_hasta ";
    $params[":f_hasta"] = $f_hasta;
}

// Total para paginación
$sqlTotal = "SELECT COUNT(*) AS total FROM transacciones t $where";
$stmt = $conn->prepare($sqlTotal);
$stmt->execute($params);
$total = $stmt->fetch()["total"];

// Consulta principal
$sql = "
SELECT 
    t.id,
    t.fecha,
    t.descripcion,
    t.monto,
    t.tipo,

    c.nombre AS categoria_nombre,
    s.nombre AS subcategoria_nombre,
    ss.nombre AS subsub_nombre

FROM transacciones t
LEFT JOIN categorias c       ON t.id_categoria = c.id
LEFT JOIN subcategorias s    ON t.id_subcategoria = s.id
LEFT JOIN subsubcategorias ss ON t.id_subsubcategoria = ss.id

$where
ORDER BY t.fecha DESC, t.id DESC
LIMIT :offset, :per_page
";

$stmt = $conn->prepare($sql);

// Añadir parámetros dinámicos
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->bindValue(":per_page", $per_page, PDO::PARAM_INT);

$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Respuesta JSON
echo json_encode([
    "page" => $page,
    "per_page" => $per_page,
    "total" => intval($total),
    "transactions" => $rows
]);
