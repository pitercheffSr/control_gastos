<?php
require_once 'config.php';
require_once 'db.php';

try {
    // Forzamos que '50% necesidades' sea del grupo 'necesidad'
    $sql = "UPDATE categorias SET grupo_503020 = 'necesidad' WHERE nombre LIKE '%50% necesidades%'";
    $pdo->exec($sql);
    
    echo "<h1>✅ ¡Arreglado!</h1>";
    echo "<p>La categoría '50% necesidades' ahora cuenta como NECESIDAD.</p>";
    echo "<a href='dashboard.php'>Volver al Dashboard</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}