<?php
require_once '../config.php';
require_once '../models/TransaccionModel.php';

// Limpiamos cualquier espacio en blanco para no romper el JSON
ob_clean();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit; 
}

$uid = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';
$model = new TransaccionModel($pdo);

try {
    if ($action === 'save') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Extraemos y saneamos los datos
        $id = !empty($data['id']) ? $data['id'] : null;
        $fecha = $data['fecha'] ?? '';
        $desc = $data['descripcion'] ?? '';
        $importe = $data['monto'] ?? 0; // Tu JS manda 'monto', la BD usa 'importe'
        $cat = !empty($data['categoria_id']) ? $data['categoria_id'] : null;

        // Mandamos los datos al modelo en el orden exacto
        if ($id) { 
            $model->update($id, $uid, $cat, $fecha, $desc, $importe); 
        } else { 
            $model->create($uid, $cat, $fecha, $desc, $importe); 
        }
        
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        if ($id) { $model->delete($id, $uid); }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'deleteMasivo') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Comprobamos si nos mandan la orden nuclear
        $borrar_todo = $data['borrar_todo'] ?? false;
        $fecha_inicio = $data['fecha_inicio'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;

        if ($borrar_todo) {
            // Modo Nuclear: Borramos TODO el historial del usuario
            $stmt = $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ?");
            $stmt->execute([$uid]);
            $eliminados = $stmt->rowCount(); 
            echo json_encode(['success' => true, 'eliminados' => $eliminados]);
        } elseif ($fecha_inicio && $fecha_fin) {
            // Modo Rango: Borramos solo entre dos fechas
            $stmt = $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ? AND fecha >= ? AND fecha <= ?");
            $stmt->execute([$uid, $fecha_inicio, $fecha_fin]);
            $eliminados = $stmt->rowCount(); 
            echo json_encode(['success' => true, 'eliminados' => $eliminados]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fechas o parámetros no válidos']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>