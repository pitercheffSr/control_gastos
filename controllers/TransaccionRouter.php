<?php
require_once '../config.php';
require_once '../models/TransaccionModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit; 
}

$uid = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';
$model = new TransaccionModel($pdo);

if ($action === 'save') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $fecha = $data['fecha'] ?? '';
    $desc = $data['descripcion'] ?? '';
    $monto = $data['monto'] ?? 0;
    $cat = $data['categoria_id'] ?? null;

    if ($id) { $model->update($id, $uid, $cat, $fecha, $desc, $monto); } 
    else { $model->create($uid, $cat, $fecha, $desc, $monto); }
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

if ($action === 'getAllLimit') {
    $stmt = $pdo->prepare("
        SELECT t.id, t.fecha, t.descripcion, t.monto as importe, t.categoria_id, c.nombre as categoria_nombre 
        FROM transacciones t 
        LEFT JOIN categorias c ON t.categoria_id = c.id 
        WHERE t.usuario_id = ? 
        ORDER BY t.fecha DESC, t.id DESC 
        LIMIT 5
    ");
    $stmt->execute([$uid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- NUEVA ACCIÓN: BORRADO MASIVO ---
if ($action === 'deleteMasivo') {
    $data = json_decode(file_get_contents("php://input"), true);
    $fecha_inicio = $data['fecha_inicio'] ?? null;
    $fecha_fin = $data['fecha_fin'] ?? null;

    if ($fecha_inicio && $fecha_fin) {
        // Ejecutamos el DELETE entre las dos fechas para el usuario activo
        $stmt = $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ? AND fecha >= ? AND fecha <= ?");
        $stmt->execute([$uid, $fecha_inicio, $fecha_fin]);
        
        // rowCount() nos devuelve el número exacto de filas que se acaban de destruir
        $eliminados = $stmt->rowCount(); 
        
        echo json_encode(['success' => true, 'eliminados' => $eliminados]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fechas no válidas']);
    }
    exit;
}