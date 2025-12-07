<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Incluir conexión PDO
require_once 'db.php';

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Obtener ID de la transacción
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    // Obtener la transacción (verificar que pertenece al usuario)
    $stmt = $conn->prepare("
        SELECT id, fecha, descripcion, monto, tipo, 
               id_categoria, id_subcategoria, id_subsubcategoria
        FROM transacciones
        WHERE id = ? AND id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    $transaccion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaccion) {
        http_response_code(404);
        echo json_encode(['error' => 'Transacción no encontrada']);
        exit;
    }

    // Asegurar que los campos numéricos sean números
    $transaccion['monto'] = (float)$transaccion['monto'];
    $transaccion['id_categoria'] = $transaccion['id_categoria'] ? (int)$transaccion['id_categoria'] : null;
    $transaccion['id_subcategoria'] = $transaccion['id_subcategoria'] ? (int)$transaccion['id_subcategoria'] : null;
    $transaccion['id_subsubcategoria'] = $transaccion['id_subsubcategoria'] ? (int)$transaccion['id_subsubcategoria'] : null;

    echo json_encode($transaccion);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
