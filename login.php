<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Iniciar sesión — ControlGastos</title>

<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">

<style>
<?php echo file_get_contents("assets/css/auth_common.css") ?: "
    body {
        background: #f6f8fb;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        font-family: Inter, system-ui, sans-serif;
    }
    .auth-card {
        width: 360px;
        background: #fff;
        padding: 32px;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    }
    .auth-title {
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: 12px;
        text-align: center;
    }
    .auth-subtitle {
        text-align: center;
        color: #666;
        font-size: .85rem;
        margin-bottom: 24px;
    }
    .auth-footer {
        text-align: center;
        margin-top: 12px;
        font-size: .8rem;
    }
    .auth-footer a { color: #7c3aed; }
"; ?>
</style>

</head>
<body>

<div class="auth-card">
    <div class="auth-title">ControlGastos</div>
    <div class="auth-subtitle">Inicia sesión para continuar</div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="toast toast-error"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="procesar_login.php">
        <div class="form-group">
            <label>Email</label>
            <input name="email" type="email" class="form-input" placeholder="tucorreo@ejemplo.com" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input name="password" type="password" class="form-input" placeholder="••••••••" required>
        </div>

        <button class="btn btn-primary btn-block" style="margin-top: 16px;">Entrar</button>
    </form>

    <div class="auth-footer">
        ¿No tienes cuenta? <a href="registro.php">Crear una cuenta</a>
    </div>
</div>

</body>
</html>
