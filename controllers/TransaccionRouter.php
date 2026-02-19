<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/TransaccionesController.php';

$uid = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$uid) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$controller = new TransaccionesController($pdo);
$controller->manejarPeticion($uid);