<?php
session_start(); // Iniciar sesión para gestionar mensajes y datos del usuario
include 'includes/conexion.php'; // Incluir el archivo de conexión a la base de datos

// Configuración de manejo de errores
ini_set('display_errors', 1);   // Activa la visualización de errores (útil para desarrollo)
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);           // Reporta todos los errores
ini_set('log_errors', 1);        // Activa el registro de errores en un archivo
ini_set('error_log', '/home/pedro/pitercheffSr/control_gastos/errores.log'); // Ruta del archivo de log

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Verifica si el método de solicitud es POST
    // Recuperar y sanitizar la descripción
    if (isset($_POST['descripcion']) && !empty($_POST['descripcion'])) {
        $descripcion = trim(strip_tags($_POST['descripcion'])); // Limpiar la descripción de etiquetas HTML
        
        // Validar la longitud de la descripción
        if (strlen($descripcion) > 255) { // Comprobar si excede 255 caracteres
            $_SESSION['error'] = "La descripción es demasiado larga (máximo 255 caracteres)."; // Mensaje de error
            header("Location: dashboard.php"); // Redirigir al panel de control
            exit;
        }
    } else {
        $_SESSION['error'] = "La descripción es un campo obligatorio."; // Mensaje de error si está vacío
        header("Location: dashboard.php");
        exit;
    }

    // Recuperar y validar otros campos de entrada
    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0; // Obtener el monto, si no existe asignar 0
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : ''; // Obtener la fecha o dejar vacío
    // Tipo: por defecto 'gasto' para evitar inconsistencias si no viene del formulario
    $tipo = isset($_POST['tipo']) && in_array($_POST['tipo'], ['ingreso', 'gasto']) ? $_POST['tipo'] : 'gasto';
    // id de la categoría (opcional) enviado desde el formulario
    $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
    // Origen del ingreso (cuando tipo = 'ingreso') enviado desde el formulario
    $ingreso_origen = isset($_POST['ingreso_origen']) ? trim($_POST['ingreso_origen']) : '';

    // Si el tipo es 'ingreso' no asociamos categoría: la dejamos en blanco y no se compute como gasto
    if ($tipo === 'ingreso') {
        $id_categoria = 0;
        $categoria_clasificacion = null;
    }

    // Validar que el monto sea un número positivo
    if ($monto <= 0) { // Comprueba que el monto sea mayor que cero
        $_SESSION['error'] = "El monto debe ser un número positivo."; // Mensaje de error
        header("Location: dashboard.php");
        exit;
    }
    
    // Validar que la fecha sea válida
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) { // Verifica el formato de fecha YYYY-MM-DD
        $_SESSION['error'] = "Formato de fecha incorrecto. Debe ser YYYY-MM-DD."; // Mensaje de error
        header("Location: dashboard.php");
        exit;
    }

    // Obtener el ID del usuario que está registrando la transacción
    $id_usuario = $_SESSION['usuario_id'];
    // Si se proporcionó un id de categoría, obtener su clasificación (50/30/20) para almacenarla
    $categoria_clasificacion = null;
    if ($id_categoria > 0) {
        $stmt_cat = $conexion->prepare("SELECT clasificacion, nombre FROM categorias WHERE id = ? LIMIT 1");
        if ($stmt_cat) {
            $stmt_cat->bind_param("i", $id_categoria);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();
            if ($fila_cat = $res_cat->fetch_assoc()) {
                // Guardamos la clasificación (por ejemplo '50', '30', '20') que usa el dashboard
                $categoria_clasificacion = $fila_cat['clasificacion'];
                // Excluir categoría llamada exactamente 'Sueldo' de la regla 50%: si el nombre es 'Sueldo'
                // y la clasificación fuera '50', no la guardamos como '50' para el cálculo (la dejamos NULL)
                if (strtolower(trim($fila_cat['nombre'])) === 'sueldo' && $categoria_clasificacion == '50') {
                    // Forzamos a NULL para que no sume dentro de 50%
                    $categoria_clasificacion = null;
                }
            }
            $stmt_cat->close();
        }
    }

    // Obtener subcategoría y sub-subcategoría
    $id_subcategoria = isset($_POST['subcategoria']) && $_POST['subcategoria'] !== '' ? intval($_POST['subcategoria']) : null;
    $id_subsubcategoria = isset($_POST['subsubcategoria']) && $_POST['subsubcategoria'] !== '' ? intval($_POST['subsubcategoria']) : null;

    // Validar la jerarquía si se seleccionó sub-subcategoría
    if ($id_subsubcategoria) {
        $stmt = $conexion->prepare("SELECT id FROM subcategorias WHERE id = ? AND parent_id = ? AND id_usuario = ?");
        $stmt->bind_param("iii", $id_subsubcategoria, $id_subcategoria, $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $_SESSION['error'] = "Sub-subcategoría no válida.";
            header("Location: dashboard.php");
            exit;
        }
        $stmt->close();
    }
    // Validar subcategoría si no hay sub-subcategoría
    if ($id_subcategoria && !$id_subsubcategoria) {
        $stmt = $conexion->prepare("SELECT id FROM subcategorias WHERE id = ? AND parent_id IS NULL AND id_usuario = ?");
        $stmt->bind_param("ii", $id_subcategoria, $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $_SESSION['error'] = "Subcategoría no válida.";
            header("Location: dashboard.php");
            exit;
        }
        $stmt->close();
    }

    // Preparar la consulta SQL para insertar una nueva transacción
    $stmt = $conexion->prepare("INSERT INTO transacciones (descripcion, monto, fecha, id_usuario, tipo, categoria, id_categoria, id_subcategoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        if ($tipo === 'ingreso') {
            $categoria_param = $ingreso_origen !== '' ? $ingreso_origen : '';
        } else {
            $categoria_param = $categoria_clasificacion !== null ? $categoria_clasificacion : '';
        }
        // Guardar la categoría principal, subcategoría y sub-subcategoría (si existe)
        $final_subcat = $id_subsubcategoria ? $id_subsubcategoria : ($id_subcategoria ? $id_subcategoria : null);
        $stmt->bind_param("sdsissii", $descripcion, $monto, $fecha, $id_usuario, $tipo, $categoria_param, $id_categoria, $final_subcat);
        if ($stmt->execute()) {
            $_SESSION['success_transaccion'] = "Transacción registrada exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al registrar la transacción: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error en la preparación de la consulta de transacción.";
    }
    header("Location: dashboard.php");
    exit; // Terminar la ejecución
}
