<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/TransaccionModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario_id'])) {
    die("No autorizado");
}

$uid = $_SESSION['usuario_id'];
$model = new TransaccionModel($pdo);
$transacciones = $model->getAll($uid);

// Limpiamos cualquier texto basura previo
if (ob_get_length()) ob_clean();

// Cabeceras universales para la descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mis_movimientos_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// BOM (Byte Order Mark): Excel lo necesita para los acentos. Libre/OpenOffice lo detecta y lo procesa de forma nativa.
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Escribimos la primera fila forzando el encapsulado con comillas dobles (")
fputcsv($output, ['Fecha', 'Descripción', 'Categoría', 'Importe'], ';', '"');

// Recorremos todos los movimientos
foreach ($transacciones as $t) {
    $catNombre = $t['categoria_nombre'] ?? 'Sin categoría';
    $importe = isset($t['importe']) ? (float)$t['importe'] : 0;
    
    // Formateamos: dd/mm/yyyy y coma para los decimales
    $fechaFmt = date('d/m/Y', strtotime($t['fecha']));
    $importeFmt = number_format($importe, 2, ',', '');

    // Escribimos la fila asegurando la compatibilidad
    fputcsv($output, [
        $fechaFmt,
        $t['descripcion'],
        $catNombre,
        $importeFmt
    ], ';', '"');
}

fclose($output);
exit;