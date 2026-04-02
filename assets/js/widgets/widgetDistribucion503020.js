async function cargarDistribucion503020(mes) {
    const container = document.getElementById('progress-503020-container');
    
    try {
        // Pedimos los ingresos y los gastos al mismo tiempo para hacer la matemática
        const [resKpis, resDist] = await Promise.all([
            fetch(`controllers/DashboardRouter.php?action=getKpis&mes=${mes}`),
            fetch(`controllers/DashboardRouter.php?action=getDistribucion&mes=${mes}`)
        ]);

        const kpis = await resKpis.json();
        const distribucion = await resDist.json();

        if (kpis.error || distribucion.error) {
            container.innerHTML = '<p class="text-red-500 text-center font-bold">No se pudieron cargar los datos.</p>';
            return;
        }

        const ingresos = parseFloat(kpis.ingresos) || 0;
        
        // Si el usuario aún no ha cobrado o registrado ingresos en este mes, le avisamos
        if (ingresos === 0) {
            container.innerHTML = `
                <div class="text-center p-6 bg-indigo-50 rounded-xl border border-indigo-100">
                    <svg class="w-12 h-12 text-indigo-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-indigo-800 font-bold mb-1 text-lg">Esperando ingresos</p>
                    <p class="text-sm text-indigo-600">Registra tu primer ingreso del mes para calcular tu capacidad de gasto.</p>
                </div>
            `;
            return;
        }

        // Ordenamos lo gastado en cada caja fuerte
        let gastos = { necesidad: 0, deseo: 0, ahorro: 0 };
        distribucion.forEach(d => {
            if(gastos[d.tipo] !== undefined) {
                gastos[d.tipo] = parseFloat(d.total) || 0;
            }
        });

        // Función constructora de las barras de progreso
        const renderBar = (titulo, gastado, limitePct, tipo) => {
            const porcentajeGastado = (gastado / ingresos) * 100;
            const porcentajeSeguro = Math.min(porcentajeGastado, 100); // Evita que la barra se salga de la pantalla visualmente
            
            let colorBarra = 'bg-indigo-500';
            let bgFondo = 'bg-gray-100';
            let mensaje = '';
            let textClass = 'text-gray-500';

            // Evaluamos Necesidades (50%) y Deseos (30%)
            if (tipo === 'necesidad' || tipo === 'deseo') {
                if (porcentajeGastado <= limitePct) {
                    colorBarra = 'bg-green-500';
                    mensaje = `Dentro del límite recomendado.`;
                    textClass = 'text-green-600 font-medium';
                } else {
                    colorBarra = 'bg-red-500';
                    bgFondo = 'bg-red-50';
                    mensaje = `¡Aviso! Has superado el ${limitePct}%.`;
                    textClass = 'text-red-600 font-bold';
                }
            } 
            // Evaluamos Ahorro (20%) - Aquí la lógica es inversa: más es mejor
            else if (tipo === 'ahorro') {
                if (porcentajeGastado >= limitePct) {
                    colorBarra = 'bg-indigo-500';
                    mensaje = `¡Excelente! Has alcanzado la meta.`;
                    textClass = 'text-indigo-600 font-bold';
                } else {
                    colorBarra = 'bg-yellow-400';
                    mensaje = `Aún te falta para llegar al ${limitePct}%.`;
                    textClass = 'text-yellow-600 font-medium';
                }
            }

            return `
                <div class="mb-5 p-3 rounded-lg ${bgFondo} transition duration-300 hover:shadow-sm">
                    <div class="flex justify-between items-end mb-2">
                        <span class="font-extrabold text-gray-800">${titulo}</span>
                        <div class="text-right">
                            <span class="text-sm font-bold text-gray-900">${gastado.toFixed(2)}€</span>
                            <span class="text-xs text-gray-500 ml-1">/ ${porcentajeGastado.toFixed(1)}%</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2 overflow-hidden shadow-inner">
                        <div class="${colorBarra} h-2.5 rounded-full transition-all duration-700 ease-out" style="width: ${porcentajeSeguro}%"></div>
                    </div>
                    <p class="text-xs flex items-center gap-1 ${textClass}">
                        ${porcentajeGastado > limitePct && tipo !== 'ahorro' ? '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>' : ''}
                        ${mensaje}
                    </p>
                </div>
            `;
        };

        // Dibujamos las tres barras juntas y el sumatorio final
        container.innerHTML = `
            ${renderBar('Necesidades (Límite 50%)', gastos.necesidad, 50, 'necesidad')}
            ${renderBar('Deseos (Límite 30%)', gastos.deseo, 30, 'deseo')}
            ${renderBar('Ahorro e Inversión (Meta 20%)', gastos.ahorro, 20, 'ahorro')}
            
            <div class="mt-2 pt-3 border-t border-gray-100 flex justify-between items-center px-1">
                <span class="text-xs text-gray-500 uppercase tracking-wide font-bold">Ingresos base</span>
                <span class="font-extrabold text-indigo-700 text-lg">${ingresos.toFixed(2)}€</span>
            </div>
        `;

    } catch (error) {
        console.error("Error cargando widget 50/30/20:", error);
        container.innerHTML = '<p class="text-red-500 text-center">Fallo de conexión al servidor.</p>';
    }
}

// Hacemos la función global para que el selector de meses la pueda llamar
window.cargarDistribucion503020 = cargarDistribucion503020;