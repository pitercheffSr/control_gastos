<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$uid = $_SESSION['usuario_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dia_inicio'])) {
    $dia = (int)$_POST['dia_inicio'];
    if ($dia >= 1 && $dia <= 28) {
        $stmt = $pdo->prepare("UPDATE usuarios SET dia_inicio_mes = ? WHERE id = ?");
        if ($stmt->execute([$dia, $uid])) {
            $mensaje = 'Día de inicio actualizado correctamente.';
        }
    }
}

$stmt = $pdo->prepare("SELECT dia_inicio_mes FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$dia_actual = $user ? (int)$user['dia_inicio_mes'] : 1;

include 'includes/header.php'; 
?>
<div class="container mx-auto p-6 max-w-2xl min-h-screen">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-6">Mi Perfil</h1>
    
    <?php if($mensaje): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 shadow-sm">
            <strong class="font-bold">¡Hecho!</strong>
            <span class="block sm:inline"><?= $mensaje ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Configuración del Periodo</h2>
        <p class="text-sm text-gray-500 mb-6">Elige qué día empieza tu mes financiero. Por ejemplo, si cobras el día 25, al filtrar por "Febrero" la app calculará los gastos del 25 de Enero al 24 de Febrero.</p>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Día de inicio del mes:</label>
                <select name="dia_inicio" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-gray-700">
                    <?php for($i=1; $i<=28; $i++): ?>
                        <option value="<?= $i ?>" <?= $i === $dia_actual ? 'selected' : '' ?>>Día <?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <p class="text-xs text-gray-400 mt-2">* Limitado al día 28 para asegurar compatibilidad con todos los meses del año.</p>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition shadow-md">
                Guardar Cambios
            </button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>