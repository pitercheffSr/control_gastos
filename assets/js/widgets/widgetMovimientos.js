/**
 * Widget de Movimientos Recientes
 * Muestra las últimas 5 transacciones y permite ir a "Nueva"
 */
document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('lista-movimientos-recent');
    if (!contenedor) return;

    window.cargarMovimientosWidget = async function() {
        try {
            // Usamos el router de transacciones con el límite de 5 definido en el modelo
            const res = await fetch('controllers/TransaccionRouter.php?action=getAllLimit');
            const movimientos = await res.json();

            if (!movimientos || movimientos.length === 0) {
                contenedor.innerHTML = '<p class="text-center p-4">No hay movimientos recientes.</p>';
                return;
            }

            contenedor.innerHTML = movimientos.map(m => `
                <div class="flex justify-between items-center border-b border-gray-100 py-3">
                    <div>
                        <p class="font-semibold text-sm text-gray-800">${m.descripcion}</p>
                        <p class="text-xs text-gray-500">${m.fecha} • ${m.categoria_nombre || 'Sin categoría'}</p>
                    </div>
                    <span class="font-bold ${parseFloat(m.monto) < 0 ? 'text-red-500' : 'text-green-500'}">
                        ${parseFloat(m.monto).toFixed(2)}€
                    </span>
                </div>
            `).join('');
        } catch (error) {
            console.error("Error cargando movimientos recientes:", error);
            contenedor.innerHTML = '<p class="text-red-500 text-xs text-center">Error al cargar datos</p>';
        }
    };

    window.irANuevaTransaccion = function() {
        // Redirige a transacciones con flag para abrir el modal automáticamente
        window.location.href = 'transacciones.php?action=new';
    };

    cargarMovimientosWidget();
});