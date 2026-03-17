<?php 
require_once 'config.php';
// La sesión ya se inicia y se comprueba en config.php
if (!isset($_SESSION['usuario_id'])) { redirect('login.php'); }

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

// NUEVO: Extraemos el árbol completo de categorías para dárselo a los gráficos
$stmtCats = $pdo->prepare("SELECT id, nombre, parent_id FROM categorias WHERE usuario_id = ?");
$stmtCats->execute([$uid]);
$categoriasArbol = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

$nombresMeses = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

include 'includes/header.php'; 
?>

<style>
    html, body { overflow-y: auto !important; height: auto !important; }
</style>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Regla 50/30/20</h2>
                    <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs font-bold uppercase tracking-wide">Análisis</span>
                </div>
                <p class="text-sm text-gray-500 mb-6 border-b border-gray-100 pb-4">Progreso sobre tus ingresos actuales.</p>
                <div id="progress-503020-container" class="flex-grow flex flex-col justify-center gap-2"><p class="text-gray-400 italic text-center">Generando gráficos...</p></div>
            </div>
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-xl font-bold text-gray-800">Movimientos Recientes</h2>
                    <a href="transacciones.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition font-bold shadow-md flex items-center gap-2">Ver todo / Nuevo</a>
                </div>
                <div id="lista-movimientos-recent" class="overflow-x-auto"><p class="text-gray-400 italic">Cargando...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- ========= TEMPLATES PARA JAVASCRIPT ========= -->
<template id="kpi-widget-template">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 transition hover:shadow-md">
        <p class="kpi-title text-sm text-gray-500 font-bold uppercase tracking-wider mb-1"></p>
        <p class="kpi-amount text-3xl font-extrabold"></p>
    </div>
</template>

<template id="movimiento-reciente-template">
    <li class="py-4 flex justify-between items-center hover:bg-gray-50 px-2 rounded transition">
        <div class="flex items-center gap-4">
            <div class="mov-icon w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"></div>
            <div>
                <p class="mov-descripcion font-bold text-gray-800 text-sm md:text-base"></p>
                <p class="text-xs text-gray-500 mt-0.5">
                    <span class="mov-fecha"></span> • 
                    <span class="mov-categoria bg-gray-100 px-1.5 py-0.5 rounded border"></span>
                </p>
            </div>
        </div>
        <span class="mov-importe font-extrabold text-sm md:text-base"></span>
    </li>
</template>

<template id="barra-progreso-template">
    <div class="barra-container mb-4 p-4 rounded-xl border border-gray-100">
        <div class="flex justify-between items-end mb-2">
            <span class="barra-titulo font-bold text-gray-800 text-sm"></span>
            <div class="text-right">
                <span class="barra-gastado text-sm font-extrabold text-gray-900"></span>
                <span class="barra-porcentaje text-xs text-gray-500 ml-1"></span>
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden shadow-inner">
            <div class="barra-progreso h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
        </div>
        <p class="barra-mensaje text-xs"></p>
    </div>
</template>
<!-- ========= FIN DE TEMPLATES ========= -->

<script>
const DIA_INICIO = <?= $dia_inicio ?>;
// Inyectamos el mapa de categorías de PHP a JavaScript
const categoriasArbol = <?= json_encode($categoriasArbol) ?>;

// MAGIA: Esta función trepa por el árbol hasta decirnos si es Necesidad, Deseo o Ahorro
function getRootCategoryName(catId) {
    if (!catId) return '';
    let currentId = catId;
    let rootName = '';
    let loop = 0; // Seguridad anti-cuelgues
    
    while(loop < 20) {
        const cat = categoriasArbol.find(c => c.id == currentId);
        if (!cat) break;
        rootName = cat.nombre; 
        if (!cat.parent_id) break; // Hemos llegado a la categoría reina (la raíz)
        currentId = cat.parent_id; 
        loop++;
    }
    return rootName.toLowerCase();
}

function alCambiarFechaManualDashboard() {
    document.getElementById('dashboardMesContable').value = '';
    cargarDashboard();
}

