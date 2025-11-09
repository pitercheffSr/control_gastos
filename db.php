<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=bd_503020;charset=utf8", "root", "1234");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
