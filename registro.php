<?php
session_start();
require_once 'config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$nombre, $email, $pass])) {
        header("Location: index.php?registro=exito");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - ControlGastos</title>
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { width: 100%; max-width: 360px; border: none; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header text-center"><div class="card-title h4">Crear Usuario</div></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input class="form-input" type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-input" type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input class="form-input" type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-2">Registrar</button>
            </form>
        </div>
        <div class="card-footer text-center"><a href="index.php">Volver al Login</a></div>
    </div>
</body>
</html>
