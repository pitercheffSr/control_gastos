<?php
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

// --- REGLA DE ORO 2: Límite de Tamaño (Máx 2MB) ---
$maxSize = 2 * 1024 * 1024; // 2 Megabytes
if (strlen($csv_data) > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'El archivo supera el límite máximo de 2MB.']);
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

// --- REGLA DE ORO 4: Leer de forma segura sin ejecutar ---
$transacciones = [];
$handle = fopen('php://memory', 'rw');
fwrite($handle, $csv_data);
rewind($handle);

if ($handle !== false) {
    // --- DETECTAR DELIMITADOR AUTOMÁTICAMENTE ---
    $delimiter = ',';
    $firstLine = fgets($handle);
    if ($firstLine !== false) {
        // Si hay más puntos y comas que comas, asumimos que es el delimitador
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        }
    }
    rewind($handle);

    $bloqueActual = 'DESCONOCIDO';
    
    // Leemos línea por línea con fgetcsv y el delimitador detectado
    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
        // Convertir la fila a UTF-8 (los bancos suelen usar ISO-8859-1) para evitar que json_encode falle silenciosamente
        foreach ($data as $key => $val) {
            $data[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        // Limpiar BOM (Byte Order Mark) oculto en el primer carácter si existe
        if (isset($data[0])) {
            $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
        }

        if (count($data) < 2) continue; // Omitir líneas vacías
        
        // Detector de cabeceras (Formato de banco específico)
        if (count($data) >= 3 && strpos($data[0], 'Fecha') !== false && strpos($data[1] ?? '', 'Descripci') !== false) {
            $bloqueActual = 'PENDIENTES';
            continue;
        } else if (count($data) >= 4 && strpos($data[0], 'Fecha contable') !== false && strpos($data[2] ?? '', 'Descripci') !== false) {
            $bloqueActual = 'CONSOLIDADOS';
            continue;
        }
        
        $fecha_raw = trim($data[0]);
        
        // Regex flexible: Soporta DD/MM/YYYY, DD-MM-YYYY, YYYY/MM/DD, YYYY-MM-DD (2 o 4 dígitos de año)
        if (preg_match('/^(\d{2,4})[-\/](\d{2})[-\/](\d{2,4})$/', $fecha_raw, $matches)) {
            
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

            if (strlen($matches[1]) === 4) {
                $fechaFormateada = $matches[1] . '-' . $matches[2] . '-' . $matches[3]; // YYYY-MM-DD
            } else {
                $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
                $fechaFormateada = $year . '-' . $matches[2] . '-' . $matches[1]; // DD-MM-YYYY -> YYYY-MM-DD
            }

            // Parsear Importe inteligente para divisas europeas/estadounidenses
            $clean = preg_replace('/[^-0-9.,]/', '', $importe_raw);
            $commaPos = strrpos($clean, ',');
            $dotPos = strrpos($clean, '.');
            if ($commaPos !== false && $dotPos !== false) {
                if ($commaPos > $dotPos) { $clean = str_replace('.', '', $clean); $clean = str_replace(',', '.', $clean); }
                else { $clean = str_replace(',', '', $clean); }
            } elseif ($commaPos !== false) { $clean = str_replace(',', '.', $clean); }
            $importe = (float) $clean;
            
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