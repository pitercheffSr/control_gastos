/**
 * Lógica principal del Dashboard
 * Coordina los filtros globales y la actualización de widgets.
 */
document.addEventListener('DOMContentLoaded', function() {
    const filtroMes = document.getElementById('dashboardFilterMonth');

    if (filtroMes) {
        filtroMes.addEventListener('change', function() {
            const mesSeleccionado = this.value;

            // Actualizar Widget de Distribución
            if (typeof window.cargarGraficoDistribucion === 'function') {
                window.cargarGraficoDistribucion(mesSeleccionado);
            }

            // Actualizar KPIs (Ingresos/Gastos) si la función existe
            if (typeof window.cargarKpisWidget === 'function') {
                window.cargarKpisWidget(mesSeleccionado);
            }
        });
    }

    // Lógica para cerrar menú hamburguesa con Escape (Requisito Global)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const menu = document.getElementById('hamburger-menu');
            if (menu) menu.classList.add('hidden');
        }
    });
});