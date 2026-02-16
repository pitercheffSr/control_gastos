<?php
// debug_login.php
// Script para averiguar por qué no funciona el login
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config.php';
require_once 'db.php';

echo "<h1>🕵️ Sherlock Holmes del Login</h1>";
echo "<p>Vamos a probar las credenciales: <strong>admin@admin.com</strong> / <strong>admin</strong></p>";

// 1. VERIFICAR CONEXIÓN
if ($pdo) {
    echo "✅ Conexión a Base de Datos: OK<br>";
} else {
    die("❌ Error de conexión a BD");
}

// 2. BUSCAR USUARIO
$email = 'admin@admin.com';
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ Usuario encontrado en BD: " . htmlspecialchars($user['nombre']) . " (ID: " . $user['id'] . ")<br>";
    echo "🔑 Hash guardado: " . substr($user['password'], 0, 10) . "...<br>";
    
    // 3. VERIFICAR CONTRASEÑA
    $pass = 'admin';
    if (password_verify($pass, $user['password'])) {
        echo "✅ Contraseña 'admin': <strong>CORRECTA</strong> (password_verify devuelve true)<br>";
        
        // 4. PRUEBA DE SESIÓN
        $_SESSION['test_login'] = 'Funciona';
        if (isset($_SESSION['test_login'])) {
            echo "✅ Guardado de Sesión PHP: OK<br>";
            echo "<hr><h2 style='color:green'>CONCLUSIÓN: Todo funciona técnicamente.</h2>";
            echo "El problema es probablemente tu navegador o el archivo index.php.<br>";
            echo "👉 <a href='index.php'>Intenta entrar aquí</a> (Borra caché con Ctrl+F5 antes).";
        } else {
            echo "❌ <strong>ERROR CRÍTICO:</strong> PHP no está guardando las sesiones. Revisa permisos de /var/lib/php/sessions.";
        }
        
    } else {
        echo "❌ Contraseña: <strong>INCORRECTA</strong>. El hash no coincide.<br>";
        echo "Solución: Ejecuta de nuevo reset_total.php";
    }
} else {
    echo "❌ Usuario 'admin@admin.com': <strong>NO EXISTE</strong> en la tabla.<br>";
    echo "Solución: Ejecuta de nuevo reset_total.php";
}

echo "<br><br><pre>Datos crudos del usuario:\n";
print_r($user);
echo "</pre>";
?>