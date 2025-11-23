<?php
// insert.php - insertar nueva transacción (POST)
session_start();
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');

// Asegurar usuario
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "No autorizado. Inicia sesión.";
    exit;
}

$id_usuario = intval($_SESSION['usuario_id']);

$fecha = $_POST['fecha'] ?? '';
$monto = $_POST['monto'] ?? '';
$tipo  = $_POST['tipo'] ?? ''; // 'gasto' o 'ingreso'
$id_cat = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
$id_sub = !empty($_POST['subcategoria']) ? intval($_POST['subcategoria']) : null;
$id_ssc = !empty($_POST['subsubcategoria']) ? intval($_POST['subsubcategoria']) : null;
$concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
$descripcion = $concepto; // puedes ajustarlo: descripción = concepto

// Validaciones simples
if (!$fecha || !$monto || !$tipo) {
    $_SESSION['error'] = "Fecha, monto y tipo son obligatorios.";
    header('Location: dashboard.php');
    exit;
}

// normalizar monto
$monto_val = filter_var($monto, FILTER_VALIDATE_FLOAT);
if ($monto_val === false) $monto_val = 0;

try {
    $sql = "INSERT INTO transacciones
            (id_usuario, fecha, monto, tipo, categoria, id_categoria, id_subcategoria, id_subsubcategoria, descripcion)
            VALUES (:id_usuario, :fecha, :monto, :tipo, :categoria_text, :id_cat, :id_sub, :id_ssc, :descripcion)";

    // Para el campo "categoria" guardamos el nombre de la categoría raíz o 'Ingreso' para ingresos
    $categoria_text = 'Sin categoría';
    if ($tipo === 'ingreso') {
        $categoria_text = $concepto !== '' ? $concepto : 'Ingreso';
    } else {
        // intentar tomar nombre de la categoría raíz si existe
        if ($id_cat) {
            $q = $conn->prepare("SELECT nombre FROM categorias WHERE id = :id LIMIT 1");
            $q->execute(['id' => $id_cat]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) $categoria_text = $r['nombre'];
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'id_usuario' => $id_usuario,
        'fecha' => $fecha,
        'monto' => $monto_val,
        'tipo' => $tipo,
        'categoria_text' => $categoria_text,
        'id_cat' => $id_cat,
        'id_sub' => $id_sub,
        'id_ssc' => $id_ssc,
        'descripcion' => $descripcion
    ]);

    $_SESSION['success'] = "Transacción guardada.";
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    error_log("insert.php error: " . $e->getMessage());
    $_SESSION['error'] = "Error guardando transacción.";
    header('Location: dashboard.php');
    exit;
}
