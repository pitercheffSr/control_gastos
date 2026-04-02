<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/TransaccionModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['usuario_id'])) { throw new Exception('No autorizado'); }

    $uid = $_SESSION['usuario_id'];
    $action = $_GET['action'] ?? '';
    $model = new TransaccionModel($pdo);

    if ($action === 'getAllLimit') {
        echo json_encode($model->getAllLimit($uid, 10));
    }
    elseif ($action === 'getAll') {
        echo json_encode($model->getAll($uid));
    }
    elseif ($action === 'save') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $id = !empty($data['id']) ? $data['id'] : null;
        $descripcion = $data['descripcion'] ?? '';
        $importe = $data['importe'] ?? 0;
        $fecha = $data['fecha'] ?? date('Y-m-d');
        $categoria_id = !empty($data['categoria_id']) ? $data['categoria_id'] : null;

        if ($id) {
            // Si hay ID, actualizamos
            $model->update($id, $uid, $descripcion, $importe, $fecha, $categoria_id);
        } else {
            // Si no hay ID, creamos uno nuevo
            $model->create($uid, $descripcion, $importe, $fecha, $categoria_id);
        }
        
        // Limpiamos a la fuerza cualquier basura oculta en el buffer antes de responder
        ob_clean(); 
        echo json_encode(['success' => true]);
        exit;
    }
    elseif ($action === 'delete') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        if ($id) {
            $model->delete($id, $uid);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('ID inválido');
        }
    }
    else {
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