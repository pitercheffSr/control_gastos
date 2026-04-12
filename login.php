<?php
/**
 * =============================================================
 *  PÁGINA DE INICIO DE SESIÓN
 * =============================================================
 */

// 1. Incluir archivos necesarios
require_once 'config.php';
require_once 'controllers/AuthController.php'; // Incluimos el controlador

$error_message = '';

// Comprobar si el usuario fue redirigido por inactividad
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_message = 'Tu sesión ha expirado por inactividad. Por favor, vuelve a iniciar sesión.';
}

// 2. Redirigir si el usuario ya ha iniciado sesión
if (isset($_SESSION['usuario_id'])) {
    redirect('dashboard.php');
}

// 3. Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error_message = 'Por favor, introduce tu nombre de usuario y contraseña.';
    } else {
        $usuario_limpio = strtolower(preg_replace('/\s+/', '', $usuario));
        $email = $usuario_limpio . '@cgastos.mi';

        $auth = new AuthController($pdo);
        $user = $auth->login($email, $password);

        if ($user) {
            // Medida contra Fijación de Sesión (Session Fixation)
            session_regenerate_id(true);

            // ¡Credenciales correctas! Iniciar la sesión.
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol'] = $user['rol']; // Guardamos el rol en la sesión
            $_SESSION['last_activity'] = time();
            $_SESSION['login_reciente'] = true; // Marca para forzar el session storage
            
            redirect('dashboard.php'); // Redirigir al panel principal
        } else {
            $error_message = 'El usuario o la contraseña son incorrectos.';
        }
    }
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
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } </style>
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
        
        <form method="POST" action="login.php" class="space-y-6" autocomplete="off">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1" for="usuario">Nombre de usuario</label>
                <div class="flex">
                    <input type="text" name="usuario" id="usuario" placeholder="usuario123" required class="w-full px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" pattern="[a-zA-Z0-9_-]+" title="Solo letras, números, guiones y guiones bajos" autocomplete="username">
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

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
 
