/**
 * dashboard.js - Versión con diagnóstico de errores
 */

import { renderKpis } from './widgets/widgetKpis.js';
import { renderDistribucion503020 } from './widgets/widgetDistribucion503020.js';
import { cargarMovimientos, initMovimientos } from './widgets/widgetMovimientos.js';

async function fetchJSON(url) {
	const resp = await fetch(url, { credentials: 'same-origin' });
	if (!resp.ok) throw new Error(`HTTP Error: ${resp.status}`);
	return await resp.json();
}

async function cargarPorcentaje() {
    try {
        const json = await fetchJSON('controllers/DashboardRouter.php?action=porcentaje');
        if (json.ok && json.data) {
            // Convertimos a número y fijamos 2 decimales
            const valor = parseFloat(json.data.porcentaje_gasto);
            document.getElementById('kpi-porcentaje').textContent = valor.toFixed(2) + ' %';
        }
    } catch (err) {
        console.error('Error cargando porcentaje:', err);
    }
}

async function cargarDistribucion() {
    try {
        // Llamada al router para obtener la distribución 50/30/20
        const json = await fetchJSON('controllers/DashboardRouter.php?action=distribucion');
        const canvas = document.getElementById('chart503020');

        // Si hay datos y el array no está vacío
        if (json.ok && json.data && json.data.length > 0) {
            console.log("Datos recibidos para el gráfico:", json.data);

            // Extraemos las etiquetas (50, 30, 20) y los totales convertidos a números
            const labels = json.data.map(item => item.categoria);
            const valores = json.data.map(item => parseFloat(item.total));

            // Llamamos al widget para renderizar en el canvas
            renderDistribucion503020(canvas, labels, valores);
        } else {
            console.warn("No hay datos categorizados como 50/30/20 para graficar.");
            // Pasamos arrays vacíos para que el widget muestre el mensaje de "Sin datos"
            renderDistribucion503020(canvas, [], []);
        }
    } catch (err) {
        console.error('Error al cargar la distribución gráfica:', err);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
	console.log("Iniciando carga del Dashboard...");
	try {
		const json = await fetchJSON('controllers/DashboardRouter.php?action=resumen');

		// --- DIAGNÓSTICO ---
		console.log("Respuesta del servidor:", json);

		if (!json.ok) throw new Error(json.error || "Error desconocido en el servidor");

		if (json.data) {
			console.log("Enviando datos a renderKpis:", json.data);
			renderKpis(json.data);
		} else {
			console.error("El servidor respondió ok pero sin 'data'");
		}

		// Cargar el resto
		await cargarPorcentaje();
		await cargarDistribucion();
		await cargarMovimientos(1);
		initMovimientos();

	} catch (err) {
		console.error('DASHBOARD ERROR CRÍTICO:', err.message);
		document.querySelectorAll('.kpi-value').forEach(el => el.textContent = 'Err');
	}
});

// Menú lateral
document.addEventListener('DOMContentLoaded', () => {
	const btn = document.getElementById('btnToggleSidebar');
	const menu = document.querySelector('.sidebar');
	const overlay = document.getElementById('menuOverlay');
	if (!btn || !menu || !overlay) return;
	btn.onclick = () => {
		menu.classList.toggle('visible');
		overlay.classList.toggle('visible');
	};
	overlay.onclick = () => {
		menu.classList.remove('visible');
		overlay.classList.remove('visible');
	};
});
