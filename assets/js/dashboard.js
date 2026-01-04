
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
import { cargarMovimientos, initMovimientos } from './widgets/widgetMovimientos.js';


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
let currentPage = 1;
let totalPages = 1;


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
		await cargarMovimientos(1);
		initMovimientos();

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

document.getElementById('prevPage').addEventListener('click', () => {
	if (currentPage > 1) {
		cargarMovimientos(currentPage - 1);
	}
});

document.getElementById('nextPage').addEventListener('click', () => {
	if (currentPage < totalPages) {
		cargarMovimientos(currentPage + 1);
	}
});


/* ------------------------------------------------------------
   Toggle sidebar (menú lateral)
------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
	const btn = document.getElementById('btnToggleSidebar');
	const sidebar = document.querySelector('.sidebar');
	const appRoot = document.querySelector('.app-root');

	if (!btn || !sidebar || !appRoot) return;

	btn.addEventListener('click', () => {
		appRoot.classList.toggle('sidebar-collapsed');
	});
});

