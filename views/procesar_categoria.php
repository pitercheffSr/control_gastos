<?php
session_start();
require_once '../includes/conexion.php'; // Usar conexión mysqli definida en includes/conexion.php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === '') {
    echo json_encode(['status'=>'error','message'=>'Acción no definida']);
    exit;
}

// Agregar categoría
if ($action === 'add_categoria') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') exit(json_encode(['status'=>'error','message'=>'Nombre vacío']));
    $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
    $stmt->bind_param('s', $nombre);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Categoría agregada','id'=>$conexion->insert_id,'nombre'=>$nombre]);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
    $stmt->close();
    exit;
}

// Agregar subcategoría
if ($action === 'add_subcategoria') {
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id_categoria <= 0 || $nombre === '') exit(json_encode(['status'=>'error','message'=>'Datos inválidos']));
    $stmt = $conexion->prepare("INSERT INTO subcategorias (id_categoria, nombre) VALUES (?, ?)");
    $stmt->bind_param('is', $id_categoria, $nombre);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Subcategoría agregada','id'=>$conexion->insert_id,'nombre'=>$nombre]);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
    $stmt->close();
    exit;
}

// Agregar sub-subcategoría
if ($action === 'add_subsubcategoria') {
    $id_subcategoria = intval($_POST['id_subcategoria'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id_subcategoria <= 0 || $nombre === '') exit(json_encode(['status'=>'error','message'=>'Datos inválidos']));
    $stmt = $conexion->prepare("INSERT INTO subsubcategorias (id_subcategoria, nombre) VALUES (?, ?)");
    $stmt->bind_param('is', $id_subcategoria, $nombre);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Sub-Subcategoría agregada','id'=>$conexion->insert_id,'nombre'=>$nombre]);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status'=>'error','message'=>'Acción desconocida']);
