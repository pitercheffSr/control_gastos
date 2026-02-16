<?php
session_start();
require_once 'config.php';
require_once 'db.php';

// Si ya hay sesión, al Dashboard directo
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Email o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - ControlGastos</title>
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { width: 100%; max-width: 360px; border: none; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header text-center">
            <div class="card-title h4">ControlGastos</div>
        </div>
        <div class="card-body">
            <?php if($error): ?> <div class="toast toast-error mb-2"><?= $error ?></div> <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-input" type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input class="form-input" type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-2">Entrar</button>
            </form>
        </div>
        <div class="card-footer text-center">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>
