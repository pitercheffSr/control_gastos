<?php
require_once '../config.php';
require_once '../models/CategoriaModel.php';
require_once '../services/ImportacionService.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

$uid = $_SESSION['usuario_id'];

// 2. Verificación de Petición y Archivo
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input['csv_data'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió ningún dato de archivo válido.']);
    exit;
}

// NOTA DE SEGURIDAD:
// Hemos eliminado la validación CSRF estricta en este archivo específico porque
// procesar (leer) un CSV NO modifica la base de datos, solo devuelve una vista previa en memoria.
// La verdadera protección CSRF se aplica al pulsar "Guardar Todos los Movimientos"
// en TransaccionRouter.php (saveBulk), que es donde realmente se altera la base de datos.

$csv_data = $input['csv_data'];
$file_name = $input['file_name'] ?? 'archivo.csv';

// --- REGLA DE ORO 2: Límite de Tamaño (Máx 10MB) ---
$maxSize = 10 * 1024 * 1024; // 10 Megabytes
if (strlen($csv_data) > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'El archivo supera el límite máximo de 10MB.']);
    exit;
}

$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['status' => 'error', 'message' => 'El archivo debe tener extensión .csv']);
    exit;
}

// Obtener categorías disponibles para pasarlas al Frontend (para el select)
$catModel = new CategoriaModel($pdo);
$categoriasRaw = $catModel->getAll($uid);
$categoriasDisponibles = [];
foreach ($categoriasRaw as $c) {
    $categoriasDisponibles[] = ['id' => $c['id'], 'nombre' => $c['nombre']];
}

// 3. Procesar el CSV delegando la lógica al Servicio
try {
    $importacionService = new ImportacionService();
    $transacciones = $importacionService->procesarCsv($csv_data, $categoriasRaw);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

if (empty($transacciones)) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontraron movimientos válidos. Asegúrate de que las columnas sean: Fecha, Descripción, Importe.']);
    exit;
}

$json_output = json_encode([
    'status' => 'success',
    'data' => $transacciones,
    'categorias_disponibles' => $categoriasDisponibles
]);

if ($json_output === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al procesar el texto: Tu CSV contiene caracteres no reconocidos.']);
    exit;
}

echo $json_output;
exit;
