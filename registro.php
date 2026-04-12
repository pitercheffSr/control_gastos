<?php
require_once 'config.php';
require_once 'controllers/AuthController.php'; // Incluimos el controlador

// La sesión ya se inicia en config.php
if (isset($_SESSION['usuario_id'])) {
    // Si es administrador, le permitimos quedarse para crear usuarios manualmente
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        header('Location: transacciones.php');
        exit;
    }
}

$error = '';
$registro_exitoso = false;
$codigo_recuperacion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_raw = trim($_POST['usuario']);
    $usuario = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($usuario_raw));
    
    // --- COMPROBACIÓN ANTI-BOT (CAPTCHA MATEMÁTICO) ---
    $respuesta_usuario = (int)$_POST['captcha_respuesta'];
    $respuesta_correcta = (int)$_SESSION['captcha_correcto'];

    if ($respuesta_usuario !== $respuesta_correcta) {
        $error = 'Seguridad anti-bot fallida. La suma matemática no es correcta.';
    } elseif (empty($usuario)) {
        $error = 'Por favor, introduce un nombre de usuario válido.';
    } else {
        $email = $usuario . '@cgastos.mi';
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 6) {
             $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $auth = new AuthController($pdo);
            $registro = $auth->register($usuario, $email, $password);

            if (isset($registro['id'])) {
                // Si NO es un administrador creando la cuenta, iniciamos sesión automáticamente
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    // Medida contra Fijación de Sesión (Session Fixation)
                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $registro['id'];
                    $_SESSION['usuario_nombre'] = $usuario;
                    $_SESSION['usuario_rol'] = 'usuario';
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_reciente'] = true; // Marca para forzar el session storage
                }
                
                $registro_exitoso = true;
                $codigo_recuperacion = $registro['recovery_code'];
            } else {
                $error = $registro['error'] ?? 'Hubo un error desconocido en el registro.';
            }
        }
    }
}

// Generamos un nuevo desafío matemático cada vez que carga la página
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha_correcto'] = $num1 + $num2;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Seguro - FinanzasPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Crear cuenta</h2>
            <p class="text-gray-500 mt-3 font-medium">Toma el control de tus finanzas hoy</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            
            <!-- Aviso de privacidad -->
            <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-6 rounded-r-xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-indigo-700 font-medium">
                            <strong>¡Privacidad garantizada!</strong> No necesitas usar tu correo personal. Crea un nombre de usuario y el sistema generará una cuenta interna (<code>@cgastos.mi</code>) automáticamente para ti.
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-xl">
                    <p class="text-sm text-red-700 font-bold"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($registro_exitoso): ?>
                <div class="text-center space-y-6 py-4">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900">¡Cuenta creada con éxito!</h3>
                    
                    <div class="bg-yellow-50 border-2 border-yellow-400 p-6 rounded-2xl shadow-sm text-left">
                        <p class="text-yellow-800 font-extrabold mb-2 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> GUARDA ESTE CÓDIGO</p>
                        <p class="text-sm text-yellow-700 mb-4">Como no usamos tu correo personal, esta es la <strong>única forma</strong> de recuperar tu cuenta si olvidas la contraseña.</p>
                        <div class="bg-white p-4 rounded-xl border border-yellow-200 font-mono text-3xl tracking-widest text-center text-gray-900 font-black select-all">
                            <?= htmlspecialchars($codigo_recuperacion) ?>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
                        <a href="admin.php" class="block w-full py-4 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all">
                            He guardado el código, volver al Panel Admin
                        </a>
                    <?php else: ?>
                        <a href="transacciones.php" class="block w-full py-4 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all">
                            He guardado el código, entrar a la App
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre de Usuario</label>
                    <div class="flex">
                        <input type="text" name="usuario" required class="w-full px-4 py-3 border border-gray-300 rounded-l-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-gray-800" placeholder="usuario123" pattern="[a-zA-Z0-9_-]+" title="Solo letras, números, guiones y guiones bajos">
                        <span class="inline-flex items-center px-4 rounded-r-xl border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm font-bold">
                            @cgastos.mi
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required autocomplete="new-password" class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 transition-all font-medium outline-none" placeholder="••••••••">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">👁️</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirmar Contraseña</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password" class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 transition-all font-medium outline-none" placeholder="••••••••">
                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">👁️</button>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Verificación Humana</label>
                        <p class="text-sm text-gray-500 mt-1">¿Cuánto es <span class="font-extrabold text-indigo-600 text-lg"><?= $num1 ?> + <?= $num2 ?></span>?</p>
                    </div>
                    <input type="number" name="captcha_respuesta" required class="w-20 text-center px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-bold outline-none" placeholder="?">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all">
                        Crear Cuenta y Entrar
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <p class="text-center mt-8 text-sm text-gray-600 font-medium">
            ¿Ya tienes una cuenta? <a href="login.php" class="font-bold text-indigo-600 hover:text-indigo-500">Inicia sesión aquí</a>
        </p>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>