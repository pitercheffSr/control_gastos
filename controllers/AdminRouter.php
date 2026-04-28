<?php
require_once '../config.php';
require_once '../models/AdminModel.php';

require_once '../controllers/AuthController.php'; // Necesitamos AuthController para verificar la contraseña
require_once '../AuthMiddleware.php';
header('Content-Type: application/json');

// --- Seguridad: Middleware para administradores ---
$uid = AuthMiddleware::checkAdmin($pdo);
AuthMiddleware::checkCSRF();

$action = $_GET['action'] ?? '';

$authController = new AuthController($pdo); // Instanciamos el AuthController
$model = new AdminModel($pdo);

switch ($action) {
    case 'getAll':
        $users = $model->getAllUsers();
        echo json_encode($users);
        break;

    case 'updateUser':
        $input = json_decode(file_get_contents('php://input'), true);

        // 1. Validamos todos los datos requeridos de una sola vez
        $validData = AuthMiddleware::validateInput($input, [
            'admin_password' => 'required',
            'id' => 'required|numeric',
            'nombre' => 'required',
            'email' => 'required|email',
            'rol' => 'required'
        ]);

        if (!$authController->verifyPasswordForUser($_SESSION['usuario_id'], $validData['admin_password'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
            if ($stmt->execute([$validData['nombre'], $validData['email'], $validData['rol'], $validData['id']])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el usuario.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
        }
        break;

    case 'resetUserPassword':
        $input = json_decode(file_get_contents('php://input'), true);

        $validData = AuthMiddleware::validateInput($input, [
            'admin_password' => 'required',
            'id' => 'required|numeric',
            'new_password' => 'required|min:6'
        ]);

        // Verificamos por seguridad que el administrador es legítimo
        if (!$authController->verifyPasswordForUser($_SESSION['usuario_id'], $validData['admin_password'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta.']);
            exit;
        }

        try {
            if ($authController->updatePassword($validData['id'], $validData['new_password'])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'No se pudo actualizar la contraseña.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
        }
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
