<?php
// controllers/ImportarRouter.php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: ../index.php'); 
    exit; 
}

$uid = $_SESSION['usuario_id'];

// Función Helper: Quita mayúsculas y acentos para que las búsquedas no fallen
function limpiarTexto($texto) {
    $texto = strtolower(trim($texto));
    $buscar  = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'];
    $reemplazar = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'];
    return str_replace($buscar, $reemplazar, $texto);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
    
    $categoria_defecto = $_POST['categoria_id'] ?? null;
    if (empty($categoria_defecto)) {
        header('Location: ../transacciones.php?mensaje=ErrorSinCategoria');
        exit;
    }

    $archivoTmp = $_FILES['archivo_csv']['tmp_name'];
    
    if (($handle = fopen($archivoTmp, "r")) !== FALSE) {
        
        $stmtCats = $pdo->prepare("SELECT id, nombre FROM categorias WHERE usuario_id = ?");
        $stmtCats->execute([$uid]);
        $categoriasUsuario = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        $delimitador = ";"; 
        $insertados = 0;
        $omitidos = 0;

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transacciones WHERE usuario_id = ? AND fecha = ? AND importe = ? AND descripcion = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, importe) VALUES (?, ?, ?, ?, ?)");

        $bloqueActual = 'DESCONOCIDO';

        while (($datos = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
            
            // Detector de cabeceras
            if (count($datos) >= 3 && $datos[0] === 'Fecha' && strpos($datos[1], 'Descripci') !== false) {
                $bloqueActual = 'PENDIENTES';
                continue;
            } else if (count($datos) >= 4 && $datos[0] === 'Fecha contable' && strpos($datos[2], 'Descripci') !== false) {
                $bloqueActual = 'CONSOLIDADOS';
                continue;
            }

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim($datos[0]))) {
                $fecha_raw = trim($datos[0]);
                
                if ($bloqueActual === 'PENDIENTES') {
                    $concepto = trim($datos[1]);
                    $importe_raw = trim($datos[2]);
                } else if ($bloqueActual === 'CONSOLIDADOS') {
                    $concepto = trim($datos[2]);
                    $importe_raw = trim($datos[3]);
                } else {
                    $concepto = trim($datos[1]);
                    $importe_raw = trim($datos[2]);
                }

                if(empty($concepto)) {
                    $concepto = "Movimiento bancario";
                }

                $partes_fecha = explode('/', $fecha_raw);
                $fecha = $partes_fecha[2] . '-' . $partes_fecha[1] . '-' . $partes_fecha[0];

                $importe_str = str_replace('.', '', $importe_raw);
                $importe_str = str_replace(',', '.', $importe_str);
                $importe = (float) $importe_str;
                
                $stmtCheck->execute([$uid, $fecha, $importe, $concepto]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $omitidos++;
                    continue; 
                }

                // --- LA NUEVA MAGIA: BÚSQUEDA POR PALABRAS EXACTAS ---
                $categoria_final = $categoria_defecto; 
                $conceptoLimpio = limpiarTexto($concepto);

                foreach ($categoriasUsuario as $cat) {
                    $nombreCat = $cat['nombre'];
                    $encontrado = false;

                    if (preg_match('/\((.*?)\)/', $nombreCat, $coincidencias)) {
                        $palabrasClave = explode(',', $coincidencias[1]);
                        
                        foreach ($palabrasClave as $palabra) {
                            $palabraLimpia = limpiarTexto($palabra);
                            if (!empty($palabraLimpia)) {
                                // Expresión regular: busca la palabra EXACTA aislada por espacios o símbolos
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
                            // Expresión regular: busca la palabra EXACTA aislada por espacios o símbolos
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

                $stmtInsert->execute([$uid, $categoria_final, $fecha, $concepto, $importe]);
                $insertados++;
            }
        }
        
        fclose($handle);
        
        header('Location: ../transacciones.php?importados=' . $insertados . '&omitidos=' . $omitidos);
        exit;
    } else {
         header('Location: ../transacciones.php?mensaje=ErrorAbrirArchivo');
         exit;
    }
} else {
    header('Location: ../transacciones.php?mensaje=ErrorArchivoNoValido');
    exit;
}
?>