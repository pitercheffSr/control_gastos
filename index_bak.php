<?php
include_once "config.php";

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Login — ControlGastos</title>
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
<style>
body { display:flex;align-items:center;justify-content:center;height:100vh;background:#f5f6fa;font-family:Inter,sans-serif; }
.login-card { width:350px;padding:30px;border-radius:12px;background:white;box-shadow:0 8px 25px rgba(0,0,0,0.10); }
.brand { text-align:center;font-size:1.4rem;margin-bottom:20px; }
</style>
</head>
<body>

<div class="login-card">
    <h3 class="brand">ControlGastos</h3>

    <?php if (isset($_SESSION['error_login'])) : ?>
        <div class="toast toast-error"><?= $_SESSION['error_login'] ?></div>
        <?php unset($_SESSION['error_login']); ?>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST">
        <div class="form-group">
            <label>Email</label>
            <input class="form-input" type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input class="form-input" type="password" name="password" required>
        </div>
        <button class="btn btn-primary btn-block mt-2" type="submit">Entrar</button>
    </form>
</div>

</body>
</html>
