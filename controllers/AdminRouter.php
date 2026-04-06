<?php
require_once '../config.php';
require_once '../models/AdminModel.php';

require_once '../controllers/AuthController.php'; // Necesitamos AuthController para verificar la contraseña
header('Content-Type: application/json');

// --- Seguridad: Solo para administradores ---
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}

$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$_SESSION['usuario_id']]);
$userRole = $stmtUser->fetchColumn();

if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Se requiere rol de administrador.']);
    exit;
}
// --- Fin de la seguridad ---

$action = $_GET['action'] ?? '';
$authController = new AuthController($pdo); // Instanciamos el AuthController
$model = new AdminModel($pdo);

switch ($action) {
    case 'getAll':
        $users = $model->getAllUsers();
        echo json_encode($users);
        break;

    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $adminPassword = $input['admin_password'] ?? null;

        if (!$authController->verifyPasswordForUser($_SESSION['usuario_id'], $adminPassword)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
            exit;
        }
        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado.']);
            exit;
        }

        if ($model->deleteUser($id)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el usuario.']);
        }
        break;

    case 'deleteAllTransactions':
        $input = json_decode(file_get_contents('php://input'), true);
        $adminPassword = $input['admin_password'] ?? null;

        if (!$authController->verifyPasswordForUser($_SESSION['usuario_id'], $adminPassword)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado.']);
            exit;
        }

        if ($model->deleteAllTransactionsForUser($id)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudieron eliminar las transacciones.']);
        }
        break;

    case 'deleteAllNonAdmins':
        $input = json_decode(file_get_contents('php://input'), true); // Necesitamos leer el body para la contraseña
        $adminPassword = $input['admin_password'] ?? null;

        if (!$authController->verifyPasswordForUser($_SESSION['usuario_id'], $adminPassword)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
            exit;
        }
        if ($model->deleteAllNonAdminUsers()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudieron eliminar los usuarios.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no válida.']);
        break;
}