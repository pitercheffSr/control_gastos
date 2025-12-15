<?php

// /control_gastos/api/ftch.php

session_start();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

// Seguridad
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];

// -------------------------------
// Parámetros
// -------------------------------
$filterType = $_POST['filter_type'] ?? 'all';
$page       = max(1, (int)($_POST['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

// -------------------------------
// Construir WHERE dinámico
// -------------------------------
$where = 'WHERE t.id_usuario = :uid';
$params = ['uid' => $idUsuario];

switch ($filterType) {

    case 'day':
        if (!empty($_POST['date'])) {
            $where .= ' AND t.fecha = :fecha';
            $params['fecha'] = $_POST['date'];
        }
        break;

    case 'month':
        $m = isset($_POST['month']) ? (int)$_POST['month'] : 0;
        $y = isset($_POST['year']) ? (int)$_POST['year'] : 0;

        if ($m > 0 && $y > 0) {
            $where .= ' AND MONTH(t.fecha) = :m AND YEAR(t.fecha) = :y';
            $params['m'] = $m;
            $params['y'] = $y;
        }
        // si no hay mes/año → NO filtrar
        break;

    case 'range':
        if (!empty($_POST['date_from']) && !empty($_POST['date_to'])) {
            $where .= ' AND t.fecha BETWEEN :df AND :dt';
            $params['df'] = $_POST['date_from'];
            $params['dt'] = $_POST['date_to'];
        }
        break;

    case 'all':
    default:
        // SIN FILTRO DE FECHA
        break;
}

// -------------------------------
// Total registros (para paginador)
// -------------------------------
$stTotal = $conn->prepare("
    SELECT COUNT(*)
    FROM transacciones t
    $where
");
$stTotal->execute($params);
$totalRows = (int)$stTotal->fetchColumn();

// -------------------------------
// Totales ingresos / gastos
// -------------------------------
$stTotals = $conn->prepare("
    SELECT
        SUM(CASE WHEN t.tipo = 'ingreso' THEN t.monto ELSE 0 END) AS total_ingresos,
        SUM(CASE WHEN t.tipo = 'gasto'   THEN t.monto ELSE 0 END) AS total_gastos
    FROM transacciones t
    $where
");
$stTotals->execute($params);
$tot = $stTotals->fetch(PDO::FETCH_ASSOC);

$totalIngresos = (float)($tot['total_ingresos'] ?? 0);
$totalGastos   = (float)($tot['total_gastos'] ?? 0);

// -------------------------------
// Transacciones paginadas
// -------------------------------
$sql = "
SELECT
    t.id,
    t.fecha,
    t.descripcion,
    t.monto,
    t.tipo,
    c.nombre AS categoria_nombre,
    sc.nombre AS subcategoria_nombre,
    ssc.nombre AS subsub_nombre
FROM transacciones t
LEFT JOIN categorias c   ON t.id_categoria = c.id
LEFT JOIN categorias sc  ON t.id_subcategoria = sc.id
LEFT JOIN categorias ssc ON t.id_subsubcategoria = ssc.id
$where
ORDER BY t.fecha DESC, t.id DESC
LIMIT $perPage OFFSET $offset
";

$st = $conn->prepare($sql);
$st->execute($params);
$transactions = $st->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Respuesta FINAL (contrato claro)
// -------------------------------
echo json_encode([
    'page' => $page,
    'per_page' => $perPage,
    'total' => $totalRows,
    'totals' => [
        'total_ingresos' => $totalIngresos,
        'total_gastos'   => $totalGastos,
        'saldo'          => $totalIngresos - $totalGastos
    ],
    'transactions' => $transactions
]);
