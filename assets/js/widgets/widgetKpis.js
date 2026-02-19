/**
 * Widget de KPIs (Ingresos, Gastos y Balance)
 * Calcula y renderiza las tarjetas de resumen en el Dashboard.
 */
document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('widget-kpis-container');
    if (!contenedor) return;

    window.cargarKpisWidget = async function(mes = '') {
        try {
            // Obtenemos los datos desde el DashboardRouter que configuramos previamente
            const res = await fetch(`controllers/DashboardRouter.php?action=getKpis&mes=${mes}`);
            const data = await res.json();

            const ingresos = parseFloat(data.ingresos || 0);
            const gastos = parseFloat(data.gastos || 0);
            const balance = ingresos - gastos;

            contenedor.innerHTML = `
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full text-green-600 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase">Ingresos Mensuales</p>
                            <p class="text-2xl font-bold text-gray-800">${ingresos.toLocaleString('es-ES', { minimumFractionDigits: 2 })}€</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full text-red-600 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase">Gastos Mensuales</p>
                            <p class="text-2xl font-bold text-gray-800">${gastos.toLocaleString('es-ES', { minimumFractionDigits: 2 })}€</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-indigo-500">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 rounded-full text-indigo-600 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase">Balance Neto</p>
                            <p class="text-2xl font-bold ${balance >= 0 ? 'text-green-600' : 'text-red-600'}">
                                ${balance.toLocaleString('es-ES', { minimumFractionDigits: 2 })}€
                            </p>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error("Error cargando los KPIs:", error);
            contenedor.innerHTML = '<p class="text-center text-red-500">Error al cargar datos financieros.</p>';
        }
    };

    // Carga inicial (usa el mes actual por defecto si no se pasa nada)
    cargarKpisWidget();
});