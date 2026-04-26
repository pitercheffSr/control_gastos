const escapeHtml = (unsafe) => unsafe ? String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") : '';

// Creamos un mapa para búsquedas rápidas de ID a Nombre
const categoriasMap = new Map(categoriasArbol.map(c => [c.id.toString(), c.nombre]));

// MAGIA: Esta función trepa por el árbol hasta decirnos si es Necesidad, Deseo o Ahorro
function getRootCategoryName(catId) {
    if (!catId) return '';
    let currentId = catId;
    let rootName = '';
    let loop = 0;

    while(loop < 20) {
        const cat = categoriasArbol.find(c => c.id == currentId);
        if (!cat) break;
        rootName = cat.nombre;
        if (!cat.parent_id) break;
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

        await renderizarBarras(fInicio, fFin, ingresos, gastos);
        await renderBalanceLineChart(fInicio, fFin);
    } catch (e) { console.error("Error en KPIs:", e); }

    try {
        const tipoGlobal = document.getElementById('filtroTipoGlobal') ? document.getElementById('filtroTipoGlobal').value : '';
        const containerMovs = document.getElementById('lista-movimientos-recent');
        const resMovs = await fetch(`controllers/TransaccionRouter.php?action=getPaginated&page=1&limit=8&startDate=${fInicio}&endDate=${fFin}&tipo=${tipoGlobal}`);
        const movs = await resMovs.json();
        const transaccionesRecientes = movs.data;

        if (!transaccionesRecientes || transaccionesRecientes.length === 0 || movs.error) {
            containerMovs.innerHTML = '<p class="text-gray-500 italic text-center py-6">No hay movimientos registrados recientes.</p>'; return;
        }

        let html = '<ul class="divide-y divide-gray-100">';
        transaccionesRecientes.forEach(m => {
            const importeValue = parseFloat(m.importe) || 0;
            const isGasto = importeValue < 0;
            const fechaParts = m.fecha.split('-');
            const fechaFormato = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;

            html += `
                <li class="py-4 flex justify-between items-center hover:bg-gray-50 px-2 rounded transition">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white ${isGasto ? 'bg-red-400' : 'bg-green-400'}">${isGasto ? '▼' : '▲'}</div>
                        <div><p class="font-bold text-gray-800 text-sm md:text-base">${escapeHtml(m.descripcion)}</p><p class="text-xs text-gray-500 mt-0.5">${fechaFormato} • <span class="bg-gray-100 px-1.5 py-0.5 rounded border">${escapeHtml(m.categoria_nombre || 'Por clasificar')}</span></p></div>
                    </div>
                    <span class="font-extrabold text-sm md:text-base ${isGasto ? 'text-red-500' : 'text-green-500'}">${Math.abs(importeValue).toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span>
                </li>`;
        });
        html += '</ul>';
        containerMovs.innerHTML = html;
    } catch(e) { console.error("Error en Movimientos:", e); }
}

let donutChartInstance = null;
let balanceLineChartInstance = null;

