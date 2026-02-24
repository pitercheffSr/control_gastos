<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$uid = $_SESSION['usuario_id'];

$stmtUser = $pdo->prepare("SELECT dia_inicio_mes FROM usuarios WHERE id = ?");
$stmtUser->execute([$uid]);
$uData = $stmtUser->fetch();
$dia_inicio = $uData ? (int)$uData['dia_inicio_mes'] : 1;

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
                    <input type="month" id="dashboardMesContable" onchange="aplicarMesContable()" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-blue-50/50 border border-blue-100 p-5 rounded-2xl flex items-start gap-4">
                <div class="bg-blue-100 p-2.5 rounded-xl text-blue-600 mt-0.5"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg></div>
                <div>
                    <h3 class="text-blue-800 font-bold">Importación desde Excel</h3>
                    <p class="text-blue-600/80 text-sm mt-1 leading-relaxed">Próximamente podrás importar tu historial bancario directamente. <strong class="text-blue-700">Admitirá un máximo de 3 meses de antigüedad</strong>.</p>
                </div>
            </div>
            <div class="bg-orange-50/50 border border-orange-100 p-5 rounded-2xl flex items-start gap-4 relative overflow-hidden">
                <div class="bg-orange-100 p-2.5 rounded-xl text-orange-600 mt-0.5"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                <div>
                    <h3 class="text-orange-800 font-bold">Privacidad Temporal</h3>
                    <p class="text-orange-600/80 text-sm mt-1 leading-relaxed">Tu cuenta y tus datos se eliminarán de forma irreversible el <strong><?= $fecha_borrado_str ?? '...'; ?></strong> (4 meses de retención máxima).</p>
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

<script>
const DIA_INICIO = <?= $dia_inicio ?>;

function alCambiarFechaManualDashboard() {
    document.getElementById('dashboardMesContable').value = '';
    cargarDashboard();
}

function limpiarFiltrosDashboard() {
    document.getElementById('dashboardMesContable').value = '';
    
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = (hoy.getMonth() + 1).toString().padStart(2, '0');
    const lastDay = new Date(year, hoy.getMonth() + 1, 0).getDate().toString().padStart(2, '0');
    
    document.getElementById('dashboardFechaInicio').value = `${year}-${month}-01`;
    document.getElementById('dashboardFechaFin').value = `${year}-${month}-${lastDay}`;
    
    cargarDashboard();
}

