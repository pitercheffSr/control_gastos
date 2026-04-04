<?php

/**
 * ------------------------------------------------------------
 * procesar_transaccion.php (Router MVC)
 * ------------------------------------------------------------
 * Router para creación de transacciones.
 * ------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/controllers/TransaccionController.php';

header('Content-Type: application/json; charset=utf-8');

// Leer JSON del body
$input = json_decode(file_get_contents('php://input'), true);

// Validar JSON
if (!is_array($input)) {
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

// Delegar en controller
$controller = new TransaccionController($pdo);
$result = $controller->crear($input);

// Responder
echo json_encode($result);
exit;
