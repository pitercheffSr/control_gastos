<?php
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once '../config.php';
    require_once '../models/TransaccionModel.php';

    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['usuario_id'])) { 
        http_response_code(403);
        die('Acceso no autorizado.');
    }

    $uid = $_SESSION['usuario_id'];
    $model = new TransaccionModel($pdo);

    // Recoger los mismos parámetros que el filtro de la tabla
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;
    $categoryId = $_GET['categoryId'] ?? null;
    $searchText = $_GET['searchText'] ?? null;
    $sortBy = $_GET['sortBy'] ?? 'fecha';
    $sortOrder = $_GET['sortOrder'] ?? 'DESC';

    // Obtener todos los datos filtrados, sin paginación
    $data = $model->getAllForExport($uid, $startDate, $endDate, $categoryId, $searchText, $sortBy, $sortOrder);

    $filename = "movimientos_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Escribir la cabecera del CSV
    fputcsv($output, ['Fecha', 'Descripción', 'Categoría', 'Importe']);

    // Escribir los datos
    foreach ($data as $row) {
        // Asegurarse de que el importe tenga el formato correcto para CSV (punto decimal)
        $row['importe'] = number_format($row['importe'], 2, '.', '');
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (\Throwable $e) {
    http_response_code(500);
    die("Error al generar el archivo de exportación.");
}