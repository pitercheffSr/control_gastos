<?php include 'includes/header.php'; ?>

<style>
    /* Forzamos el scroll nativo del navegador para evitar bloqueos en pantallas divididas */
    html, body {
        overflow-y: auto !important;
        height: auto !important;
    }
</style>

<div class="w-full bg-gray-50 min-h-screen pb-32 pt-6 px-4 md:px-6">
    <div class="container mx-auto max-w-6xl">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800">Panel de Control</h1>
                <p class="text-sm text-gray-500 mt-1">Tu resumen financiero automático.</p>
            </div>
            <div class="bg-white p-2 rounded-xl border shadow-sm flex items-center gap-2">
                <span class="text-sm font-bold text-gray-600 pl-2">Mes:</span>
                <input type="month" id="dashboardFilterMonth" value="<?= date('Y-m') ?>" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
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
                
                <div id="progress-503020-container" class="flex-grow flex flex-col justify-center gap-2">
                    <p class="text-gray-400 italic text-center">Generando gráficos...</p>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-xl font-bold text-gray-800">Movimientos Recientes</h2>
                    <a href="transacciones.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition font-bold shadow-md flex items-center gap-2">
                        Ver todo / Nuevo
                    </a>
                </div>
                <div id="lista-movimientos-recent" class="overflow-x-auto">
                    <p class="text-gray-400 italic">Cargando...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================
// MOTOR DEL DASHBOARD (Todo unificado aquí)
// ==========================================

async function cargarDashboard() {
    const mes = document.getElementById('dashboardFilterMonth').value;
    
    try {
        // 1. OBTENEMOS INGRESOS Y GASTOS (KPIs)
        const resKpis = await fetch(`controllers/DashboardRouter.php?action=getKpis&mes=${mes}`);
        const kpis = await resKpis.json();
        
        const ingresos = parseFloat(kpis.ingresos) || 0;
        const gastos = parseFloat(kpis.gastos) || 0;
        const balance = ingresos - gastos;

        // Pintamos los recuadros superiores
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

        // 2. DIBUJAMOS BARRAS 50/30/20 (Pasándole los ingresos)
        await renderizarBarras(mes, ingresos);

    } catch (e) {
        console.error("Error en KPIs:", e);
    }

    // 3. OBTENEMOS MOVIMIENTOS RECIENTES
    try {
        const resMovs = await fetch(`controllers/TransaccionRouter.php?action=getAllLimit`);
        const movs = await resMovs.json();
        
        const containerMovs = document.getElementById('lista-movimientos-recent');
        
        if (!movs || movs.length === 0) {
            containerMovs.innerHTML = '<p class="text-gray-500 italic text-center py-6">No hay movimientos registrados este mes.</p>';
            return;
        }

        let html = '<ul class="divide-y divide-gray-100">';
        movs.forEach(m => {
            const monto = parseFloat(m.monto);
            const isGasto = monto < 0;
            // Damos formato a la fecha (DD/MM/YYYY)
            const fechaParts = m.fecha.split('-');
            const fechaFormato = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;

            html += `
                <li class="py-4 flex justify-between items-center hover:bg-gray-50 px-2 rounded transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white ${isGasto ? 'bg-red-400' : 'bg-green-400'}">
                            ${isGasto ? '▼' : '▲'}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-sm md:text-base">${m.descripcion}</p>
                            <p class="text-xs text-gray-500 mt-0.5">${fechaFormato} • <span class="bg-gray-100 px-1.5 py-0.5 rounded border">${m.categoria_nombre || 'Sin categoría'}</span></p>
                        </div>
                    </div>
                    <span class="font-extrabold text-sm md:text-base ${isGasto ? 'text-red-500' : 'text-green-500'}">
                        ${monto.toLocaleString('es-ES', {minimumFractionDigits: 2})}€
                    </span>
                </li>
            `;
        });
        html += '</ul>';
        containerMovs.innerHTML = html;

    } catch(e) {
        console.error("Error en Movimientos:", e);
    }
}

