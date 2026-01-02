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

/* ------------------------------------------------------------
   Helper fetch JSON seguro
------------------------------------------------------------ */
async function fetchJSON(url) {
	const resp = await fetch(url, { credentials: 'same-origin' });

	if (!resp.ok) {
		throw new Error(`HTTP ${resp.status}`);
	}

	return await resp.json();
}

/* ------------------------------------------------------------
Cargar porcentaje de gasto
------------------------------------------------------------ */
async function cargarPorcentaje() {
	try {
		const json = await fetchJSON(
			'/control_gastos/controllers/DashboardRouter.php?action=porcentaje'
		);

		console.log('Porcentaje gasto:', json);

		if (!json.ok) {
			throw new Error(json.error || 'Error backend');
		}

		document.getElementById('kpi-porcentaje').textContent =
			json.data.porcentaje_gasto.toFixed(2) + ' %';

	} catch (err) {
		console.error('Error cargando porcentaje:', err);
	}
}
/* ------------------------------------------------------------
   Cargar distribución 50 / 30 / 20
   (datos preparados para gráficos)
------------------------------------------------------------ */
async function cargarDistribucion() {
	try {
		const json = await fetchJSON(
			'/control_gastos/controllers/DashboardRouter.php?action=distribucion'
		);

		if (!json.ok) {
			throw new Error(json.error || 'Error backend');
		}

		const labels = [];
		const valores = [];
		const porcentajes = [];

		json.data.forEach((item) => {
			labels.push(item.categoria);
			valores.push(item.total);
			porcentajes.push(item.porcentaje);
		});

		// 🔍 Preparado para gráficos (Paso 8)
		console.log('Distribución labels:', labels);
		console.log('Distribución valores:', valores);
		console.log('Distribución porcentajes:', porcentajes);

	} catch (err) {
		console.error('Error cargando distribución:', err);
	}
}
/* ------------------------------------------------------------
   Carga inicial del dashboard
------------------------------------------------------------ */

document.addEventListener('DOMContentLoaded', async () => {
	try {
		const resp = await fetch('/control_gastos/controllers/DashboardRouter.php');
		const json = await resp.json();

		if (!json.ok) {
			throw new Error(json.error || 'Error cargando datos del dashboard');
		}

		const d = json.data;

		if (
			typeof d.ingresos !== 'number' ||
			typeof d.gastos !== 'number' ||
			typeof d.balance !== 'number'
		) {
			throw new Error('Datos del dashboard incompletos');
		}

		document.getElementById('kpi-ingresos').textContent =
			d.ingresos.toFixed(2) + ' €';

		document.getElementById('kpi-gastos').textContent =
			d.gastos.toFixed(2) + ' €';

		const balanceEl = document.getElementById('kpi-balance');
		balanceEl.textContent = d.balance.toFixed(2) + ' €';
		balanceEl.classList.toggle('text-success', d.balance >= 0);
		balanceEl.classList.toggle('text-error', d.balance < 0);

		// ⬇️ AQUÍ
		await cargarPorcentaje();
		await cargarDistribucion();

	} catch (err) {
		console.error('Dashboard error:', err);
	}
});

