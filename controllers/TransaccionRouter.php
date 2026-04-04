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
        echo json_encode($model->getAll($uid, 10));
    }
    elseif ($action === 'getAll') {
        echo json_encode($model->getAll($uid));
    }
    elseif ($action === 'save') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $id = !empty($data['id']) ? $data['id'] : null;
        $descripcion = $data['descripcion'] ?? '';
        $importe = $data['monto'] ?? $data['importe'] ?? 0;
        $fecha = $data['fecha'] ?? date('Y-m-d');
        $categoria_id = !empty($data['categoria_id']) ? $data['categoria_id'] : null;

        // Corrección para el panel lateral que envía 'tipo' y un importe siempre positivo
        $tipo = $data['tipo'] ?? null;
        if ($tipo === 'gasto' && $importe > 0) {
            $importe = -$importe;
        } elseif ($tipo === 'ingreso' && $importe < 0) {
            $importe = abs($importe);
        }

        $transactionId = null;
        if ($id) {
            // Si hay ID, actualizamos (parámetros en orden correcto)
            $model->update($id, $uid, $categoria_id, $fecha, $descripcion, $importe);
            $transactionId = $id;
        } else {
            // Si no hay ID, creamos uno nuevo (parámetros en orden correcto)
            $transactionId = $model->create($uid, $categoria_id, $fecha, $descripcion, $importe);
        }
        
        if ($transactionId) {
            // Obtenemos el objeto completo para devolverlo al frontend
            $transaction = $model->getSingleTransactionWithCategoryName($transactionId, $uid);
            ob_clean(); 
            echo json_encode(['success' => true, 'data' => $transaction]);
            exit;
        } else {
            throw new Exception('No se pudo guardar o recuperar la transacción.');
        }
    }
    elseif ($action === 'getById') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            throw new Exception('ID de transacción no proporcionado.');
        }
        $transaction = $model->getById((int)$id, $uid);
        if (!$transaction) {
            throw new Exception('Transacción no encontrada o no pertenece al usuario.');
        }
        ob_clean();
        echo json_encode(['success' => true, 'data' => $transaction]);
        exit;
    }
    elseif ($action === 'getPaginated') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 25); // Usar el límite por defecto de la vista
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;
        $categoryId = $_GET['categoryId'] ?? null;
        $searchText = $_GET['searchText'] ?? null;

        $result = $model->getPaginated($uid, $page, $limit, $startDate, $endDate, $categoryId, $searchText);
        
        ob_clean();
        // Devolvemos un objeto que incluye tanto los datos como la información de paginación
        echo json_encode(['success' => true, 'data' => $result['data'], 'total' => $result['total'], 'totals' => $result['totals']]);
        exit;
    }
    // ... (rest of the actions)

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
    elseif ($action === 'deleteMultiple') {
        $data = json_decode(file_get_contents("php://input"), true);
        $ids = $data['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $model->deleteMultiple($ids, $uid);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('No se proporcionaron IDs válidos para eliminar.');
        }
    }
    elseif ($action === 'updateCategoryMultiple') {
        $data = json_decode(file_get_contents("php://input"), true);
        $ids = $data['ids'] ?? [];
        $categoryId = !empty($data['categoria_id']) ? $data['categoria_id'] : null;

        if (!empty($ids) && is_array($ids)) {
            $model->updateCategoryMultiple($ids, $categoryId, $uid);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('No se proporcionaron IDs válidos para actualizar.');
        }
    }
    elseif ($action === 'reassignCategory') {
        $data = json_decode(file_get_contents("php://input"), true);
        $transactionId = $data['transactionId'] ?? null;
        $categoryId = !empty($data['categoryId']) ? $data['categoryId'] : null;

        if ($transactionId) {
            $result = $model->reassignCategory($transactionId, $categoryId, $uid);
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Fallo al reasignar la categoría en la base de datos.');
            }
        } else {
            throw new Exception('ID de transacción no proporcionado.');
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