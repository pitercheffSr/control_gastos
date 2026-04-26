<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['usuario_id'];

$stmtUser = $pdo->prepare("SELECT dia_inicio_mes, fecha_borrado FROM usuarios WHERE id = ?");
$stmtUser->execute([$uid]);
$uData = $stmtUser->fetch();
$dia_inicio = $uData ? (int)$uData['dia_inicio_mes'] : 1;

$fecha_borrado_str = '...';
if ($uData && !empty($uData['fecha_borrado'])) {
    $fecha_borrado_str = date('d/m/Y', strtotime($uData['fecha_borrado']));
}

$stmtMeses = $pdo->prepare("
    SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as mes_val
    FROM transacciones
    WHERE usuario_id = ?
    ORDER BY mes_val DESC
");
$stmtMeses->execute([$uid]);
$mesesDisponibles = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

// LA SOLUCIÓN ESTÁ AQUÍ: Ahora el panel "ve" tus categorías Y las fijas del sistema (usuario_id IS NULL)
$stmtCats = $pdo->prepare("SELECT id, nombre, parent_id FROM categorias WHERE usuario_id = ? OR usuario_id IS NULL");
$stmtCats->execute([$uid]);
$categoriasArbol = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

$nombresMeses = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

include 'includes/header.php';
?>

<!-- 1. Incluir la librería de gráficos (Chart.js) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- CSS Externo -->
<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="w-full bg-gray-50 min-h-screen pb-32 pt-6 px-4 md:px-6">
    <div class="container mx-auto max-w-6xl">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800">Panel de Control</h1>
                <p class="text-sm text-gray-500 mt-1">Tu resumen financiero preciso.</p>
            </div>

            <div class="bg-white p-2 rounded-xl border shadow-sm flex flex-wrap items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2 border-r border-gray-200 pr-3">
                    <span class="text-sm font-bold text-gray-600 pl-2">Mes Contable:</span>
                    <select id="dashboardMesContable" onchange="aplicarMesContable()" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
                        <option value="">Seleccionar...</option>
                        <?php foreach($mesesDisponibles as $m):
                            $partes = explode('-', $m['mes_val']);
                            $nombreMostrar = $nombresMeses[$partes[1]] . ' ' . $partes[0];
                        ?>
                            <option value="<?= $m['mes_val'] ?>"><?= $nombreMostrar ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center gap-2 pl-1">
                    <span class="text-sm font-bold text-gray-600">Desde:</span>
                    <input type="date" id="dashboardFechaInicio" onchange="alCambiarFechaManualDashboard()" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
                </div>
                <div class="flex items-center gap-2 pr-2">
                    <span class="text-sm font-bold text-gray-600">Hasta:</span>
                    <input type="date" id="dashboardFechaFin" onchange="alCambiarFechaManualDashboard()" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
                </div>

                <button onclick="limpiarFiltrosDashboard()" class="text-gray-500 hover:text-indigo-600 font-bold px-4 py-1.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition border border-gray-100 ml-auto md:ml-0">
                    Limpiar
                </button>
            </div>
        </div>

        <div class="mb-8">
            <div class="bg-orange-50/50 border border-orange-100 p-5 rounded-2xl flex items-start gap-4 relative overflow-hidden">
                <div class="bg-orange-100 p-2.5 rounded-xl text-orange-600 mt-0.5"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                <div>
                    <h3 class="text-orange-800 font-bold">Privacidad Temporal</h3>
                    <p class="text-orange-600/80 text-sm mt-1 leading-relaxed">Tu cuenta y tus datos se eliminarán de forma irreversible el <strong><?= $fecha_borrado_str ?></strong> (4 meses de retención máxima).</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="widget-kpis-container">
            <p class="text-gray-400 italic col-span-3">Calculando métricas...</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Regla 50/30/20</h2>
                    <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wide">Análisis</span>
                </div>
                <p class="text-sm text-gray-500 mb-6 border-b border-gray-100 pb-4">Progreso sobre tus ingresos actuales.</p>
                <div id="progress-503020-container" class="flex-grow flex flex-col justify-center gap-2"><p class="text-gray-400 italic text-center">Generando gráficos...</p></div>
            </div>
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Distribución de Gastos</h2>
                    <span class="bg-purple-50 text-purple-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wide">Por Categoría</span>
                </div>
                <p class="text-sm text-gray-500 mb-6 border-b border-gray-100 pb-4">Top 6 gastos en el periodo seleccionado.</p>
                <div id="donut-chart-container" class="relative flex-grow flex items-center justify-center" style="min-height: 250px;">
                    <canvas id="gastosDonutChart"></canvas>
                </div>
            </div>
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Evolución del Balance</h2>
                    <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wide">Histórico</span>
                </div>
                <p class="text-sm text-gray-500 mb-6 border-b border-gray-100 pb-4">Balance neto acumulado a lo largo del tiempo.</p>
                <div id="balance-chart-container" class="relative flex-grow flex items-center justify-center" style="min-height: 300px;">
                    <canvas id="balanceLineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Nueva fila para el gráfico de balance, si se desea que ocupe todo el ancho -->
        <div class="grid grid-cols-1 gap-8 mt-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-xl font-bold text-gray-800">Movimientos Recientes</h2>
                    <a href="transacciones.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition font-bold shadow-md flex items-center gap-2">Ver todo / Nuevo</a>
                </div>
                <div id="lista-movimientos-recent" class="overflow-x-auto"><p class="text-gray-400 italic">Cargando...</p></div>
            </div>
        </div>
    </div>
</div>

<script>
const DIA_INICIO = <?= $dia_inicio ?>;
// Inyectamos el mapa de categorías de PHP a JavaScript
const categoriasArbol = <?= json_encode($categoriasArbol) ?>;
</script>
<!-- JS Externo -->
<script src="assets/js/dashboard.js"></script>

<?php include 'includes/footer.php'; ?>
