<?php
/**
 * =============================================================
 *  PÁGINA DE INICIO DE SESIÓN
 * =============================================================
 */

// 1. Incluir archivos necesarios
require_once 'config.php';

$error_message = '';

// Comprobar si el usuario fue redirigido por inactividad
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_message = 'Tu sesión ha expirado por inactividad. Por favor, vuelve a iniciar sesión.';
} elseif (isset($_SESSION['auth_error'])) {
    $error_message = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']); // Limpiamos el error tras leerlo
}

// 2. Redirigir si el usuario ya ha iniciado sesión
if (isset($_SESSION['usuario_id'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Control de Gastos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- CSS Externo -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-2xl shadow-lg">
        <hgroup class="text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">Iniciar Sesión</h1>
            <p class="text-gray-500">Accede a tu panel de FinanzasPro</p>
        </hgroup>

        <?php if (!empty($error_message)): ?>
            <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="controllers/AuthRouter.php?action=login" class="space-y-6" autocomplete="off">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1" for="usuario">Nombre de usuario</label>
                <div class="flex">
                    <input type="text" name="usuario" id="usuario" required class="w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" pattern="[a-zA-Z0-9_\-]+" title="Solo letras, números, guiones y guiones bajos" autocomplete="username" placeholder="usuario123">
                    <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-bold">
                        @cgastos.mi
                    </span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1" for="password">Contraseña</label>
                <div class="relative">
                    <input type="password" name="password" id="password" placeholder="••••••••" required class="w-full px-4 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" autocomplete="current-password">
                    <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">👁️</button>
                </div>
                <div class="text-right mt-2">
                    <a href="recuperar.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            <button type="submit" class="w-full px-5 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-md transition">Entrar</button>
        </form>
        <p class="text-center text-sm text-gray-600">¿No tienes una cuenta? <a href="registro.php" class="font-medium text-indigo-600 hover:underline">Regístrate aquí</a></p>
    </div>

    <!-- JS Externo -->
    <script src="assets/js/auth.js"></script>
</body>
</html>
