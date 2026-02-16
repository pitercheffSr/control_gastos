<?php
// ACTIVAR TODOS LOS ERRORES (Para que no salga pantalla blanca)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🕵️ Informe de Diagnóstico</h1>";

// 1. VERIFICAR RUTA DEL PROYECTO
echo "<h3>1. Sistema de Archivos</h3>";
echo "Directorio actual: " . __DIR__ . "<br>";

// 2. VERIFICAR CSS (La causa visual más probable)
$cssPath = __DIR__ . '/assets/css/dashboard.css';
echo "Buscando CSS en: <code>$cssPath</code>... ";
if (file_exists($cssPath)) {
    echo "✅ <strong>EXISTE</strong>.<br>";
    if (is_readable($cssPath)) {
        echo "Lectura de permisos: ✅ <strong>OK</strong>.<br>";
        echo "<a href='assets/css/dashboard.css' target='_blank'>➡️ Clic aquí para probar si el navegador puede verlo</a> (Debería abrirse el código CSS).";
    } else {
        echo "❌ <strong>ERROR DE PERMISOS</strong> (Apache no puede leerlo).<br>";
    }
} else {
    echo "❌ <strong>NO EXISTE</strong>. Verifica que la carpeta se llame 'assets' y no 'Assets'.<br>";
}

// 3. VERIFICAR BASE DE DATOS Y MODELOS
echo "<h3>2. Lógica PHP y Base de Datos</h3>";

$files = [
    'config.php',
    'db.php',
    'controllers/DashboardController.php',
    'models/TransaccionModel.php',
    'models/CategoriaModel.php'
];

foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "Archivo <code>$file</code>: ✅<br>";
        require_once __DIR__ . '/' . $file; // Intentamos cargarlo para ver si explota
    } else {
        echo "Archivo <code>$file</code>: ❌ <strong>FALTA</strong><br>";
    }
}

try {
    if (isset($pdo)) {
        echo "Conexión Base de Datos: ✅ <strong>OK</strong><br>";
        // Prueba rápida del modelo
        $catModel = new CategoriaModel($pdo);
        $total = count($catModel->listarArbol());
        echo "Prueba de Modelo (Categorías): ✅ Se encontraron $total categorías.<br>";
    } else {
        echo "Conexión Base de Datos: ❌ <strong>FALLÓ</strong> (Variable \$pdo no definida)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error Crítico: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Prueba Visual</h3>";
echo '<link rel="stylesheet" href="assets/css/dashboard.css">';
echo '<div class="card bg-primary text-light" style="padding:20px; max-width:300px; margin-top:10px;">
        <div class="card-title h5">Prueba de Estilo</div>
        <small>Si ves esto azul con letras blancas y redondeado, el CSS funciona.</small>
      </div>';
?>