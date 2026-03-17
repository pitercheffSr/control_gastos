<?php
require_once '../config.php';
require_once '../models/DashboardModel.php';

ob_clean();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit; 
}

$uid = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';
$fInicio = $_GET['fecha_inicio'] ?? null;
$fFin = $_GET['fecha_fin'] ?? null;

$model = new DashboardModel($pdo);

try {
    if ($action === 'getKpis') {
        echo json_encode($model->getKpis($uid, $fInicio, $fFin));
        exit;
    }

    if ($action === 'getDistribucionGastos') {
        echo json_encode($model->getDistribucionGastos($uid, $fInicio, $fFin));
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>