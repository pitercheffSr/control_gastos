<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    // Usamos __DIR__ para rutas más robustas, independientemente de dónde se llame el script.
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../models/TransaccionModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['usuario_id'])) { throw new Exception('No autorizado'); }

    $uid = $_SESSION['usuario_id'];
    $action = $_GET['action'] ?? '';
    $model = new TransaccionModel($pdo);

    // --- PROTECCIÓN CSRF PARA RUTAS QUE MODIFICAN DATOS ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Intentar leer el token del cuerpo si la petición es JSON
        $inputData = json_decode(file_get_contents('php://input'), true);
        $jsonToken = is_array($inputData) ? ($inputData['csrf_token'] ?? '') : '';
        
        $csrfToken = trim((string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? $jsonToken));
        $sessionToken = trim((string)($_SESSION['csrf_token'] ?? ''));
        
        $isSameOrigin = false;
        if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && $_SERVER['HTTP_SEC_FETCH_SITE'] === 'same-origin') {
            $isSameOrigin = true;
        } elseif (isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
            $isSameOrigin = true;
        }

        // Si el navegador NO puede confirmar que es nuestra propia web, exigimos el token estricto
        if (!$isSameOrigin) {
            if (empty($sessionToken)) {
                http_response_code(401);
                throw new Exception('Sesión expirada o cookies bloqueadas por el navegador.');
            }
            if (empty($csrfToken) || !hash_equals($sessionToken, $csrfToken)) {
                http_response_code(403);
                throw new Exception('Token de seguridad ausente o inválido. Refresca la página.');
            }
        }
    }

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
        $importe = $data['monto'] ?? $data['importe'] ?? 0; // Acepta 'monto' desde JS
        $fecha = $data['fecha'] ?? date('Y-m-d');
        $categoria_id = !empty($data['categoria_id']) ? $data['categoria_id'] : null;

        if ($id) {
            // Si hay ID, actualizamos (parámetros en orden correcto)
            $model->update($id, $uid, $categoria_id, $fecha, $descripcion, $importe);
        } else {
            // Si no hay ID, creamos uno nuevo (parámetros en orden correcto)
            $model->create($uid, $categoria_id, $fecha, $descripcion, $importe);
        }
        
        // Limpiamos a la fuerza cualquier basura oculta en el buffer antes de responder
        ob_clean(); 
        echo json_encode(['success' => true]);
        exit;
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
        $limit = (int)($_GET['limit'] ?? 50); // Aumentamos el límite por defecto
        $startDate = $_GET['startDate'] ?? null;
        $endDate = $_GET['endDate'] ?? null;
        $categoryId = $_GET['categoryId'] ?? null;
        $searchText = $_GET['searchText'] ?? null;
        $sortBy = $_GET['sortBy'] ?? 'fecha';
        $sortOrder = $_GET['sortOrder'] ?? 'DESC';
        $tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['ingreso', 'gasto']) ? $_GET['tipo'] : null;

        $result = $model->getPaginated($uid, $page, $limit, $startDate, $endDate, $categoryId, $searchText, $sortBy, $sortOrder, $tipo);
        
        ob_clean();
        // Devolvemos un objeto que incluye tanto los datos como la información de paginación
        echo json_encode(['success' => true, 'data' => $result['data'], 'total' => $result['total']]);
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
    elseif ($action === 'deleteMultiple') {
        $data = json_decode(file_get_contents("php://input"), true);
        $ids = $data['ids'] ?? [];
        
        if (!empty($ids) && is_array($ids)) {
            $model->deleteMultiple($ids, $uid);
            ob_clean();
            echo json_encode(['success' => true]);
            exit;
        } else {
            throw new Exception('No se proporcionaron IDs válidos.');
        }
    }
    elseif ($action === 'updateCategoryMultiple') {
        $data = json_decode(file_get_contents("php://input"), true);
        $ids = $data['ids'] ?? [];
        $categoryId = $data['categoria_id'] ?? null;
        
        if (!empty($ids) && is_array($ids)) {
            $model->updateCategoryMultiple($ids, $categoryId, $uid);
            ob_clean();
            echo json_encode(['success' => true]);
            exit;
        } else {
            throw new Exception('No se proporcionaron IDs válidos.');
        }
    }
    elseif ($action === 'saveBulk') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data) || !is_array($data)) {
            throw new Exception('No se recibieron datos o el formato es incorrecto.');
        }
        $result = $model->createBulk($uid, $data);
        ob_clean();
        echo json_encode(['success' => true, 'inserted' => $result['inserted'], 'skipped' => $result['skipped']]);
        exit;
    }
    elseif ($action === 'reassignCategory') {
        $data = json_decode(file_get_contents("php://input"), true);
        $transactionId = $data['transactionId'] ?? null;
        $categoryId = $data['categoryId'] ?? null;

        if ($transactionId) {
            $model->reassignCategory($transactionId, $categoryId, $uid);
            ob_clean();
            echo json_encode(['success' => true]);
            exit;
        } else {
            throw new Exception('ID de transacción inválido');
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