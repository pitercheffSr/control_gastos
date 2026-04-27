<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/AuthController.php';

$action = $_GET['action'] ?? '';
$auth = new AuthController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. INICIAR SESIÓN ---
    if ($action === 'login') {
        try {
            $validData = AuthMiddleware::validateInput($_POST, [
                'usuario' => 'required',
                'password' => 'required'
            ], false);

            $usuario_limpio = strtolower(preg_replace('/\s+/', '', $validData['usuario']));
        $email = $usuario_limpio . '@cgastos.mi';

            $user = $auth->login($email, $validData['password']);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol'] = $user['rol'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_reciente'] = true;

            header('Location: ../dashboard.php');
            exit;
        } else {
            $_SESSION['auth_error'] = 'El usuario o la contraseña son incorrectos.';
        }
        } catch (Exception $e) {
            $_SESSION['auth_error'] = 'Por favor, introduce tu nombre de usuario y contraseña.';
        }
        header('Location: ../login.php');
        exit;
    }

    // --- 2. REGISTRO ---
    elseif ($action === 'register') {
        try {
            $validData = AuthMiddleware::validateInput($_POST, [
                'usuario' => 'required',
                'password' => 'required|min:6',
                'confirm_password' => 'required',
                'captcha_respuesta' => 'required|numeric'
            ], false);

            $usuario = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($validData['usuario']));
            $respuesta_usuario = (int)$validData['captcha_respuesta'];
            $respuesta_correcta = (int)($_SESSION['captcha_correcto'] ?? -1);

            if ($respuesta_usuario !== $respuesta_correcta) {
                $_SESSION['auth_error'] = 'Seguridad anti-bot fallida. La suma matemática no es correcta.';
            } elseif (empty($usuario)) {
                $_SESSION['auth_error'] = 'Por favor, introduce un nombre de usuario válido.';
            } elseif ($validData['password'] !== $validData['confirm_password']) {
                $_SESSION['auth_error'] = 'Las contraseñas no coinciden.';
            } else {
            $email = $usuario . '@cgastos.mi';
                $registro = $auth->register($usuario, $email, $validData['password']);

            if (isset($registro['id'])) {
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $registro['id'];
                    $_SESSION['usuario_nombre'] = $usuario;
                    $_SESSION['usuario_rol'] = 'usuario';
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_reciente'] = true;
                }

                // Pasamos el código de recuperación de vuelta a la vista
                $_SESSION['auth_success_code'] = $registro['recovery_code'];
                header('Location: ../registro.php');
                exit;
            } else {
                $_SESSION['auth_error'] = $registro['error'] ?? 'Hubo un error desconocido en el registro.';
            }
        }
        } catch (Exception $e) {
            $_SESSION['auth_error'] = $e->getMessage();
        }
        header('Location: ../registro.php');
        exit;
    }

    // --- 3. RECUPERAR CONTRASEÑA ---
    elseif ($action === 'recover') {
        $usuario = $_POST['usuario'] ?? '';
        $codigo = $_POST['codigo'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($usuario) || empty($codigo) || empty($password)) {
            $_SESSION['auth_error'] = 'Todos los campos son obligatorios.';
        } elseif (strlen($password) < 6) {
            $_SESSION['auth_error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } else {
            $usuario_limpio = strtolower(preg_replace('/\s+/', '', $usuario));
            $email = $usuario_limpio . '@cgastos.mi';
            $codigo_limpio = strtoupper(trim($codigo));

            if ($auth->resetPasswordWithCode($email, $codigo_limpio, $password)) {
                $_SESSION['auth_success'] = '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión.';
            } else {
                $_SESSION['auth_error'] = 'El nombre de usuario o el código de recuperación son incorrectos.';
            }
        }
        header('Location: ../recuperar.php');
        exit;
    }
}

// --- 4. CERRAR SESIÓN (GET) ---
if ($action === 'logout') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vaciar todas las variables de sesión
    $_SESSION = [];

    // Destruir la cookie de sesión en el navegador
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header('Location: ../login.php');
    exit;
}
?>
