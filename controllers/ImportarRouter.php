<?php
// controllers/ImportarRouter.php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: ../index.php'); 
    exit; 
}

$uid = $_SESSION['usuario_id'];

// 1. Verificar si se ha enviado un archivo y no hay errores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
    
    // Obtenemos la categoría elegida en el modal. Esta será nuestro "Salvavidas"
    $categoria_defecto = $_POST['categoria_id'] ?? null;
    if (empty($categoria_defecto)) {
        header('Location: ../transacciones.php?mensaje=ErrorSinCategoria');
        exit;
    }

    $archivoTmp = $_FILES['archivo_csv']['tmp_name'];
    
    if (($handle = fopen($archivoTmp, "r")) !== FALSE) {
        
        // --- NUEVO: Cargar todas las categorías del usuario para el auto-etiquetado ---
        $stmtCats = $pdo->prepare("SELECT id, nombre FROM categorias WHERE usuario_id = ?");
        $stmtCats->execute([$uid]);
        $categoriasUsuario = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

        $delimitador = ";"; 
        $insertados = 0;
        $omitidos = 0;

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transacciones WHERE usuario_id = ? AND fecha = ? AND importe = ? AND descripcion = ?");
        
        $stmtInsert = $pdo->prepare("INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, importe) VALUES (?, ?, ?, ?, ?)");

        $bloqueActual = 'DESCONOCIDO';

        // 3. Leer línea por línea
        while (($datos = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
            
            // Detector de cabeceras según el formato de tu banco
            if (count($datos) >= 3 && $datos[0] === 'Fecha' && strpos($datos[1], 'Descripci') !== false) {
                $bloqueActual = 'PENDIENTES';
                continue;
            } else if (count($datos) >= 4 && $datos[0] === 'Fecha contable' && strpos($datos[2], 'Descripci') !== false) {
                $bloqueActual = 'CONSOLIDADOS';
                continue;
            }

            // Comprobar si la fila contiene una fecha válida (DD/MM/YYYY)
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

                // Limpieza de fechas y números
                $partes_fecha = explode('/', $fecha_raw);
                $fecha = $partes_fecha[2] . '-' . $partes_fecha[1] . '-' . $partes_fecha[0];

                $importe_str = str_replace('.', '', $importe_raw);
                $importe_str = str_replace(',', '.', $importe_str);
                $importe = (float) $importe_str;
                
                // Comprobar si es un duplicado exacto
                $stmtCheck->execute([$uid, $fecha, $importe, $concepto]);
                $existe = $stmtCheck->fetchColumn();

                if ($existe > 0) {
                    $omitidos++;
                    continue; // Es duplicado, saltamos al siguiente
                }

                // --- LA MAGIA DE LA AUTO-CATEGORIZACIÓN ---
                $categoria_final = $categoria_defecto; // Partimos con la de "salvavidas"
                
                // Recorremos todas tus categorías buscando coincidencias
                foreach ($categoriasUsuario as $cat) {
                    // Si el nombre de tu categoría está dentro del texto del banco...
                    // Ej: "Mercadona" está dentro de "MERCADONA AVDA. AGUSTINOS"
                    if (stripos($concepto, $cat['nombre']) !== false) {
                        $categoria_final = $cat['id'];
                        break; // ¡Coincidencia encontrada! Dejamos de buscar
                    }
                }

                // 6. Guardamos en la BD usando la categoría final detectada
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