function aplicarMesContable() {
    const mesVal = document.getElementById('dashboardMesContable').value;
    if (!mesVal) return;
    
    const [yearStr, monthStr] = mesVal.split('-');
    let year = parseInt(yearStr);
    let month = parseInt(monthStr);

    let fInicio, fFin;
    if (DIA_INICIO === 1) {
        fInicio = `${year}-${monthStr}-01`;
        let lastDay = new Date(year, month, 0).getDate();
        fFin = `${year}-${monthStr}-${lastDay.toString().padStart(2, '0')}`;
    } else {
        let prevMonth = month - 1;
        let prevYear = year;
        if (prevMonth === 0) { prevMonth = 12; prevYear--; }
        
        fInicio = `${prevYear}-${prevMonth.toString().padStart(2, '0')}-${DIA_INICIO.toString().padStart(2, '0')}`;
        
        let dFin = new Date(year, month - 1, DIA_INICIO - 1);
        let finM = (dFin.getMonth() + 1).toString().padStart(2, '0');
        let finD = dFin.getDate().toString().padStart(2, '0');
        fFin = `${dFin.getFullYear()}-${finM}-${finD}`;
    }

    document.getElementById('dashboardFechaInicio').value = fInicio;
    document.getElementById('dashboardFechaFin').value = fFin;
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

        document.getElementById('widget-kpis-container').innerHTML = `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 transition hover:shadow-md">
                <p class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-1">Ingresos</p>
                <p class="text-3xl font-extrabold text-green-600">${ingresos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-red-500 transition hover:shadow-md">
                <p class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-1">Gastos</p>
                <p class="text-3xl font-extrabold text-red-600">${gastos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500 transition hover:shadow-md">
                <p class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-1">Balance Total</p>
                <p class="text-3xl font-extrabold ${balance >= 0 ? 'text-indigo-600' : 'text-red-500'}">${balance.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</p>
            </div>
        `;
        
        await renderizarBarras(fInicio, fFin, ingresos);
    } catch (e) { console.error("Error en KPIs:", e); }

    try {
        const resMovs = await fetch(`controllers/TransaccionRouter.php?action=getAllLimit`);
        const movs = await resMovs.json();
        const containerMovs = document.getElementById('lista-movimientos-recent');
        if (!movs || movs.length === 0 || movs.error) {
            containerMovs.innerHTML = '<p class="text-gray-500 italic text-center py-6">No hay movimientos registrados recientes.</p>'; return;
        }

        let html = '<ul class="divide-y divide-gray-100">';
        movs.forEach(m => {
            const importeValue = parseFloat(m.importe) || 0; 
            const isGasto = importeValue < 0;
            const fechaParts = m.fecha.split('-');
            const fechaFormato = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;

            html += `
                <li class="py-4 flex justify-between items-center hover:bg-gray-50 px-2 rounded transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white ${isGasto ? 'bg-red-400' : 'bg-green-400'}">${isGasto ? '▼' : '▲'}</div>
                        <div><p class="font-bold text-gray-800 text-sm md:text-base">${m.descripcion}</p><p class="text-xs text-gray-500 mt-0.5">${fechaFormato} • <span class="bg-gray-100 px-1.5 py-0.5 rounded border">${m.categoria_nombre || 'Sin categoría'}</span></p></div>
                    </div>
                    <span class="font-extrabold text-sm md:text-base ${isGasto ? 'text-red-500' : 'text-green-500'}">${importeValue.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span>
                </li>`;
        });
        html += '</ul>';
        containerMovs.innerHTML = html;
    } catch(e) { console.error("Error en Movimientos:", e); }
}

async function renderizarBarras(fInicio, fFin, ingresos) {
    const container = document.getElementById('progress-503020-container');
    
    try {
        const resDist = await fetch(`controllers/DashboardRouter.php?action=getDistribucionGastos&fecha_inicio=${fInicio}&fecha_fin=${fFin}`);
        const distribucion = await resDist.json();
        
        // Obtenemos los gastos estricta y puramente de la base de datos
        let gastos = { necesidad: 0, deseo: 0, ahorro: 0 };
        if (Array.isArray(distribucion)) { 
            distribucion.forEach(d => { 
                if(gastos[d.tipo] !== undefined) gastos[d.tipo] = parseFloat(d.total) || 0; 
            }); 
        }

        const crearBarraHTML = (titulo, gastado, limitePct, tipo) => {
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
            }

            return `
                <div class="mb-4 p-4 rounded-xl ${bgFondo} border border-gray-100">
                    <div class="flex justify-between items-end mb-2"><span class="font-bold text-gray-800 text-sm">${titulo}</span><div class="text-right"><span class="text-sm font-extrabold text-gray-900">${gastado.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span><span class="text-xs text-gray-500 ml-1">/ ${porcentaje.toFixed(1)}%</span></div></div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden shadow-inner"><div class="${colorBarra} h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: ${porcentajeVisual}%"></div></div>
                    <p class="text-xs ${txtColor}">${mensaje}</p>
                </div>`;
        };

        container.innerHTML = `
            ${crearBarraHTML('Necesidades (Límite 50%)', gastos.necesidad, 50, 'necesidad')}
            ${crearBarraHTML('Deseos (Límite 30%)', gastos.deseo, 30, 'deseo')}
            ${crearBarraHTML('Ahorro e Inversión (Meta 20%)', gastos.ahorro, 20, 'ahorro')}
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center"><span class="text-xs text-gray-400 uppercase tracking-wide font-bold">Base de cálculo</span><span class="font-extrabold text-gray-700">${ingresos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span></div>`;
    } catch(e) { console.error("Error en Barras:", e); }
}

document.addEventListener('DOMContentLoaded', () => {
    limpiarFiltrosDashboard();
});
</script>

<?php include 'includes/footer.php'; ?>