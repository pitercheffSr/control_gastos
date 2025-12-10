<?php
// Cargar configuración global (sesiones unificadas)
include_once "config.php";

// Si el usuario YA está logueado → ir al dashboard
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
body {
    background: #f5f6fa;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Inter, sans-serif;
}
.login-card {
    width: 360px;
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.brand {
    text-align: center;
    font-size: 1.6rem;
    margin-bottom: 25px;
}
</style>

</head>

<body>

<div class="login-card">

    <h3 class="brand">ControlGastos</h3>

    <?php if (isset($_SESSION['error_login'])) : ?>
        <div class="toast toast-error"><?=$_SESSION['error_login']?></div>
        <?php unset($_SESSION['error_login']); ?>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST">

        <div class="form-group">
            <label>Email</label>
            <input class="form-input" type="email" name="email" required placeholder="tu@correo.com">
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input class="form-input" type="password" name="password" required placeholder="********">
        </div>

        <button class="btn btn-primary btn-block mt-2" type="submit">
            Entrar
        </button>

    </form>

</div>

</body>
</html>
