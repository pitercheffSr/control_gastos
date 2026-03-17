<?php
// Ponemos el servidor en "modo seguro" atrapando todo lo que salga
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/TransaccionModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    if (!isset($_SESSION['usuario_id'])) { 
        throw new Exception('No autorizado. Tu sesión puede haber caducado.');
    }

    $uid = $_SESSION['usuario_id'];
    $action = $_GET['action'] ?? '';
    $model = new TransaccionModel($pdo);

    if ($action === 'save') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $id = !empty($data['id']) ? $data['id'] : null;
        $fecha = $data['fecha'] ?? '';
        $desc = $data['descripcion'] ?? '';
        $importe = isset($data['monto']) ? (float)$data['monto'] : 0; 
        
        $cat = (!empty($data['categoria_id']) && $data['categoria_id'] !== 'null') ? $data['categoria_id'] : null;

        if ($id) { 
            $model->update($id, $uid, $cat, $fecha, $desc, $importe); 
        } else { 
            $model->create($uid, $cat, $fecha, $desc, $importe); 
        }
        
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        if ($id) { $model->delete($id, $uid); }
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'getAllLimit') {
        $datos = $model->getAll($uid);
        echo json_encode(array_slice($datos, 0, 5));
    }
    elseif ($action === 'deleteMasivo') {
        $data = json_decode(file_get_contents("php://input"), true);
        $borrar_todo = $data['borrar_todo'] ?? false;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;

        if ($borrar_todo) {
            $stmt = $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ?");
            $stmt->execute([$uid]);
            echo json_encode(['success' => true, 'eliminados' => $stmt->rowCount()]);
        } elseif ($fecha_inicio && $fecha_fin) {
            $stmt = $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ? AND fecha >= ? AND fecha <= ?");
            $stmt->execute([$uid, $fecha_inicio, $fecha_fin]);
            echo json_encode(['success' => true, 'eliminados' => $stmt->rowCount()]);
        } else {
            throw new Exception('Fechas no válidas');
        }
    } else {
        throw new Exception('Acción no reconocida');
    }

} catch (\Throwable $e) {
    // Limpiamos cualquier basura y enviamos el error real en formato JSON limpio
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

ob_end_flush();
?>