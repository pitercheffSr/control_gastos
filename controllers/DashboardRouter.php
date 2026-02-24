<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/DashboardModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']); 
    exit;
}

$uid = $_SESSION['usuario_id'];
// Recibimos las fechas exactas que manda el calendario
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$action = $_GET['action'] ?? '';

$model = new DashboardModel($pdo);

// Limpiamos la salida para que el JSON no se rompa
ob_clean();
header('Content-Type: application/json');

try {
    if ($action === 'getKpis') {
        $kpis = $model->getKpis($uid, $fecha_inicio, $fecha_fin);
        echo json_encode([
            'ingresos' => $kpis['ingresos'] ?? 0,
            'gastos' => $kpis['gastos'] ?? 0
        ]);
    } elseif ($action === 'getDistribucionGastos') {
        $dist = $model->getDistribucionGastos($uid, $fecha_inicio, $fecha_fin);
        echo json_encode($dist ?: []);
    } else {
        echo json_encode(['error' => 'Accion no valida']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>