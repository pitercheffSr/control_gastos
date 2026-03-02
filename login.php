<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recogemos y limpiamos el usuario de la misma manera
    $usuario_raw = trim($_POST['usuario']);
    $usuario = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($usuario_raw));
    
    if (empty($usuario)) {
        $error = 'Por favor, introduce un nombre de usuario válido.';
    } else {
        // 2. Le pegamos el dominio mágico
        $email = $usuario . '@cgastos.mi';
        $password = $_POST['password'];

        $stmt = $pdo->prepare('SELECT id, password FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Control de Gastos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 text-white shadow-xl shadow-indigo-200 mb-6 transform transition hover:scale-105 duration-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Bienvenido de nuevo</h2>
            <p class="text-gray-500 mt-3 font-medium">Introduce tus datos para acceder a tu panel.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-xl flex items-start animate-fade-in">
                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-red-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div>
                    <div class="ml-3"><p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($error) ?></p></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" autocomplete="off">
                
                <input style="display:none" type="text" name="fakeusernameremembered"/>
                <input style="display:none" type="password" name="fakepasswordremembered"/>

                <div>
                    <label for="usuario" class="block text-sm font-bold text-gray-700 mb-2">Nombre de Usuario</label>
                    <div class="relative">
                        <input type="text" name="usuario" id="usuario" required autocomplete="off" data-lpignore="true"
                               class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-800 outline-none" 
                               placeholder="Tu usuario">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required autocomplete="new-password" data-lpignore="true" class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-800 outline-none" placeholder="••••••••">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        Entrar a mi cuenta
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center mt-8 text-sm text-gray-600 font-medium">
            ¿No tienes cuenta? <a href="registro.php" class="font-bold text-indigo-600 hover:text-indigo-500 transition-colors">Regístrate gratis</a>
        </p>
    </div>
</body>
</html>