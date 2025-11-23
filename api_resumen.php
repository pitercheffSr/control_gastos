<?php
// api_resumen.php - devuelve { labels: [...], values: [...] } para Chart.js
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? 'gasto'; // 'gasto' o 'ingreso'
$periodo = $_GET['periodo'] ?? 'todo';
$f1 = $_GET['f1'] ?? null;
$f2 = $_GET['f2'] ?? null;
$tipo_filtro = $_GET['tipo_filtro'] ?? ''; // opcional

try {
    $params = [];
    $where = "t.tipo = :tt"; $params['tt'] = $tipo;

    if ($tipo_filtro) {
        $where .= " AND (c.tipo = :ct OR c.tipo IS NULL)";
        $params['ct'] = $tipo_filtro;
    }

    // periodos simples
    if ($periodo === 'hoy') {
        $where .= " AND t.fecha = CURDATE()";
    } elseif ($periodo === 'semana') {
        $where .= " AND YEARWEEK(t.fecha,1) = YEARWEEK(CURDATE(),1)";
    } elseif ($periodo === 'mes') {
        $where .= " AND YEAR(t.fecha)=YEAR(CURDATE()) AND MONTH(t.fecha)=MONTH(CURDATE())";
    } elseif ($periodo === 'anio') {
        $where .= " AND YEAR(t.fecha)=YEAR(CURDATE())";
    } elseif ($periodo === 'rango' && $f1 && $f2) {
        $where .= " AND t.fecha BETWEEN :f1 AND :f2";
        $params['f1'] = $f1; $params['f2'] = $f2;
    }

    $sql = "SELECT COALESCE(c.nombre, t.categoria) AS label, SUM(t.monto) AS total
            FROM transacciones t
            LEFT JOIN categorias c ON t.id_categoria = c.id
            WHERE $where
            GROUP BY label
            ORDER BY total DESC
            LIMIT 12";
    $st = $conn->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $labels = []; $values = [];
    foreach ($rows as $r) { $labels[] = $r['label']; $values[] = (float)$r['total']; }
    echo json_encode(['labels'=>$labels,'values'=>$values]);
} catch (Exception $e) {
    echo json_encode(['labels'=>[], 'values'=>[], 'error'=>$e->getMessage()]);
}
