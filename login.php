<?php
/**
 * =============================================================
 *  PÁGINA DE INICIO DE SESIÓN
 * =============================================================
 */

// 1. Incluir el archivo de configuración.
// Esto conectará a la BD. Si hay un error, se mostrará aquí.
require_once 'config.php';

$error_message = '';

// 2. Redirigir si el usuario ya ha iniciado sesión
if (isset($_SESSION['usuario_id'])) {
    redirect('transacciones.php'); // O tu página principal después del login
}

// 3. Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, introduce tu email y contraseña.';
    } else {
        try {
            // Buscar al usuario por su email
            $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            // Verificar si el usuario existe y la contraseña es correcta
            if ($usuario && password_verify($password, $usuario['password'])) {
                // ¡Credenciales correctas! Iniciar la sesión.
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['last_activity'] = time();
                
                redirect('transacciones.php'); // Redirigir al panel principal
            } else {
                $error_message = 'El email o la contraseña son incorrectos.';
            }
        } catch (PDOException $e) {
            // Esto captura errores de la consulta, como que la tabla 'usuarios' no exista.
            $error_message = 'Error del sistema. Por favor, contacta al administrador.';
            // Para depurar, podrías registrar el error: error_log($e->getMessage());
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
            <input type="email" name="email" placeholder="Email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" autocomplete="email">
            <div class="relative">
                <input type="password" name="password" id="password" placeholder="Contraseña" required class="w-full px-4 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" autocomplete="current-password">
                <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">👁️</button>
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
//000000000 000 
