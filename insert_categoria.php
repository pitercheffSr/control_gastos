<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!empty($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
    $stmt->execute(['nombre' => $nombre]);
}

header('Location: categorias.php');
exit;
