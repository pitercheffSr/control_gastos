<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/CategoriaModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    if (!isset($_SESSION['usuario_id'])) { 
        throw new Exception('No autorizado. Tu sesión puede haber caducado.');
    }

    $uid = $_SESSION['usuario_id'];
    $action = $_GET['action'] ?? '';
    $model = new CategoriaModel($pdo);

    if (empty($_SESSION['csrf_token']) || empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        throw new Exception('Error de validación de seguridad (CSRF). Por favor, recargue la página.');
    }

    if ($action === 'createFromTransaction') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $nombre = $data['nombre'] ?? null;
        $parentId = $data['parent_id'] ?? null;

        if (!$nombre || !$parentId) {
            throw new Exception('Faltan el nombre o la categoría padre.');
        }

        $parentCat = $model->getById($parentId, $uid);
        if (!$parentCat) {
            throw new Exception('La categoría padre no existe o no te pertenece.');
        }
        $tipo_fijo = $parentCat['tipo_fijo'] ?? 'gasto';

        $model->create($uid, $nombre, $tipo_fijo, $parentId);
        
        $newCatId = $pdo->lastInsertId();
        $newCat = $model->getById($newCatId, $uid);

        echo json_encode(['success' => true, 'categoria' => $newCat]);
    } else {
        throw new Exception('Acción no reconocida en CategoriaRouter.');
    }
} catch (\Throwable $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

ob_end_flush();
?>