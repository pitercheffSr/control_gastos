<?php

/**
 * ------------------------------------------------------------
 * DashboardRouter
 * ------------------------------------------------------------
 * Punto de entrada HTTP para el dashboard.
 *
 * Reglas:
 * - NO SQL
 * - NO lógica de negocio
 * - SOLO enrutar acciones
 * ------------------------------------------------------------
 */


require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/DashboardController.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Acción solicitada
 * Por defecto: resumen
 */
$action = $_GET['action'] ?? 'resumen';

$controller = new DashboardController($pdo);

switch ($action) {


	// -----------------------------------------
	// Movimientos recientes
	// -----------------------------------------

	case 'movimientos':
		if (!isset($_SESSION['usuario_id'])) {
			$result = ['ok' => false, 'error' => 'No autenticado'];
			break;
		}

		$page  = max(1, (int)($_GET['page'] ?? 1));
		$limit = max(1, (int)($_GET['limit'] ?? 10));

		$result = $controller->movimientos(
			(int) $_SESSION['usuario_id'],
			$page,
			$limit = 5
		);
		break;

	// -----------------------------------------
	// Resumen general (ingresos / gastos / balance)
	// -----------------------------------------
	case 'resumen':
		$res = $controller->resumen();
		// Forzamos que si el controlador devuelve ok:true, el resultado final sea consistente
		$result = $res;
		break;
	// -----------------------------------------
	// Distribución 50 / 30 / 20
	// -----------------------------------------
	case 'distribucion':
		$result = $controller->distribucion();
		break;
	// -----------------------------------------
	// Porcentaje de gasto sobre ingresos
	// -----------------------------------------
	case 'porcentaje':
		$result = $controller->porcentaje();
		break;


	// -----------------------------------------
	// Acción no válida
	// -----------------------------------------
	default:
		http_response_code(400);
		$result = [
			'ok'    => false,
			'error' => 'Acción desconocida'
		];
}

echo json_encode($result);
exit;
