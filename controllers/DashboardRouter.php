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

session_start();

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
		$page  = max(1, (int)($_GET['page'] ?? 1));
		$limit = 10;
		$offset = ($page - 1) * $limit;

		$result = $controller->movimientos($page, $limit, $offset);
		break;


	// -----------------------------------------
	// Resumen general (ingresos / gastos / balance)
	// -----------------------------------------
	case 'resumen':
		$result = $controller->resumen();
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
