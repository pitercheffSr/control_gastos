<?php 
require_once 'config.php';
// La sesión ya se inicia y se comprueba en config.php
if (!isset($_SESSION['usuario_id'])) { redirect('index.php'); }

$uid = $_SESSION['usuario_id'];
$mensaje = '';
$nuevo_codigo = '';
$error_seguridad = '';
$error_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dia_inicio'])) {
        $dia = (int)$_POST['dia_inicio'];
        if ($dia >= 1 && $dia <= 28) {
            $stmt = $pdo->prepare("UPDATE usuarios SET dia_inicio_mes = ? WHERE id = ?");
            if ($stmt->execute([$dia, $uid])) {
                $mensaje = 'Día de inicio actualizado correctamente.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'new_recovery_code') {
        $password = $_POST['current_password'] ?? '';
        require_once 'controllers/AuthController.php';
        $auth = new AuthController($pdo);
        
        if ($auth->verifyPasswordForUser($uid, $password)) {
            $nuevo_codigo = $auth->generateNewRecoveryCode($uid);
            $mensaje = 'Se ha generado un nuevo código de recuperación con éxito.';
        } else {
            $error_seguridad = 'La contraseña actual es incorrecta.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        require_once 'controllers/AuthController.php';
        $auth = new AuthController($pdo);
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_password = 'Todos los campos son obligatorios para cambiar la contraseña.';
        } elseif ($new_password !== $confirm_password) {
            $error_password = 'Las contraseñas nuevas no coinciden.';
        } elseif (strlen($new_password) < 6) {
            $error_password = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif (!$auth->verifyPasswordForUser($uid, $current_password)) {
            $error_password = 'La contraseña actual ingresada es incorrecta.';
        } elseif ($auth->updatePassword($uid, $new_password)) {
            $mensaje = '¡Contraseña actualizada correctamente!';
        }
    }
}

$stmt = $pdo->prepare("SELECT nombre, email, dia_inicio_mes, fecha_borrado FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$dia_actual = $user ? (int)$user['dia_inicio_mes'] : 1;
$nombre = $user['nombre'] ?? 'Usuario';
$email = $user['email'] ?? '';
$fecha_borrado = ($user && !empty($user['fecha_borrado'])) ? date('d/m/Y', strtotime($user['fecha_borrado'])) : 'Desconocida';

include 'includes/header.php'; 
?>
<div class="container mx-auto p-6 max-w-5xl min-h-screen pb-24">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Mi Perfil</h1>
        <p class="text-sm text-gray-500 mt-1">Gestiona tu información y preferencias de la aplicación.</p>
    </div>
    
    <?php if($mensaje): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded-r-xl shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="text-sm text-green-700 font-bold"><?= htmlspecialchars($mensaje) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Columna Izquierda: Resumen de cuenta -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                <div class="flex items-center gap-4 mb-6 mt-2">
                    <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-2xl font-extrabold">
                        <?= strtoupper(substr($nombre, 0, 1)) ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($nombre) ?></h2>
                        <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-bold border border-gray-200">
                            Usuario Estándar
                        </span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Nombre de usuario interno</p>
                        <p class="text-sm font-medium text-gray-700 bg-gray-50 p-2.5 rounded-xl border border-gray-100 break-all"><?= htmlspecialchars($email) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Caducidad de la cuenta</p>
                        <div class="flex items-center gap-2 text-sm font-medium text-orange-700 bg-orange-50 p-2.5 rounded-xl border border-orange-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <?= $fecha_borrado ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna Derecha: Configuración -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gray-200"></div>
                <div class="flex items-start gap-4 mb-6 mt-2">
                    <div class="bg-blue-50 p-3 rounded-2xl text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Configuración del Periodo</h2>
                        <p class="text-sm text-gray-500 mt-1 leading-relaxed">Elige qué día empieza tu mes financiero. Por ejemplo, si cobras el día 25, al filtrar por "Febrero" en el Dashboard, la app calculará tus movimientos del 25 de Enero al 24 de Febrero automáticamente.</p>
                    </div>
                </div>
                
                <form method="POST" class="space-y-6 bg-gray-50 p-6 rounded-2xl border border-gray-100">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Día de inicio del mes contable:</label>
                        <select name="dia_inicio" class="w-full border border-gray-300 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-gray-700 bg-white transition-shadow shadow-sm cursor-pointer">
                            <?php for($i=1; $i<=28; $i++): ?>
                                <option value="<?= $i ?>" <?= $i === $dia_actual ? 'selected' : '' ?>>Día <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="text-xs text-gray-400 mt-3 flex items-center gap-1 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Limitado al día 28 para asegurar compatibilidad con febrero.
                        </p>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" class="w-full md:w-auto px-8 bg-indigo-600 text-white font-bold py-3.5 rounded-xl hover:bg-indigo-700 transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Guardar Preferencias
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Nueva Tarjeta de Seguridad -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 relative overflow-hidden mt-6">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-red-400"></div>
                <div class="flex items-start gap-4 mb-6 mt-2">
                    <div class="bg-red-50 p-3 rounded-2xl text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Código de Recuperación</h2>
                        <p class="text-sm text-gray-500 mt-1 leading-relaxed">Si perdiste tu código original, puedes generar uno nuevo. Por seguridad, <strong>el código anterior dejará de funcionar</strong> inmediatamente.</p>
                    </div>
                </div>

                <?php if ($error_seguridad): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl">
                        <p class="text-sm text-red-700 font-bold"><?= htmlspecialchars($error_seguridad) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($nuevo_codigo): ?>
                    <div class="bg-yellow-50 border-2 border-yellow-400 p-6 rounded-2xl shadow-sm text-left mb-4">
                        <p class="text-yellow-800 font-extrabold mb-2 flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> GUARDA ESTE NUEVO CÓDIGO</p>
                        <p class="text-sm text-yellow-700 mb-4">Este es tu nuevo código. El anterior ha sido invalidado permanentemente.</p>
                        <div class="bg-white p-4 rounded-xl border border-yellow-200 font-mono text-3xl tracking-widest text-center text-gray-900 font-black select-all">
                            <?= htmlspecialchars($nuevo_codigo) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                        <input type="hidden" name="action" value="new_recovery_code">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Verifica tu identidad</label>
                            <input type="password" name="current_password" required placeholder="Tu contraseña actual" autocomplete="current-password" class="w-full border border-gray-300 rounded-xl p-3.5 focus:ring-2 focus:ring-red-500 outline-none font-medium text-gray-700 bg-white transition-shadow shadow-sm">
                        </div>
                        <button type="submit" onclick="return confirm('¿Estás seguro de que quieres invalidar tu código anterior y generar uno nuevo?');" class="w-full md:w-auto px-6 bg-red-600 text-white font-bold py-3.5 rounded-xl hover:bg-red-700 transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Generar Nuevo Código
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Nueva Tarjeta: Cambiar Contraseña -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 relative overflow-hidden mt-6">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gray-800"></div>
                <div class="flex items-start gap-4 mb-6 mt-2">
                    <div class="bg-gray-100 p-3 rounded-2xl text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Cambiar Contraseña</h2>
                        <p class="text-sm text-gray-500 mt-1 leading-relaxed">Actualiza tu contraseña para mantener tu cuenta segura.</p>
                    </div>
                </div>

                <?php if ($error_password): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl">
                        <p class="text-sm text-red-700 font-bold"><?= htmlspecialchars($error_password) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="bg-gray-50 p-6 rounded-2xl border border-gray-100 space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Contraseña actual</label>
                        <input type="password" name="current_password" required placeholder="••••••••" autocomplete="current-password" class="w-full border border-gray-300 rounded-xl p-3.5 focus:ring-2 focus:ring-gray-800 outline-none font-medium text-gray-700 bg-white transition-shadow shadow-sm">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Nueva contraseña</label>
                            <input type="password" name="new_password" required placeholder="Mínimo 6 caracteres" autocomplete="new-password" class="w-full border border-gray-300 rounded-xl p-3.5 focus:ring-2 focus:ring-gray-800 outline-none font-medium text-gray-700 bg-white transition-shadow shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Repetir nueva contraseña</label>
                            <input type="password" name="confirm_password" required placeholder="Mínimo 6 caracteres" autocomplete="new-password" class="w-full border border-gray-300 rounded-xl p-3.5 focus:ring-2 focus:ring-gray-800 outline-none font-medium text-gray-700 bg-white transition-shadow shadow-sm">
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full md:w-auto px-8 bg-gray-800 text-white font-bold py-3.5 rounded-xl hover:bg-gray-900 transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>
<?php include 'includes/footer.php'; ?>