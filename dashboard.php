<?php
session_start(); // <--- CRÍTICO: SIEMPRE PRIMERO
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
// Asegúrate de que DashboardController.php es el que me pasaste antes
require_once __DIR__ . '/controllers/DashboardController.php'; 

$dashController = new DashboardController($pdo);
$resumen = $dashController->obtenerResumen();

include __DIR__ . '/includes/header.php';
?>

<div class="columns">
    <div class="column col-12">
        <div class="card">
            <div class="card-header">Resumen</div>
            <div class="card-body">
                <h3>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></h3>
                <p>Balance: <strong><?= number_format($resumen['balance'], 2) ?> €</strong></p>
                <canvas id="chartGastos" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Tu código Chart.js aquí
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>