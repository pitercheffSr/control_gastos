<?php

/**
 * TransaccionRouter.php
 * Gestiona las peticiones de creación, edición y borrado de transacciones.
 */

// Ya no llamamos a session_start() aquí porque config.php lo hace por nosotros.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/TransaccionController.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? null;

/* =====================================================
   🔐 VALIDACIÓN CSRF (Solo para acciones de escritura)
   ===================================================== */
if (in_array($action, ['crear', 'editar', 'eliminar'], true)) {
	validar_csrf();
}

/* =====================================================
   ENRUTADOR
   ===================================================== */
$controller = new TransaccionController($pdo);

switch ($action) {

	case 'listar':
		$result = $controller->listar();
		break;

	case 'obtener':
		$id = $_GET['id'] ?? null;
		$result = $controller->obtener(['id' => $id]);
		break;

	case 'crear':
		// Leemos el JSON enviado por transacciones_form.js
		$data = json_decode(file_get_contents('php://input'), true) ?? [];
		$result = $controller->crear($data);
		break;

	case 'editar':
		$data = json_decode(file_get_contents('php://input'), true) ?? [];
		$result = $controller->editar($data);
		break;

	case 'eliminar':
		$data = json_decode(file_get_contents('php://input'), true) ?? [];
		$result = $controller->eliminar($data);
		break;

	default:
		http_response_code(400);
		$result = [
			'ok'    => false,
			'error' => 'Acción desconocida'
		];
}

echo json_encode($result);
exit;
