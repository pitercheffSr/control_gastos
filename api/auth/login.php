<?php

// api/auth/login.php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

// Accept JSON body as well
if (!$email) {
    $body = json_decode(file_get_contents('php://input'), true);
    if ($body) {
        $email = $body['email'] ?? $email;
        $password = $body['password'] ?? $password;
    }
}

if (!$email || !$password) {
    echo json_encode(['ok' => false,'error' => 'Faltan credenciales']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        echo json_encode(['ok' => false,'error' => 'Email no encontrado']);
        exit;
    }
    if (!password_verify($password, $u['password'])) {
        echo json_encode(['ok' => false,'error' => 'Contraseña incorrecta']);
        exit;
    }
    // login success
    $_SESSION['usuario_id'] = $u['id'];
    $_SESSION['usuario_nombre'] = $u['nombre'] ?? '';
    echo json_encode(['ok' => true, 'id' => $u['id']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false,'error' => $e->getMessage()]);
}
