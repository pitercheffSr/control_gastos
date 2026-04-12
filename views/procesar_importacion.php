<?php
session_start();
require_once '../config.php';
require_once '../models/CategoriaModel.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Verificación de Sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}

$uid = $_SESSION['usuario_id'];

// 2. Verificación de Petición y Archivo
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo_csv'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió ningún archivo.']);
    exit;
}

$file = $_FILES['archivo_csv'];

// --- REGLA DE ORO 1: Validar Errores de Subida ---
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Error al subir el archivo. Código: ' . $file['error']]);
    exit;
}

// --- REGLA DE ORO 2: Límite de Tamaño (Máx 2MB) ---
$maxSize = 2 * 1024 * 1024; // 2 Megabytes
if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'El archivo supera el límite máximo de 2MB.']);
    exit;
}

// --- REGLA DE ORO 3: Validar Extensión y MIME Type ---
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['status' => 'error', 'message' => 'El archivo debe tener extensión .csv']);
    exit;
}

$mime = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
}

// Algunos sistemas o bancos generan CSV que se detectan de estas formas
$allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['status' => 'error', 'message' => "El tipo de archivo no es válido ($mime). Solo se permiten CSV."]);
    exit;
}

// Obtener categorías disponibles para pasarlas al Frontend (para el select)
$catModel = new CategoriaModel($pdo);
$categoriasRaw = $catModel->getAll($uid);
$categoriasDisponibles = [];
foreach ($categoriasRaw as $c) {
    $categoriasDisponibles[] = ['id' => $c['id'], 'nombre' => $c['nombre']];
}

// --- REGLA DE ORO 4: Leer de forma segura sin ejecutar ---
$transacciones = [];
$handle = fopen($file['tmp_name'], 'r'); // Modo solo lectura temporal

if ($handle !== false) {
    $rowCounter = 0;
    
    // Leemos línea por línea con fgetcsv (NUNCA usar include/require)
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        // Detectar si el CSV usa punto y coma como separador (muy común en Europa/España)
        if (count($data) === 1 && strpos($data[0], ';') !== false) {
            $data = explode(';', $data[0]);
        }
        
        $rowCounter++;
        
        // Omitir cabeceras si existen (heurística: la primera columna no parece fecha ni número)
        if ($rowCounter === 1 && !preg_match('/^[0-9]{2,4}[-\/][0-9]{2}/', $data[0]) && !is_numeric($data[0])) {
            continue; 
        }
        
        // Asumimos un formato estándar bancario básico:
        // [0] Fecha, [1] Concepto/Descripción, [2] Importe
        $fechaRaw = trim($data[0] ?? '');
        $descripcion = trim($data[1] ?? '');
        $importeRaw = trim($data[2] ?? '');
        
        // Si las columnas parecen correctas, las parseamos
        if (!empty($fechaRaw) && !empty($descripcion) && $importeRaw !== '') {
            // Formatear Fecha (Soporta DD/MM/YYYY o YYYY-MM-DD)
            $fechaFormateada = date('Y-m-d', strtotime(str_replace('/', '-', $fechaRaw)));
            
            // Parsear Importe con lógica inteligente para divisas europeas/estadounidenses
            $clean = preg_replace('/[^-0-9.,]/', '', $importeRaw);
            $commaPos = strrpos($clean, ',');
            $dotPos = strrpos($clean, '.');
            if ($commaPos !== false && $dotPos !== false) {
                if ($commaPos > $dotPos) { $clean = str_replace('.', '', $clean); $clean = str_replace(',', '.', $clean); } // 1.500,25
                else { $clean = str_replace(',', '', $clean); } // 1,500.25
            } elseif ($commaPos !== false) { $clean = str_replace(',', '.', $clean); } // 1500,25
            
            $transacciones[] = [
                'fecha' => $fechaFormateada,
                'descripcion' => substr($descripcion, 0, 255), // Límite de seguridad
                'importe' => (float) $clean,
                'categoria_id' => null // Pendiente de que el usuario lo clasifique en el front
            ];
        }
    }
    fclose($handle);
}

if (empty($transacciones)) {
    echo json_encode(['status' => 'error', 'message' => 'No se encontraron movimientos válidos. Asegúrate de que las columnas sean: Fecha, Descripción, Importe.']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'data' => $transacciones,
    'categorias_disponibles' => $categoriasDisponibles
]);
exit;