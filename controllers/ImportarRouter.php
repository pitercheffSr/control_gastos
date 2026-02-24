<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/TransaccionModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario_id'])) {
    die("No autorizado");
}

$uid = $_SESSION['usuario_id'];
$model = new TransaccionModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    $archivo = $_FILES['archivo_csv']['tmp_name'];
    
    // Categoría de rescate si el sistema no logra adivinar
    $cat_fallback = isset($_POST['categoria_id']) && !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;

    // 1. CARGAMOS LA MEMORIA HISTÓRICA DEL USUARIO
    // Obtenemos todos los movimientos pasados para que el sistema "aprenda" cómo categoriza este usuario.
    $stmtMemoria = $pdo->prepare("SELECT descripcion, categoria_id FROM transacciones WHERE usuario_id = ? AND categoria_id IS NOT NULL ORDER BY fecha DESC");
    $stmtMemoria->execute([$uid]);
    
    $diccionario_ia = [];
    foreach ($stmtMemoria->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $descLimpiada = strtolower(trim($row['descripcion']));
        // Guardamos la relación. Al estar ordenado por fecha, se queda con la categoría más reciente que usaste para ese concepto.
        if (!isset($diccionario_ia[$descLimpiada])) {
            $diccionario_ia[$descLimpiada] = $row['categoria_id'];
        }
    }

    // 2. PROCESAMOS EL ARCHIVO
    if (($handle = fopen($archivo, "r")) !== FALSE) {
        // Saltamos la primera línea si son las cabeceras (Fecha, Descripcion, etc.)
        $primera_linea = fgetcsv($handle, 1000, ";");
        
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // Asumiendo formato estándar: Fecha | Descripcion | (Categoría Excel) | Importe
            // Dependiendo del formato real de tu Excel, podrías tener que ajustar los índices $data[0], $data[1]...
            
            $fechaRaw = $data[0] ?? date('Y-m-d');
            $descripcionRaw = $data[1] ?? 'Movimiento Excel';
            
            // Buscamos el importe. Normalmente en los extractos de banco está en la última o penúltima columna.
            // Si tu CSV tiene 4 columnas: 0=Fecha, 1=Desc, 2=CategoriaTxt, 3=Importe
            $importeStr = isset($data[3]) ? $data[3] : (isset($data[2]) ? $data[2] : '0'); 
            
            // Limpiamos el importe (quitamos euros, cambiamos comas por puntos)
            $importeStr = str_replace(['€', ' ', '.'], ['', '', ''], $importeStr);
            $importeStr = str_replace(',', '.', $importeStr);
            $monto = (float)$importeStr;

            // Formateamos la fecha si viene como dd/mm/yyyy a yyyy-mm-dd
            $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaRaw);
            $fechaFinal = $fechaObj ? $fechaObj->format('Y-m-d') : date('Y-m-d', strtotime(str_replace('/', '-', $fechaRaw)));

            // 3. LA MAGIA DE LA CATEGORIZACIÓN AUTOMÁTICA
            $descBuscador = strtolower(trim($descripcionRaw));
            
            if (isset($diccionario_ia[$descBuscador])) {
                // ¡Coincidencia! El sistema recuerda esta descripción y le pone la misma categoría
                $cat_asignada = $diccionario_ia[$descBuscador];
            } else {
                // No lo reconoce. Usa la categoría por defecto del formulario.
                $cat_asignada = $cat_fallback;
            }

            // Guardamos el movimiento en la base de datos
            $nuevaTransaccion = [
                'id' => null,
                'fecha' => $fechaFinal,
                'descripcion' => $descripcionRaw,
                'monto' => $monto,
                'categoria_id' => $cat_asignada
            ];
            
            $model->save($uid, $nuevaTransaccion);
        }
        fclose($handle);
    }
    
    // Devolvemos al usuario a la pantalla de transacciones para que afine lo que haga falta
    header('Location: ../transacciones.php');
    exit;
}
?>