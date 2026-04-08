<?php
require_once 'config.php';
require_once 'controllers/AuthController.php';

if (isset($_SESSION['usuario_id'])) {
    redirect('dashboard.php');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($codigo) || empty($password)) {
        $error_message = 'Todos los campos son obligatorios.';
    } elseif (strlen($password) < 6) {
        $error_message = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $usuario_limpio = strtolower(preg_replace('/\s+/', '', $usuario));
        $email = $usuario_limpio . '@cgastos.mi';
        $codigo_limpio = strtoupper(trim($codigo));

        $auth = new AuthController($pdo);
        
        if ($auth->resetPasswordWithCode($email, $codigo_limpio, $password)) {
            $success_message = '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión.';
        } else {
            $error_message = 'El nombre de usuario o el código de recuperación son incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Cuenta - FinanzasPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-orange-400 to-red-500"></div>
            
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2 text-center">Recuperar acceso</h2>
            <p class="text-sm text-gray-500 text-center mb-8">Utiliza tu código de seguridad para crear una nueva contraseña.</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-xl text-green-700 font-bold text-sm">
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <a href="login.php" class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all">Ir a Iniciar Sesión</a>
            <?php else: ?>
            
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl text-red-700 font-bold text-sm">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="recuperar.php" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de Usuario</label>
                        <input type="text" name="usuario" required placeholder="tu_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Código de Recuperación</label>
                        <input type="text" name="codigo" required placeholder="Ej: A1B2C3D4" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-bold text-lg uppercase tracking-wider text-center">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nueva Contraseña</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required placeholder="••••••••" class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                            <button type="button" onclick="const i = document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">👁️</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all mt-4">Restablecer Contraseña</button>
                </form>
            <?php endif; ?>
        </div>
        
        <p class="text-center mt-8 text-sm text-gray-600 font-medium">
            <a href="login.php" class="font-bold text-indigo-600 hover:text-indigo-500 flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Volver al inicio
            </a>
        </p>
    </div>
</body>
</html>