/**
 * widgetDistribucion503020.js - Renderiza el gráfico de dona
 */
export function renderDistribucion503020(canvas, labels, valores) {
	if (!canvas) {
		console.error("No se encontró el elemento canvas para el gráfico");
		return;
	}

	// Si ya existe un gráfico en este canvas, lo destruimos para poder crear uno nuevo
	if (window.myChart503020 instanceof Chart) {
		window.myChart503020.destroy();
	}

	// Mapeo de etiquetas técnicas a nombres legibles
	const labelMap = {
		'50': 'Necesidades (50%)',
		'30': 'Deseos (30%)',
		'20': 'Ahorros/Deudas (20%)',
		'10': 'Otros (10%)'
	};

	const readableLabels = labels.map(l => labelMap[l] || `Grupo ${l}`);

	// Crear el gráfico
	window.myChart503020 = new Chart(canvas, {
		type: 'doughnut',
		data: {
			labels: readableLabels,
			datasets: [{
				label: 'Distribución de Gastos',
				data: valores,
				backgroundColor: [
					'#5755d9', // Púrpura (Spectre.css primary)
					'#32b643', // Verde (Success)
					'#ffb700', // Amarillo (Warning)
					'#e85600'  // Naranja (Error)
				],
				borderWidth: 2,
				hoverOffset: 4
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false, // Permite que se adapte al contenedor
			plugins: {
				legend: {
					position: 'bottom',
					labels: { boxWidth: 12, padding: 20 }
				}
			}
		}
	});
}
