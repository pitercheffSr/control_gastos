<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/CategoriaModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['usuario_id'])) { throw new Exception('No autorizado'); }

    $uid = $_SESSION['usuario_id'];
    $action = $_GET['action'] ?? '';
    $model = new CategoriaModel($pdo);

    if ($action === 'getAll') {
        echo json_encode($model->getAll($uid));
    } 
    elseif ($action === 'save') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = !empty($data['id']) ? $data['id'] : null;
        $nombre = $data['nombre'] ?? '';
        $tipo = $data['tipo_fijo'] ?? 'gasto';
        $parent = !empty($data['parent_id']) ? $data['parent_id'] : null;

        if ($id) {
            $model->update($id, $uid, $nombre, $tipo, $parent);
        } else {
            $model->create($uid, $nombre, $tipo, $parent);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        
        if ($id) { 
            $model->delete($id, $uid); 
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('ID de categoría no válido');
        }
    } else {
        throw new Exception('Acción no reconocida');
    }

} catch (\Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
ob_end_flush();
?>