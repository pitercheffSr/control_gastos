<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../models/DashboardModel.php';
    require_once __DIR__ . '/../AuthMiddleware.php';

    $uid = AuthMiddleware::checkAPI();

    $action = $_GET['action'] ?? '';
    $fInicio = $_GET['fecha_inicio'] ?? null;
    $fFin = $_GET['fecha_fin'] ?? null;

    $model = new DashboardModel($pdo);

    if ($action === 'getKpis') {
        $data = $model->getKpis($uid, $fInicio, $fFin);
        ob_clean();
        echo json_encode($data);
        exit;
    }

    if ($action === 'getDistribucionGastos') {
        $data = $model->getDistribucionGastos($uid, $fInicio, $fFin);
        ob_clean();
        echo json_encode($data);
        exit;
    }

    if ($action === 'getHistoricalBalance') {
        $data = $model->getHistoricalBalance($uid, $fInicio, $fFin);
        ob_clean();
        echo json_encode($data);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
} catch (\Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
