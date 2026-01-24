<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/CategoriaController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'listar';

/* =====================================================
   🔐 VALIDACIÓN CSRF
   ===================================================== */
if (in_array($action, ['crear', 'editar', 'eliminar'], true)) {
	validar_csrf();
}

/* =====================================================
   ROUTER ÚNICO
   ===================================================== */
$controller = new CategoriaController($pdo);

switch ($action) {
	case 'listar':
		$result = $controller->listar();
		break;

	case 'crear':
		$data = json_decode(file_get_contents('php://input'), true) ?? [];
		$result = $controller->crear($data);
		break;

	case 'editar':
		$data = json_decode(file_get_contents('php://input'), true) ?? [];
		$result = $controller->editar($data);
		break;

	case 'eliminar':
		$id = $_GET['id'] ?? null;
		$result = $controller->eliminar(['id' => $id]);
		break;

	default:
		http_response_code(400);
		$result = ['ok' => false, 'error' => 'Acción desconocida'];
}

echo json_encode($result);
exit;
