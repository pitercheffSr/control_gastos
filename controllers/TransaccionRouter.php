<?php
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/TransaccionController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

$controller = new TransaccionController($pdo);
// -------- LISTAR --------
if ($action === 'listar') {
    echo json_encode($controller->listar());
    exit;
}
// -------- CREAR --------
if ($action === 'eliminar') {
    $input = json_decode(file_get_contents('php://input'), true);
    echo json_encode($controller->eliminar($input));
    exit;
}
// -------- OBTENER --------
if ($action === 'obtener') {
    $id = $_GET['id'] ?? null;
    echo json_encode($controller->obtener(['id' => $id]));
    exit;
}
// -------- EDITAR --------
if ($action === 'editar') {
    $input = json_decode(file_get_contents('php://input'), true);
    echo json_encode($controller->editar($input));
    exit;
}



// 👇 SOLO si ninguna acción coincidió
echo json_encode([
    'ok' => false,
    'error' => 'Acción desconocida'
]);
exit;