function limpiarFiltrosDashboard() {
    document.getElementById('dashboardMesContable').value = '';
    const hoy = new Date();
    let y = hoy.getFullYear(); let m = hoy.getMonth() + 1; let d = hoy.getDate();
    let fInicio, fFin;

    if (DIA_INICIO === 1) {
        fInicio = `${y}-${m.toString().padStart(2, '0')}-01`;
        let lastDay = new Date(y, m, 0).getDate();
        fFin = `${y}-${m.toString().padStart(2, '0')}-${lastDay.toString().padStart(2, '0')}`;
    } else {
        let currentPeriodMonth = m; let currentPeriodYear = y;
        if (d < DIA_INICIO) {
            currentPeriodMonth--;
            if (currentPeriodMonth === 0) { currentPeriodMonth = 12; currentPeriodYear--; }
        }
        fInicio = `${currentPeriodYear}-${currentPeriodMonth.toString().padStart(2, '0')}-${DIA_INICIO.toString().padStart(2, '0')}`;
        let nextMonth = currentPeriodMonth + 1; let nextYear = currentPeriodYear;
        if (nextMonth === 13) { nextMonth = 1; nextYear++; }
        let dFin = new Date(nextYear, nextMonth - 1, DIA_INICIO - 1);
        fFin = `${dFin.getFullYear()}-${(dFin.getMonth() + 1).toString().padStart(2, '0')}-${dFin.getDate().toString().padStart(2, '0')}`;
    }
    document.getElementById('dashboardFechaInicio').value = fInicio; document.getElementById('dashboardFechaFin').value = fFin;
    cargarDashboard();
}

function aplicarMesContable() {
    const mesVal = document.getElementById('dashboardMesContable').value;
    if (!mesVal) return;
    const [yearStr, monthStr] = mesVal.split('-');
    let year = parseInt(yearStr); let month = parseInt(monthStr);
    let fInicio, fFin;

    if (DIA_INICIO === 1) {
        fInicio = `${year}-${monthStr}-01`;
        let lastDay = new Date(year, month, 0).getDate();
        fFin = `${year}-${monthStr}-${lastDay.toString().padStart(2, '0')}`;
    } else {
        let prevMonth = month - 1; let prevYear = year;
        if (prevMonth === 0) { prevMonth = 12; prevYear--; }
        fInicio = `${prevYear}-${prevMonth.toString().padStart(2, '0')}-${DIA_INICIO.toString().padStart(2, '0')}`;
        let dFin = new Date(year, month - 1, DIA_INICIO - 1);
        let finM = (dFin.getMonth() + 1).toString().padStart(2, '0'); let finD = dFin.getDate().toString().padStart(2, '0');
        fFin = `${dFin.getFullYear()}-${finM}-${finD}`;
    }
    document.getElementById('dashboardFechaInicio').value = fInicio; document.getElementById('dashboardFechaFin').value = fFin;
    cargarDashboard();
}