function renderDonutChart(distribucion) {
    const container = document.getElementById('donut-chart-container');
    const canvas = document.getElementById('gastosDonutChart');

    if (!canvas || typeof Chart === 'undefined') {
        container.innerHTML = '<p class="text-center text-red-500">Error: Chart.js no está cargado.</p>';
        return;
    }

    if (donutChartInstance) donutChartInstance.destroy();

    if (!distribucion || !Array.isArray(distribucion) || distribucion.length === 0) {
        container.innerHTML = '<p class="text-gray-400 italic text-center py-10">No hay datos de gastos para mostrar.</p>';
        return;
    }

    let grupos = {};

    distribucion.forEach(d => {
        const total = parseFloat(d.total) || 0;
        if (total <= 0) return;

        let currentId = d.categoria_id;
        let rootId = null;
        let rootName = '';
        let loop = 0;

        while(currentId && loop < 20) {
            const cat = categoriasArbol.find(c => c.id == currentId);
            if (!cat) break;
            rootId = cat.id;
            rootName = cat.nombre.toLowerCase();
            if (!cat.parent_id) break;
            currentId = cat.parent_id;
            loop++;
        }

        let groupName = 'Otros / Sin Clasificar';

        if (rootName.includes('necesidad') || rootName === 'necesidades') {
            groupName = 'Necesidades (50%)';
        } else if (rootName.includes('deseo') || rootName === 'deseos') {
            groupName = 'Deseos (30%)';
        } else if (rootName.includes('ahorro') || rootName.includes('inversion') || rootName.includes('inversión')) {
            groupName = 'Ahorro (20%)';
        } else if (rootId) {
            const catReal = categoriasArbol.find(c => c.id == rootId);
            groupName = catReal ? catReal.nombre : 'Otros';
        }

        if (!grupos[groupName]) {
            grupos[groupName] = { id: rootId, nombre: groupName, total: 0 };
        }
        grupos[groupName].total += total;
    });

    let chartData = Object.values(grupos).sort((a, b) => b.total - a.total);

    if (chartData.length === 0) {
        container.innerHTML = '<p class="text-gray-400 italic text-center py-10">No hay gastos categorizados en este periodo.</p>';
        return;
    }

    const labels = chartData.map(d => d.nombre);
    const data = chartData.map(d => d.total);

    const backgroundColors = labels.map(label => {
        if (label.includes('Necesidades')) return '#22c55e';
        if (label.includes('Deseos')) return '#a855f7';
        if (label.includes('Ahorro')) return '#4f46e5';
        return '#94a3b8';
    });

    const ctx = canvas.getContext('2d');
    donutChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: 'Gastos',
                data: data,
                backgroundColor: backgroundColors,
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onHover: (event, chartElement) => {
                const canvas = event.native.target;
                canvas.style.cursor = chartElement[0] ? 'pointer' : 'default';
            },
            onClick: (evt, elements) => {
                if (elements.length === 0) return;
                const clickedElement = elements[0];
                const clickedData = chartData[clickedElement.index];

                if (clickedData && clickedData.id) {
                    const categoryId = clickedData.id;
                    const startDate = document.getElementById('dashboardFechaInicio').value;
                    const endDate = document.getElementById('dashboardFechaFin').value;
                    window.location.href = `transacciones.php?categoryId=${categoryId}&startDate=${startDate}&endDate=${endDate}`;
                }
            },
            cutout: '60%',
            plugins: {
                legend: {
                    position: window.innerWidth < 768 ? 'bottom' : 'right',
                    labels: { boxWidth: 12, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const totalSum = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = totalSum > 0 ? (context.parsed / totalSum * 100).toFixed(1) : 0;
                            return `${context.label}: ${context.parsed.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' })} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

async function renderizarBarras(fInicio, fFin, ingresos, gastosTotalesKpi) {
    const container = document.getElementById('progress-503020-container');

    try {
        const resDist = await fetch(`controllers/DashboardRouter.php?action=getDistribucionGastos&fecha_inicio=${fInicio}&fecha_fin=${fFin}`);
        const distribucion = await resDist.json();

        renderDonutChart(distribucion);

        let gastos = { necesidad: 0, deseo: 0, ahorro: 0, gasto: 0 };

        if (Array.isArray(distribucion)) {
            distribucion.forEach(d => {
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

        let totalAsignado = gastos.necesidad + gastos.deseo + gastos.ahorro;
        gastos.gasto = Math.max(0, gastosTotalesKpi - totalAsignado);

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
            } else if (tipo === 'gasto') {
                if (gastado > 0) { colorBarra = 'bg-gray-400'; bgFondo = 'bg-gray-100'; mensaje = 'Gastos pendientes de asignar a una categoría correcta.'; txtColor = 'text-gray-600 font-bold'; }
                else { colorBarra = 'bg-gray-200'; mensaje = '¡Todo clasificado perfectamente!'; txtColor = 'text-gray-400'; }
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
            ${crearBarraHTML('Otros / Por Clasificar', gastos.gasto, 100, 'gasto')}
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center"><span class="text-xs text-gray-400 uppercase tracking-wide font-bold">Base de cálculo (Ingresos)</span><span class="font-extrabold text-gray-700">${ingresos.toLocaleString('es-ES', {minimumFractionDigits: 2})}€</span></div>`;
    } catch(e) { console.error("Error en Barras:", e); }
}

async function renderBalanceLineChart(fInicio, fFin) {
    const container = document.getElementById('balance-chart-container');
    const canvas = document.getElementById('balanceLineChart');

    if (!canvas || typeof Chart === 'undefined') {
        container.innerHTML = '<p class="text-center text-red-500">Error: Chart.js no está cargado.</p>';
        return;
    }

    if (balanceLineChartInstance) balanceLineChartInstance.destroy();

    try {
        const res = await fetch(`controllers/DashboardRouter.php?action=getHistoricalBalance&fecha_inicio=${fInicio}&fecha_fin=${fFin}`);
        const data = await res.json();

        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-gray-400 italic text-center py-10">No hay datos de balance para mostrar en este periodo.</p>';
            return;
        }

        const labels = data.map(item => {
            const date = new Date(item.month_start + 'T00:00:00');
            return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        });
        const balances = data.map(item => parseFloat(item.balance));

        const ctx = canvas.getContext('2d');
        balanceLineChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Balance Acumulado',
                    data: balances,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4f46e5',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' })}`;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: false, ticks: { callback: function(value) { return value.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' }); } } }
                }
            }
        });

    } catch (e) {
        console.error("Error al cargar el gráfico de balance histórico:", e);
        container.innerHTML = '<p class="text-center text-red-500">Error al cargar el histórico de balance.</p>';
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === "Escape") {
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    }
});

document.getElementById('filtroTipoGlobal')?.addEventListener('change', () => {
    cargarDashboard();
});

document.addEventListener('DOMContentLoaded', () => {
    limpiarFiltrosDashboard();
});
