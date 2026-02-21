<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/TransaccionModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BARRERA DE SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$uid = $_SESSION['usuario_id'];
$model = new TransaccionModel($pdo);
$action = $_GET['action'] ?? '';

// Limpiamos basura oculta antes de imprimir JSON
ob_clean();
header('Content-Type: application/json');

if ($action === 'getAllLimit') {
    echo json_encode($model->getAllLimit($uid, 5));
} elseif ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Pasamos el $uid para garantizar que se guarde a su nombre
    echo json_encode(['success' => $model->save($data, $uid)]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Pasamos el $uid para que solo pueda borrar lo suyo
    echo json_encode(['success' => $model->delete($data['id'], $uid)]);
} else {
    echo json_encode(['error' => 'Acción no válida']);
}