<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/CategoriaModel.php';
    require_once '../AuthMiddleware.php';

    $uid = AuthMiddleware::checkAPI();
    AuthMiddleware::checkCSRF();

    $action = $_GET['action'] ?? '';
    $model = new CategoriaModel($pdo);

    if ($action === 'getAll') {
        echo json_encode($model->getAll($uid));
    }
    elseif ($action === 'save') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, [
            'id' => 'numeric', // Opcional para creación
            'nombre' => 'required',
            'tipo_fijo' => '', // Solo para extraerlo y limpiarlo
            'parent_id' => 'numeric' // Opcional
        ]);

        $tipo = !empty($validData['tipo_fijo']) ? $validData['tipo_fijo'] : 'gasto';

        if (!empty($validData['id'])) {
            $model->update($validData['id'], $uid, $validData['nombre'], $tipo, $validData['parent_id']);
        } else {
            $model->create($uid, $validData['nombre'], $tipo, $validData['parent_id']);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, ['id' => 'required|numeric']);

        $model->delete($validData['id'], $uid);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'updateOrder') {
        $input = json_decode(file_get_contents("php://input"), true);
        $validData = AuthMiddleware::validateInput($input, [
            'movedId' => 'required|numeric',
            'newParentId' => 'numeric',
            'siblingIds' => 'required|array'
        ]);

        $model->updateOrder($validData['movedId'], $validData['newParentId'], $validData['siblingIds'], $uid);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'getTotals') {
        $startDate = $_GET['startDate'] ?? date('Y-m-01');
        $endDate = $_GET['endDate'] ?? date('Y-m-t');

        $totals = $model->getTotalsRecursive($uid, $startDate, $endDate);

        echo json_encode(['success' => true, 'totals' => $totals]);
    } else {
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
