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

// Obtener datos del POST (JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    // Verificar que la transacción pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM transacciones WHERE id = ? AND id_usuario = ? LIMIT 1");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Transacción no encontrada o no autorizada']);
        exit;
    }

    // Actualizar la transacción
    $stmt = $conn->prepare("
        UPDATE transacciones
        SET fecha = ?,
            descripcion = ?,
            monto = ?,
            tipo = ?,
            id_categoria = ?,
            id_subcategoria = ?,
            id_subsubcategoria = ?
        WHERE id = ? AND id_usuario = ?
    ");

    $fecha = $data['fecha'] ?? null;
    $descripcion = $data['descripcion'] ?? null;
    $monto = isset($data['monto']) ? (float)$data['monto'] : 0;
    $tipo = $data['tipo'] ?? 'gasto';
    $id_cat = isset($data['categoria']) && $data['categoria'] !== '' ? (int)$data['categoria'] : null;
    $id_subcat = isset($data['subcategoria']) && $data['subcategoria'] !== '' ? (int)$data['subcategoria'] : null;
    $id_subsub = isset($data['subsub']) && $data['subsub'] !== '' ? (int)$data['subsub'] : null;

    $stmt->execute([
        $fecha,
        $descripcion,
        $monto,
        $tipo,
        $id_cat,
        $id_subcat,
        $id_subsub,
        $id,
        $_SESSION['usuario_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Transacción actualizada correctamente']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
