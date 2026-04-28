<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    // Usamos __DIR__ para rutas más robustas, independientemente de dónde se llame el script.
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../models/TransaccionModel.php';
    require_once __DIR__ . '/../AuthMiddleware.php';

    $uid = AuthMiddleware::checkAPI();
    AuthMiddleware::checkCSRF();

    $action = $_GET['action'] ?? '';
    $model = new TransaccionModel($pdo);

    if ($action === 'getAllLimit') {
        echo json_encode($model->getAllLimit($uid, 10));
    }
    elseif ($action === 'getAll') {
        echo json_encode($model->getAll($uid));
    }
    elseif ($action === 'save') {
        $input = json_decode(file_get_contents("php://input"), true);

        // Pre-procesar campos alternativos o por defecto antes de la validación
        $input['importe'] = $input['monto'] ?? $input['importe'] ?? 0;
        if (empty($input['fecha'])) {
            $input['fecha'] = date('Y-m-d');
        }

        $validData = AuthMiddleware::validateInput($input, [
            'id' => 'numeric',
            'descripcion' => 'max:255', // Valida la longitud máxima permitida
            'importe' => 'required|numeric',
            'fecha' => 'required|date',
            'categoria_id' => 'numeric'
        ]);

        if (!empty($validData['id'])) {
            // Si hay ID, actualizamos (parámetros en orden correcto)
            $model->update($validData['id'], $uid, $validData['categoria_id'] ?? null, $validData['fecha'], $validData['descripcion'] ?? '', $validData['importe']);
        } else {
            // Si no hay ID, creamos uno nuevo (parámetros en orden correcto)
            $model->create($uid, $validData['categoria_id'] ?? null, $validData['fecha'], $validData['descripcion'] ?? '', $validData['importe']);
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
        // Validar variables GET, incluyendo las fechas
        $validData = AuthMiddleware::validateInput($_GET, [
            'page' => 'numeric',
            'limit' => 'numeric',
            'startDate' => 'date', // Valida formato pero permite null si no se envía
            'endDate' => 'date'
        ]);

        $page = (int)($validData['page'] ?? 1);
        $limit = (int)($validData['limit'] ?? 50); // Aumentamos el límite por defecto
        $startDate = $validData['startDate'] ?? null;
        $endDate = $validData['endDate'] ?? null;
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
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, ['id' => 'required|numeric']);

        $model->delete($validData['id'], $uid);
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }
    elseif ($action === 'deleteMultiple') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, ['ids' => 'required|array']);

        $model->deleteMultiple($validData['ids'], $uid);
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }
    elseif ($action === 'updateCategoryMultiple') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, [
            'ids' => 'required|array',
            'categoria_id' => 'numeric' // Opcional, puede ser null
        ]);

        $model->updateCategoryMultiple($validData['ids'], $validData['categoria_id'] ?? null, $uid);
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }
    elseif ($action === 'saveBulk') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data) || !is_array($data)) {
            throw new Exception('No se recibieron datos o el formato es incorrecto.');
        }

        // Validar cada transacción del lote asegurando el formato de fecha
        $validData = [];
        foreach ($data as $trx) {
            $validData[] = AuthMiddleware::validateInput($trx, [
                'descripcion' => 'required|max:255',
                'importe' => 'required|numeric',
                'fecha' => 'required|date',
                'categoria_id' => 'numeric'
            ]);
        }

        $result = $model->createBulk($uid, $validData);
        ob_clean();
        echo json_encode(['success' => true, 'inserted' => $result['inserted'], 'skipped' => $result['skipped']]);
        exit;
    }
    elseif ($action === 'reassignCategory') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, [
            'transactionId' => 'required|numeric',
            'categoryId' => 'numeric'
        ]);

        $model->reassignCategory($validData['transactionId'], $validData['categoryId'] ?? null, $uid);
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    }
    elseif ($action === 'autoClassify') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, ['ids' => 'array']); // Array opcional

        require_once __DIR__ . '/../models/CategoriaModel.php';
        $catModel = new CategoriaModel($pdo);
        $categorias = $catModel->getAll($uid);

        $updatedCount = $model->autoClassify($validData['ids'] ?? [], $uid, $categorias);

        ob_clean(); echo json_encode(['success' => true, 'updated' => $updatedCount]); exit;
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
