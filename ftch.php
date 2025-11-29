<?php
/**
 * ftch.php — versión completa y CORREGIDA según tu BD
 * ---------------------------------------------------
 * Compatible con tu tabla transacciones:
 *  - id_usuario
 *  - monto
 *  - tipo
 *  - descripcion
 *  - id_categoria
 *  - id_subcategoria
 *  - id_subsubcategoria
 *  - fecha
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$input = array_merge($_GET, $_POST);
$filter_type = $input['filter_type'] ?? 'month';

// ---------------------------
// PAGINACIÓN
// ---------------------------
$page = max(1, intval($input['page'] ?? 1));
$per_page = max(10, intval($input['per_page'] ?? 50));
$offset = ($page - 1) * $per_page;

// ---------------------------
// WHERE inicial según tu BD
// ---------------------------
$where = "t.id_usuario = :id_usuario";
$params = [':id_usuario' => $usuario_id];

// ---------------------------
// FILTROS POR FECHA
// ---------------------------
switch ($filter_type) {
    case 'day':
        $date = $input['date'] ?? date('Y-m-d');
        $where .= " AND DATE(t.fecha) = :filter_date";
        $params[':filter_date'] = $date;
        break;

    case 'week':
        if (!empty($input['week_start']) && !empty($input['week_end'])) {
            $where .= " AND DATE(t.fecha) BETWEEN :week_start AND :week_end";
            $params[':week_start'] = $input['week_start'];
            $params[':week_end'] = $input['week_end'];
        } else {
            $date = $input['date'] ?? date('Y-m-d');
            $dt = new DateTime($date);
            $monday = $dt->modify(('Monday' === $dt->format('l')) ? 'this monday' : 'last monday')->format('Y-m-d');
            $sunday = (new DateTime($monday))->modify('next sunday')->format('Y-m-d');
            $where .= " AND DATE(t.fecha) BETWEEN :week_start AND :week_end";
            $params[':week_start'] = $monday;
            $params[':week_end'] = $sunday;
        }
        break;

    case 'month':
        $year = intval($input['year'] ?? date('Y'));
        $month = intval($input['month'] ?? date('n'));
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = (new DateTime($first))->modify('last day of this month')->format('Y-m-d');
        $where .= " AND DATE(t.fecha) BETWEEN :month_start AND :month_end";
        $params[':month_start'] = $first;
        $params[':month_end'] = $last;
        break;

    case 'year':
        $year = intval($input['year'] ?? date('Y'));
        $where .= " AND YEAR(t.fecha) = :year";
        $params[':year'] = $year;
        break;

    case 'range':
        if (empty($input['date_from']) || empty($input['date_to'])) {
            echo json_encode(['error' => 'Para rango se requieren date_from y date_to']);
            exit;
        }
        $where .= " AND DATE(t.fecha) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $input['date_from'];
        $params[':date_to'] = $input['date_to'];
        break;

    case 'all':
    default:
        break;
}

try {
    // ---------------------------
    // TOTAL DE RESULTADOS
    // ---------------------------
    $countSql = "SELECT COUNT(*) FROM transacciones t WHERE $where";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // ---------------------------
    // CONSULTA PRINCIPAL — ajustada a tu BD
    // ---------------------------
    $sql = "SELECT 
                t.id,
                t.descripcion,
                t.monto,
                t.tipo,
                t.id_categoria,
                t.id_subcategoria,
                t.id_subsubcategoria,
                t.fecha
            FROM transacciones t
            WHERE $where
            ORDER BY t.fecha DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------
    // TOTALES — ajustados a tu BD
    // ---------------------------
    $totalsSql = "SELECT
                      SUM(CASE WHEN t.tipo = 'ingreso' THEN t.monto ELSE 0 END) AS total_ingresos,
                      SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END) AS total_gastos,
                      SUM(CASE WHEN t.tipo = 'ingreso' THEN t.monto ELSE -t.monto END) AS saldo
                  FROM transacciones t
                  WHERE $where";

    $stmt = $pdo->prepare($totalsSql);
    $stmt->execute($params);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    // ---------------------------
    // RESPUESTA JSON
    // ---------------------------
    echo json_encode([
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'transactions' => $rows,
        'totals' => $totals,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
    exit;
}
?>
