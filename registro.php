<?php
require_once 'config.php';
require_once 'controllers/AuthController.php';

if (isset($_SESSION['user_id'])) redirect('dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController($pdo);
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    $resultado = $auth->registro($nombre, $email, $pass);

    if ($resultado === true) {
        $success = "¡Cuenta creada con éxito! Ahora puedes iniciar sesión.";
    } else {
        $error = $resultado;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - ControlGastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fc; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 100%; max-width: 400px; border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); border-radius: 10px; }
    </style>
</head>
<body>
    <div class="card card-login p-4">
        <div class="text-center mb-4">
            <h3 class="text-primary fw-bold">Crear Cuenta</h3>
            <p class="text-muted">Únete y organiza tus gastos</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success py-2">
                <?= $success ?>
                <div class="mt-2"><a href="index.php" class="btn btn-sm btn-success w-100">Ir al Login</a></div>
            </div>
        <?php else: ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Registrarse</button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <small class="text-muted">¿Ya tienes cuenta?</small><br>
            <a href="index.php" class="text-decoration-none fw-bold">Inicia Sesión</a>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>