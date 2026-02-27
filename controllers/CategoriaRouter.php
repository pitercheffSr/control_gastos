<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/CategoriaModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Seguridad
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$uid = $_SESSION['usuario_id'];
$model = new CategoriaModel($pdo);
$action = $_GET['action'] ?? '';

ob_clean();
header('Content-Type: application/json');

try {
    if ($action === 'save') {
        // 1. Recibimos la caja con los datos
        $data = json_decode(file_get_contents('php://input'), true);
        
        // 2. Sacamos los datos uno por uno
        $id = $data['id'] ?? null;
        $nombre = $data['nombre'] ?? '';
        $tipo_fijo = $data['tipo_fijo'] ?? 'gasto';
        $parent_id = $data['parent_id'] ?? null;

        // 3. Si viene un ID, actualizamos. Si no, creamos.
        if (!empty($id)) {
            $success = $model->update($id, $uid, $nombre, $tipo_fijo, $parent_id);
        } else {
            $success = $model->save($uid, $nombre, $tipo_fijo, $parent_id);
        }
        
        echo json_encode(['success' => $success]);

    } elseif ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $model->delete($data['id'], $uid);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>