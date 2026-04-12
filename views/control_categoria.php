<?php

/**
 * ------------------------------------------------------------
 * procesar_categoria.php (Router MVC)
 * ------------------------------------------------------------
 * Router para acciones de categorías.
 * No contiene lógica de negocio ni SQL.
 * ------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// --- PROTECCIÓN CSRF PARA CATEGORÍAS ---
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF ausente o inválido.']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === '') {
    echo json_encode(['status' => 'error', 'message' => 'Acción no definida']);
    exit;
}

$controller = new CategoriaController($pdo);

switch ($action) {

    case 'add_categoria':
        $result = $controller->addCategoria($_POST);
        break;

    case 'add_subcategoria':
        $result = $controller->addSubcategoria($_POST);
        break;

    case 'delete_subcategoria':
        $result = $controller->deleteSubcategoria($_POST);
        break;

    case 'delete_categoria':
        $result = $controller->deleteCategoria($_POST);
        break;

    case 'add_subsubcategoria':
        $result = $controller->addSubSubcategoria($_POST);
        break;

    default:
        $result = ['status' => 'error', 'message' => 'Acción desconocida'];
}

echo json_encode($result);
exit;
