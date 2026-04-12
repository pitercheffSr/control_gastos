<?php
session_start();
require_once '../config.php';
require_once '../models/CategoriaModel.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Verificación de Sesión
// Función Helper: Quita mayúsculas y acentos para que las búsquedas no fallen
function limpiarTexto($texto) {
    $texto = strtolower(trim($texto));
    $buscar  = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'];
    $reemplazar = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'];
    return str_replace($buscar, $reemplazar, $texto);
}

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

// --- PROTECCIÓN CSRF PARA LA IMPORTACIÓN AJAX ---
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF ausente o inválido. Recarga la página.']);
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
    $bloqueActual = 'DESCONOCIDO';
    
    // Leemos línea por línea con fgetcsv (NUNCA usar include/require)
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        // Detectar si el CSV usa punto y coma como separador (muy común en Europa/España)
        if (count($data) === 1 && strpos($data[0], ';') !== false) {
            $data = explode(';', $data[0]);
        }
        
        // Detector de cabeceras (Formato de banco específico)
        if (count($data) >= 3 && $data[0] === 'Fecha' && strpos($data[1], 'Descripci') !== false) {
            $bloqueActual = 'PENDIENTES';
            continue;
        } else if (count($data) >= 4 && $data[0] === 'Fecha contable' && strpos($data[2], 'Descripci') !== false) {
            $bloqueActual = 'CONSOLIDADOS';
            continue;
        }
        
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim($data[0]))) {
            $fecha_raw = trim($data[0]);
            
            if ($bloqueActual === 'PENDIENTES') {
                $concepto = trim($data[1] ?? '');
                $importe_raw = trim($data[2] ?? '');
            } else if ($bloqueActual === 'CONSOLIDADOS') {
                $concepto = trim($data[2] ?? '');
                $importe_raw = trim($data[3] ?? '');
            } else {
                $concepto = trim($data[1] ?? '');
                $importe_raw = trim($data[2] ?? '');
            }

            if(empty($concepto)) {
                $concepto = "Movimiento bancario";
            }

            $partes_fecha = explode('/', $fecha_raw);
            $fechaFormateada = $partes_fecha[2] . '-' . $partes_fecha[1] . '-' . $partes_fecha[0];

            $importe_str = str_replace('.', '', $importe_raw);
            $importe_str = str_replace(',', '.', $importe_str);
            $importe = (float) $importe_str;
            
            // --- MAGIA DE AUTO-CATEGORIZACIÓN ---
            $categoria_final = null; 
            $conceptoLimpio = limpiarTexto($concepto);

            foreach ($categoriasRaw as $cat) {
                $nombreCat = $cat['nombre'];
                $encontrado = false;

                if (preg_match('/\((.*?)\)/', $nombreCat, $coincidencias)) {
                    $palabrasClave = explode(',', $coincidencias[1]);
                    
                    foreach ($palabrasClave as $palabra) {
                        $palabraLimpia = limpiarTexto($palabra);
                        if (!empty($palabraLimpia)) {
                            $patron = '/(^|[^a-z0-9])' . preg_quote($palabraLimpia, '/') . '([^a-z0-9]|$)/i';
                            if (preg_match($patron, $conceptoLimpio)) {
                                $encontrado = true;
                                break; 
                            }
                        }
                    }
                }

                if (!$encontrado) {
                    $nombreBase = trim(preg_replace('/\((.*?)\)/', '', $nombreCat));
                    $nombreBaseLimpio = limpiarTexto($nombreBase);
                    if (!empty($nombreBaseLimpio)) {
                        $patron = '/(^|[^a-z0-9])' . preg_quote($nombreBaseLimpio, '/') . '([^a-z0-9]|$)/i';
                        if (preg_match($patron, $conceptoLimpio)) {
                            $encontrado = true;
                        }
                    }
                }

                if ($encontrado) {
                    $categoria_final = $cat['id'];
                    break;
                }
            }

            $transacciones[] = [
                'fecha' => $fechaFormateada,
                'descripcion' => substr($concepto, 0, 255),
                'importe' => $importe,
                'categoria_id' => $categoria_final
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