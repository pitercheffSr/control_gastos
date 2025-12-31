/**
 * ------------------------------------------------------------
 * dashboard.js
 * ------------------------------------------------------------
 * Lógica de carga del dashboard principal.
 *
 * Responsabilidades:
 * - Solicitar al backend el resumen del mes actual
 * - Pintar KPIs en el DOM
 * - NO contiene lógica de negocio (eso está en backend)
 * - NO maneja sesiones ni seguridad
 *
 * Fuente de datos:
 * - /controllers/DashboardRouter.php
 * ------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', async () => {
	try {
		// Solicitar resumen del mes actual al backend
		const resp = await fetch('/control_gastos/controllers/DashboardRouter.php');

		// Parsear respuesta JSON
		const json = await resp.json();

		// Validación básica del contrato backend
		if (!json.ok) {
			throw new Error(json.error || 'Error cargando datos del dashboard');
		}

		// Alias corto a los datos devueltos
		const d = json.data;

		// --------------------------------------------------------
		// KPI: INGRESOS DEL MES
		// --------------------------------------------------------
		document.getElementById('kpi-ingresos').textContent =
			d.ingresos.toFixed(2) + ' €';

		// --------------------------------------------------------
		// KPI: GASTOS DEL MES
		// --------------------------------------------------------
		document.getElementById('kpi-gastos').textContent =
			d.gastos.toFixed(2) + ' €';

		// --------------------------------------------------------
		// KPI: BALANCE (ingresos - gastos)
		// Se colorea dinámicamente:
		// - Verde si positivo o cero
		// - Rojo si negativo
		// --------------------------------------------------------
		const balanceEl = document.getElementById('kpi-balance');
		balanceEl.textContent = d.balance.toFixed(2) + ' €';

		balanceEl.classList.toggle('text-success', d.balance >= 0);
		balanceEl.classList.toggle('text-error', d.balance < 0);

		// --------------------------------------------------------
		// KPI: PORCENTAJE DE GASTO SOBRE INGRESOS
		// El backend ya gestiona el caso ingresos = 0
		// --------------------------------------------------------
		document.getElementById('kpi-porcentaje').textContent =
			d.porcentaje_gasto.toFixed(2) + ' %';

	} catch (err) {
		// Error silencioso controlado:
		// - Se muestra en consola
		// - No bloquea la carga del dashboard
		console.error('Dashboard error:', err);
	}
});
