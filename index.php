<?php
require_once 'config.php';
require_once 'controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si YA existe la sesión, no mostramos el login, vamos directo al Dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($pdo);
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($auth->login($email, $pass)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - MiCartera</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md border border-gray-200">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-700">MiCartera</h1>
            <p class="text-gray-500">Gestión de Gastos 50/30/20</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm border border-red-200">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="password" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition duration-300 shadow-md">
                Entrar
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600 border-t pt-4">
            ¿No tienes cuenta? <a href="registro.php" class="text-indigo-600 font-bold hover:underline">Regístrate gratis</a>
        </div>
    </div>
</body>
</html>