async function cargarDashboard() {
    const fInicio = document.getElementById('dashboardFechaInicio').value;
    const fFin = document.getElementById('dashboardFechaFin').value;
    if(!fInicio || !fFin) return;
    if(fInicio > fFin) { alert("La fecha 'Desde' no puede ser mayor que 'Hasta'."); return; }

    try {
        const resKpis = await fetch(`controllers/DashboardRouter.php?action=getKpis&fecha_inicio=${fInicio}&fecha_fin=${fFin}`);
        const kpis = await resKpis.json();
        const ingresos = parseFloat(kpis.ingresos) || 0;
        const gastos = parseFloat(kpis.gastos) || 0;
        const balance = ingresos - gastos;

        const kpiContainer = document.getElementById('widget-kpis-container');
        const kpiTemplate = document.getElementById('kpi-widget-template');
        kpiContainer.innerHTML = ''; // Limpiamos el contenedor

        const createKpiWidget = (title, amount, amountColor, borderColor) => {
            const clone = kpiTemplate.content.cloneNode(true);
            const widget = clone.querySelector('div');
            widget.classList.add(borderColor);
            
            clone.querySelector('.kpi-title').textContent = title;
            
            const amountEl = clone.querySelector('.kpi-amount');
            amountEl.textContent = `${amount.toLocaleString('es-ES', {minimumFractionDigits: 2})}€`;
            amountEl.classList.add(amountColor);
            
            return clone;
        };

        kpiContainer.appendChild(createKpiWidget('Ingresos', ingresos, 'text-green-600', 'border-l-green-500'));
        kpiContainer.appendChild(createKpiWidget('Gastos', gastos, 'text-red-600', 'border-l-red-500'));
        kpiContainer.appendChild(createKpiWidget('Balance Total', balance, balance >= 0 ? 'text-indigo-600' : 'text-red-500', 'border-l-indigo-500'));
        
        await renderizarBarras(fInicio, fFin, ingresos, gastos);
    } catch (e) { console.error("Error en KPIs:", e); }

    try {
        const resMovs = await fetch(`controllers/TransaccionRouter.php?action=getAllLimit`);
        const movs = await resMovs.json();
        const containerMovs = document.getElementById('lista-movimientos-recent');
        const movTemplate = document.getElementById('movimiento-reciente-template');

        if (!movs || movs.length === 0 || movs.error) {
            containerMovs.innerHTML = '<p class="text-gray-500 italic text-center py-6">No hay movimientos registrados recientes.</p>'; return;
        }

        containerMovs.innerHTML = ''; // Limpiamos
        const ul = document.createElement('ul');
        ul.className = 'divide-y divide-gray-100';

        movs.forEach(m => {
            const clone = movTemplate.content.cloneNode(true);
            const importeValue = parseFloat(m.importe) || 0; 
            const isGasto = importeValue < 0;
            const fechaParts = m.fecha.split('-');
            const fechaFormato = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;

            const iconEl = clone.querySelector('.mov-icon');
            iconEl.textContent = isGasto ? '▼' : '▲';
            iconEl.classList.add(isGasto ? 'bg-red-400' : 'bg-green-400');

            clone.querySelector('.mov-descripcion').textContent = m.descripcion;
            clone.querySelector('.mov-fecha').textContent = fechaFormato;
            clone.querySelector('.mov-categoria').textContent = m.categoria_nombre || 'Por clasificar';
            
            const importeEl = clone.querySelector('.mov-importe');
            importeEl.textContent = `${Math.abs(importeValue).toLocaleString('es-ES', {minimumFractionDigits: 2})} €`;
            importeEl.classList.add(isGasto ? 'text-red-500' : 'text-green-500');

            ul.appendChild(clone);
        });
        containerMovs.appendChild(ul);
    } catch(e) { console.error("Error en Movimientos:", e); }
}

