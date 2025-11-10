<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario_id'];
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto = $_POST['monto'] ?? 0;
    $categoria = $_POST['categoria'] ?? null;
    $subcategoria = $_POST['subcategoria'] ?? null;
    $subsubcategoria = $_POST['subsubcategoria'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO transacciones (id_usuario, fecha, monto, id_categoria, id_subcategoria, id_subsubcategoria)
        VALUES (:id_usuario, :fecha, :monto, :categoria, :subcategoria, :subsubcategoria)
    ");
    $stmt->execute([
        'id_usuario' => $id_usuario,
        'fecha' => $fecha,
        'monto' => $monto,
        'categoria' => $categoria ?: null,
        'subcategoria' => $subcategoria ?: null,
        'subsubcategoria' => $subsubcategoria ?: null,
    ]);

    header('Location: dashboard.php');
    exit;
}
?>
