<?php
// db.php

$DB_HOST = 'localhost';
$DB_NAME = 'bd_503020';
$DB_USER = 'cg_user';
$DB_PASS = 'cg_pass';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

try {
	$conn = new PDO($dsn, $DB_USER, $DB_PASS, [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	]);

	// Asignamos a $pdo para mantener compatibilidad con el resto del sistema
	$pdo = $conn;
} catch (PDOException $e) {
	// Detectar si la petición espera JSON (útil para que el dashboard no se cuelgue si falla la BD)
	$esApi = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
		|| (isset($_GET['action']));

	if ($esApi) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'ok' => false,
			'error' => 'Error fatal de conexión BD: ' . $e->getMessage()
		]);
		exit;
	}

	if (isset($_GET['debug'])) {
		die("Error BD: " . $e->getMessage());
	}
	die("Error de conexión a la base de datos.");
}
