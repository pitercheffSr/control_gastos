<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../db.php';
$id_usuario = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo_csv'])) {
    echo json_encode(['status' => 'error', 'message' => 'Petición inválida.']);
    exit;
}

$file = $_FILES['archivo_csv'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Error al subir el archivo.']);
    exit;
}

$filePath = $file['tmp_name'];

// --- INICIO DE VALIDACIONES DE ARCHIVO ---
$max_size = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'El archivo es demasiado grande. El límite es 5 MB.']);
    exit;
}

$allowed_mime_types = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filePath);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_mime_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo de archivo no válido (' . htmlspecialchars($mime_type) . '). Por favor, sube un archivo CSV.']);
    exit;
}
// --- FIN DE VALIDACIONES DE ARCHIVO ---

try {
    // --- INICIO DE LA LÓGICA Y FUNCIONES HELPER ---

    /**
     * Parsea una cadena de moneda a un float, manejando formatos europeos comunes.
     */
    $parseAmount = function(string $amountStr): float {
        $cleanStr = str_replace('.', '', $amountStr); // Quita separadores de miles
        $cleanStr = str_replace(',', '.', $cleanStr); // Reemplaza coma decimal
        return (float) $cleanStr;
    };

    /**
     * Parsea una cadena de fecha a 'Y-m-d' desde formatos comunes.
     */
    $parseDate = function(string $dateStr): string {
        $dateStr = trim($dateStr);
        $formats = ['d/m/Y', 'Y-m-d'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateStr);
            if ($date && $date->format($format) === $dateStr) {
                return $date->format('Y-m-d');
            }
        }
        $timestamp = strtotime($dateStr);
        return $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
    };

    /**
     * Encuentra la mejor categoría para una descripción usando coincidencia de palabra completa.
     */
    $findCategoryForDescription = function(string $description, array $categories): ?int {
        foreach ($categories as $category) {
            $pattern = '/\b' . preg_quote($category['nombre'], '/') . '\b/i';
            if (preg_match($pattern, $description)) {
                return (int) $category['id'];
            }
        }
        return null;
    };

    // 1. LEER los conceptos específicos (subcategorías) del usuario para una clasificación precisa.
    // Se omiten las categorías genéricas (Necesidades, etc.) porque no aparecen en las descripciones bancarias.
    $stmt = $pdo->prepare("SELECT id, nombre FROM subcategorias WHERE id_usuario = :id_usuario ORDER BY LENGTH(nombre) DESC");
    $stmt->execute(['id_usuario' => $id_usuario]);
    $userConcepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. ABRIR Y PROCESAR EL CSV
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo CSV.');
    }

    // 3. LEER CABECERA Y VALIDAR DATOS SENSIBLES
    $header = fgetcsv($handle);
    if ($header === false) {
        throw new Exception('El archivo CSV está vacío o no se pudo leer la cabecera.');
    }
    $header_lower = array_map('strtolower', $header);

    $palabras_prohibidas = ['cuenta', 'iban', 'cbu', 'clabe', 'account', 'number'];
    foreach ($header_lower as $col) {
        foreach ($palabras_prohibidas as $palabra) {
            if (strpos(trim($col), $palabra) !== false) {
                throw new Exception('Por seguridad, se ha detectado una columna potencialmente sensible ("' . htmlspecialchars($col) . '"). Por favor, elimínala del archivo y vuelve a intentarlo.');
            }
        }
    }

    // 4. MAPEAR COLUMNAS (Fecha, Descripción, Importe)
    $dateIndex = array_search('fecha', $header_lower);
    $descIndex = array_search('descripción', $header_lower) ?: array_search('descripcion', $header_lower);
    $amountIndex = array_search('importe', $header_lower) ?: array_search('monto', $header_lower);

    if ($dateIndex === false || $descIndex === false || $amountIndex === false) {
        throw new Exception('El archivo CSV debe contener las columnas "Fecha", "Descripción" y "Importe".');
    }

    // 5. LEER FILAS Y AUTO-CLASIFICAR
    $transacciones = [];
    while (($row = fgetcsv($handle)) !== false) {
        // Validar que la fila tenga el número esperado de columnas
        if (count($row) < max($dateIndex, $descIndex, $amountIndex) + 1) {
            continue; // Saltar fila malformada
        }

        $descripcion = trim($row[$descIndex]);
        $importe = $parseAmount($row[$amountIndex]);
        $fecha = $parseDate($row[$dateIndex]);

        if (empty($descripcion) || empty($importe)) {
            continue; // Saltar filas vacías
        }

        // Algoritmo de auto-clasificación mejorado
        $categoria_sugerida_id = $findCategoryForDescription($descripcion, $userConcepts);

        $transacciones[] = [
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'importe' => $importe,
            'categoria_id' => $categoria_sugerida_id
        ];
    }
    fclose($handle);

    // 7. DEVOLVER RESULTADO
    echo json_encode([
        'status' => 'success',
        'data' => $transacciones,
        'categorias_disponibles' => $userConcepts // Se envían los datos crudos, no HTML
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
