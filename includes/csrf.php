<?php

function validar_csrf(): void
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'Sesión no iniciada']);
		exit;
	}

	// 1️⃣ Token enviado por HEADER (fetch moderno)
	$headers = array_change_key_case(getallheaders(), CASE_LOWER);
	$token = $headers['x-csrf-token'] ?? null;

	// 2️⃣ Fallback (por si algún día usas form POST clásico)
	if (!$token) {
		$token = $_POST['csrf_token'] ?? null;
	}

	// 3️⃣ Validación estricta
	if (
		empty($token) ||
		empty($_SESSION['csrf_token']) ||
		!hash_equals($_SESSION['csrf_token'], $token)
	) {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'CSRF token inválido']);
		exit;
	}
}
