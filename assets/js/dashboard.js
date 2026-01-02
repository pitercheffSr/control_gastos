
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

import { renderKpis } from './widgets/widgetKpis.js';
import { renderDistribucion503020 } from './widgets/widgetDistribucion503020.js';


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

		json.data.forEach((item) => {
			labels.push(item.categoria);
			valores.push(item.total);
		});

		// 🎯 PINTAR DONUT
		const canvas = document.getElementById('chart503020');
		renderDistribucion503020(canvas, labels, valores);

	} catch (err) {
		console.error('Error cargando distribución:', err);
	}
}
let paginaActual = 1;

/* ------------------------------------------------------------
   Cargar movimientos (historial)
------------------------------------------------------------ */
async function cargarMovimientos(page = 1) {
	try {
		const json = await fetchJSON(
			`/control_gastos/controllers/DashboardRouter.php?action=movimientos&page=${page}`
		);

		if (!json.ok) {
			throw new Error(json.error || 'Error backend');
		}

		const tbody = document.querySelector('#transactionsTable tbody');
		tbody.innerHTML = '';

		if (json.data.length === 0) {
			tbody.innerHTML =
				"<tr><td colspan='6'>No hay movimientos</td></tr>";
			return;
		}

		json.data.forEach((t) => {
			tbody.insertAdjacentHTML(
				'beforeend',
				`
				<tr>
					<td>${t.fecha}</td>
					<td>${t.descripcion ?? ''}</td>
					<td>${t.categoria ?? '-'}</td>
					<td>${t.subcategoria ?? '-'}</td>
					<td>${t.monto} €</td>
					<td>${t.tipo}</td>
				</tr>
				`
			);
		});

		paginaActual = json.page;
		document.getElementById('pageInfo').textContent =
			'Página ' + paginaActual;

	} catch (err) {
		console.error('Error cargando movimientos:', err);
	}
}
/* ------------------------------------------------------------
	Carga inicial del dashboard
------------------------------------------------------------ */

document.addEventListener('DOMContentLoaded', async () => {
	try {
		const json = await fetchJSON('/control_gastos/controllers/DashboardRouter.php');

		if (!json.ok) throw new Error(json.error);

		renderKpis(json.data);
		await cargarPorcentaje();
		await cargarDistribucion();
		await cargarMovimientos();

	} catch (err) {
		console.error('Dashboard error:', err);
	}
});
/* ------------------------------------------------------------
   Lógica de interacción del dashboard
------------------------------------------------------------ */

document.addEventListener('DOMContentLoaded', () => {
	const btn = document.getElementById('btnToggleSidebar');
	const sidebar = document.querySelector('.sidebar');
	const main = document.querySelector('.main-content');

	if (!btn || !sidebar || !main) return;

	btn.addEventListener('click', () => {
		sidebar.classList.toggle('hidden');
		main.classList.toggle('expanded');
	});
});


document.getElementById('prevPage')?.addEventListener('click', () => {
	if (paginaActual > 1) {
		cargarMovimientos(paginaActual - 1);
	}
});

document.getElementById('nextPage')?.addEventListener('click', () => {
	cargarMovimientos(paginaActual + 1);
});


