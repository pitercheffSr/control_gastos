<?php
require_once 'config.php';
require_once 'controllers/AuthController.php';

// Si ya está logueado, ir al dashboard
if (isset($_SESSION['user_id'])) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($pdo);
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($auth->login($email, $pass)) {
        redirect('dashboard.php');
    } else {
        $error = "Credenciales incorrectas.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ControlGastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fc; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 100%; max-width: 400px; border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border-radius: 10px; }
    </style>
</head>
<body>
    <div class="card card-login p-4">
        <div class="text-center mb-4">
            <h3 class="text-primary fw-bold">Finanzas 50/30/20</h3>
            <p class="text-muted">Inicia sesión para continuar</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@ejemplo.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="******">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Entrar</button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <small class="text-muted">¿No tienes cuenta?</small><br>
            <a href="registro.php" class="text-decoration-none fw-bold">Crear Cuenta Nueva</a>
        </div>
    </div>
</body>
</html>