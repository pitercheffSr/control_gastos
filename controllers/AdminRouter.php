<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../models/AdminModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    if (!isset($_SESSION['usuario_id'])) { 
        throw new Exception('No autorizado.');
    }

    $uid = $_SESSION['usuario_id'];
    
    // Verificación de máxima seguridad: ¿Es realmente un administrador?
    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    
    if (!$user || $user['rol'] !== 'admin') {
        throw new Exception('Acceso denegado. Permisos insuficientes.');
    }

    $action = $_GET['action'] ?? '';
    $model = new AdminModel($pdo);

    if ($action === 'getAll') {
        echo json_encode($model->getAllUsers());
    } 
    elseif ($action === 'delete') {
        $data = json_decode(file_get_contents("php://input"), true);
        $idBorrar = $data['id'] ?? null;
        
        if ($idBorrar && $idBorrar != $uid) { // Protección para no borrarte a ti mismo por error
            $model->deleteUser($idBorrar);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('ID inválido o intento de auto-eliminación.');
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