async function renderizarBarras(mes, ingresos) {
    const container = document.getElementById('progress-503020-container');
    
    if (ingresos <= 0) {
        container.innerHTML = `
            <div class="text-center p-6 bg-indigo-50 rounded-xl border border-indigo-100">
                <p class="text-indigo-800 font-bold mb-1">Esperando ingresos</p>
                <p class="text-sm text-indigo-600">Registra un ingreso en este mes para que el sistema calcule tu capacidad de gasto.</p>
            </div>
        `;
        return;
    }

    try {
        const resDist = await fetch(`controllers/DashboardRouter.php?action=getDistribucionGastos&mes=${mes}`);
        const distribucion = await resDist.json();

        let gastos = { necesidad: 0, deseo: 0, ahorro: 0 };
        if (Array.isArray(distribucion)) {
            distribucion.forEach(d => {
                if(gastos[d.tipo] !== undefined) {
                    gastos[d.tipo] = parseFloat(d.total) || 0;
                }
            });
        }

        const crearBarraHTML = (titulo, gastado, limitePct, tipo) => {
            const porcentaje = (gastado / ingresos) * 100;
            const porcentajeVisual = Math.min(porcentaje, 100); 
            
            let colorBarra = 'bg-indigo-500', bgFondo = 'bg-gray-50', mensaje = '', txtColor = 'text-gray-500';

            if (tipo === 'necesidad' || tipo === 'deseo') {
                if (porcentaje <= limitePct) { 
                    colorBarra = 'bg-green-500'; 
                    mensaje = 'Dentro de lo recomendado.'; 
                    txtColor = 'text-green-600 font-semibold'; 
                } else { 
                    colorBarra = 'bg-red-500'; 
                    bgFondo = 'bg-red-50'; 
                    mensaje = `¡Aviso! Superaste el ${limitePct}%.`; 
                    txtColor = 'text-red-600 font-bold'; 
                }
            } else if (tipo === 'ahorro') {
                if (porcentaje >= limitePct) { 
                    colorBarra = 'bg-indigo-500'; 
                    mensaje = '¡Meta alcanzada!'; 
                    txtColor = 'text-indigo-600 font-bold'; 
                } else { 
                    colorBarra = 'bg-yellow-400'; 
                    mensaje = `Aún falta para el ${limitePct}%.`; 
                    txtColor = 'text-yellow-600 font-semibold'; 
                }
            }

            return `
                <div class="mb-4 p-4 rounded-xl ${bgFondo} border border-gray-100">
                    <div class="flex justify-between items-end mb-2">
                        <span class="font-bold text-gray-800 text-sm">${titulo}</span>
                        <div class="text-right">
                            <span class="text-sm font-extrabold text-gray-900">${gastado.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span>
                            <span class="text-xs text-gray-500 ml-1">/ ${porcentaje.toFixed(1)}%</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden shadow-inner">
                        <div class="${colorBarra} h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: ${porcentajeVisual}%"></div>
                    </div>
                    <p class="text-xs ${txtColor}">${mensaje}</p>
                </div>
            `;
        };

        container.innerHTML = `
            ${crearBarraHTML('Necesidades (Límite 50%)', gastos.necesidad, 50, 'necesidad')}
            ${crearBarraHTML('Deseos (Límite 30%)', gastos.deseo, 30, 'deseo')}
            ${crearBarraHTML('Ahorro e Inversión (Meta 20%)', gastos.ahorro, 20, 'ahorro')}
            
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs text-gray-400 uppercase tracking-wide font-bold">Base de cálculo</span>
                <span class="font-extrabold text-gray-700">${ingresos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span>
            </div>
        `;

    } catch(e) {
        console.error("Error en Barras:", e);
        container.innerHTML = '<p class="text-red-500 font-bold text-center">No se pudieron calcular los porcentajes.</p>';
    }
}

// Inicializamos todo cuando la página cargue
document.addEventListener('DOMContentLoaded', () => {
    const inputMes = document.getElementById('dashboardFilterMonth');
    inputMes.addEventListener('change', cargarDashboard);
    cargarDashboard(); // Primera carga automática
});
</script>

<?php include 'includes/footer.php'; ?>