async function renderizarBarras(fInicio, fFin, ingresos, gastosTotalesKpi) {
    const container = document.getElementById('progress-503020-container');
    const barraTemplate = document.getElementById('barra-progreso-template');
    
    try {
        const resDist = await fetch(`controllers/DashboardRouter.php?action=getDistribucionGastos&fecha_inicio=${fInicio}&fecha_fin=${fFin}`);
        const distribucion = await resDist.json();
        
        let gastos = { necesidad: 0, deseo: 0, ahorro: 0, gasto: 0 };
        
        if (Array.isArray(distribucion)) { 
            distribucion.forEach(d => { 
                // Ahora usamos la magia de trepar por el árbol
                const rootName = getRootCategoryName(d.categoria_id);
                
                if (rootName.includes('necesidad') || rootName === 'necesidades') {
                    gastos.necesidad += parseFloat(d.total) || 0;
                } else if (rootName.includes('deseo') || rootName === 'deseos') {
                    gastos.deseo += parseFloat(d.total) || 0;
                } else if (rootName.includes('ahorro') || rootName.includes('inversion') || rootName.includes('inversión')) {
                    gastos.ahorro += parseFloat(d.total) || 0;
                }
            }); 
        }

        // Restamos lo que ya sabemos al Gasto Total
        let totalAsignado = gastos.necesidad + gastos.deseo + gastos.ahorro;
        gastos.gasto = Math.max(0, gastosTotalesKpi - totalAsignado);

        const crearBarraElemento = (titulo, gastado, limitePct, tipo) => {
            const porcentaje = ingresos > 0 ? (gastado / ingresos) * 100 : 0;
            const porcentajeVisual = Math.min(porcentaje, 100); 
            let colorBarra = 'bg-indigo-500', bgFondo = 'bg-gray-50', mensaje = '', txtColor = 'text-gray-500';

            if (tipo === 'necesidad' || tipo === 'deseo') {
                if (porcentaje <= limitePct && ingresos > 0) { colorBarra = 'bg-green-500'; mensaje = 'Dentro de lo recomendado.'; txtColor = 'text-green-600 font-semibold'; } 
                else if (porcentaje > limitePct && ingresos > 0) { colorBarra = 'bg-red-500'; bgFondo = 'bg-red-50'; mensaje = `¡Aviso! Superaste el ${limitePct}%.`; txtColor = 'text-red-600 font-bold'; }
                else { colorBarra = 'bg-gray-300'; mensaje = 'Sin ingresos registrados.'; txtColor = 'text-gray-400'; }
            } else if (tipo === 'ahorro') {
                if (porcentaje >= limitePct && ingresos > 0) { colorBarra = 'bg-indigo-500'; mensaje = '¡Meta alcanzada!'; txtColor = 'text-indigo-600 font-bold'; } 
                else if (ingresos > 0) { colorBarra = 'bg-yellow-400'; mensaje = `Aún falta para el ${limitePct}%.`; txtColor = 'text-yellow-600 font-semibold'; }
                else { colorBarra = 'bg-gray-300'; mensaje = 'Sin ingresos registrados.'; txtColor = 'text-gray-400'; }
            } else if (tipo === 'gasto') {
                if (gastado > 0) { colorBarra = 'bg-gray-400'; bgFondo = 'bg-gray-100'; mensaje = 'Gastos pendientes de asignar a una categoría correcta.'; txtColor = 'text-gray-600 font-bold'; }
                else { colorBarra = 'bg-gray-200'; mensaje = '¡Todo clasificado perfectamente!'; txtColor = 'text-gray-400'; }
            }

            const clone = barraTemplate.content.cloneNode(true);
            const containerEl = clone.querySelector('.barra-container');
            containerEl.classList.add(bgFondo);

            clone.querySelector('.barra-titulo').textContent = titulo;
            clone.querySelector('.barra-gastado').textContent = `${gastado.toLocaleString('es-ES', {minimumFractionDigits: 2})}€`;
            clone.querySelector('.barra-porcentaje').textContent = `/ ${porcentaje.toFixed(1)}%`;

            const progresoEl = clone.querySelector('.barra-progreso');
            progresoEl.classList.add(colorBarra);
            requestAnimationFrame(() => {
                progresoEl.style.width = `${porcentajeVisual}%`;
            });

            const mensajeEl = clone.querySelector('.barra-mensaje');
            mensajeEl.textContent = mensaje;
            mensajeEl.className = `barra-mensaje text-xs ${txtColor}`;

            return clone;
        };

        container.innerHTML = ''; // Limpiamos
        container.appendChild(crearBarraElemento('Necesidades (Límite 50%)', gastos.necesidad, 50, 'necesidad'));
        container.appendChild(crearBarraElemento('Deseos (Límite 30%)', gastos.deseo, 30, 'deseo'));
        container.appendChild(crearBarraElemento('Ahorro e Inversión (Meta 20%)', gastos.ahorro, 20, 'ahorro'));
        container.appendChild(crearBarraElemento('Otros / Por Clasificar', gastos.gasto, 100, 'gasto'));
        
        const summaryEl = document.createElement('div');
        summaryEl.className = "mt-4 pt-4 border-t border-gray-100 flex justify-between items-center";
        summaryEl.innerHTML = `<span class="text-xs text-gray-400 uppercase tracking-wide font-bold">Base de cálculo (Ingresos)</span><span class="font-extrabold text-gray-700">${ingresos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span>`;
        container.appendChild(summaryEl);

    } catch(e) { console.error("Error en Barras:", e); }
}

document.addEventListener('DOMContentLoaded', () => {
    limpiarFiltrosDashboard();
});
</script>

<?php include 'includes/footer.php'; ?>