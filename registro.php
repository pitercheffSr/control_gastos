<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['usuario_id'])) {
    header('Location: transacciones.php');
    exit;
}

$error = '';

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
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este nombre de usuario ya está en uso. ¡Prueba con otro!';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)');
                
                if ($stmt->execute([$usuario, $email, $hash])) {
                    $nuevo_id = $pdo->lastInsertId();
                    
                    $stmtUser = $pdo->prepare("UPDATE usuarios SET dia_inicio_mes = 1 WHERE id = ?");
                    $stmtUser->execute([$nuevo_id]);

                    $fechaRegistro = new DateTime();
                    $fechaBorrado = clone $fechaRegistro;
                    $fechaBorrado->modify('+4 months');
                    $fechaBorradoStr = $fechaBorrado->format('Y-m-d H:i:s');
                    $stmtBorrado = $pdo->prepare("UPDATE usuarios SET fecha_borrado = ? WHERE id = ?");
                    $stmtBorrado->execute([$fechaBorradoStr, $nuevo_id]);

                    require_once 'models/CategoriaModel.php';
                    $catModel = new CategoriaModel($pdo);
                    $catModel->crearCategoriasPorDefecto($nuevo_id);

                    $_SESSION['usuario_id'] = $nuevo_id;
                    header('Location: transacciones.php');
                    exit;
                } else {
                    $error = 'Error al registrar el usuario.';
                }
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
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-xl">
                    <p class="text-sm text-red-700 font-bold"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" autocomplete="off">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nombre de Usuario</label>
                    <input type="text" name="usuario" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-gray-800 outline-none" placeholder="Ej: pedro123">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 transition-all font-medium outline-none" placeholder="••••••••">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400">👁️</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirmar Contraseña</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 transition-all font-medium outline-none" placeholder="••••••••">
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