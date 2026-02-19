<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/DashboardModel.php';

// CORRECCIÓN: Le decimos a PHP que solo inicie la sesión si no se ha iniciado ya antes en config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$uid = $_SESSION['usuario_id'];
$model = new DashboardModel($pdo);
$action = $_GET['action'] ?? '';
$mes = $_GET['mes'] ?? date('Y-m');

// Limpiamos cualquier espacio en blanco o aviso previo antes de enviar el JSON
ob_clean();
header('Content-Type: application/json');

if ($action === 'getKpis') {
    echo json_encode($model->getKpis($uid, $mes));
} elseif ($action === 'getDistribucionGastos') {
    echo json_encode($model->getDistribucionGastos($uid, $mes));
} else {
    echo json_encode(['error' => 'Acción no encontrada']);
}