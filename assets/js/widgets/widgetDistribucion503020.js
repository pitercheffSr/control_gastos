/**
 * ------------------------------------------------------------
 * widgetDistribucion503020.js
 * ------------------------------------------------------------
 * Renderiza el gráfico donut 50 / 30 / 20
 * usando Chart.js.
 *
 * Responsabilidad:
 * - SOLO pintar el gráfico
 * - NO pedir datos
 * - NO lógica de negocio
 * ------------------------------------------------------------
 */

let chart503020 = null;

export function renderDistribucion503020(ctx, labels, valores) {
	// Si el gráfico ya existe, destruirlo (evita duplicados)
	if (chart503020) {
		chart503020.destroy();
	}

	chart503020 = new Chart(ctx, {
		type: 'doughnut',
		data: {
			labels: labels,
			datasets: [
				{
					data: valores,
					backgroundColor: [
						'#4CAF50', // 50%
						'#FFC107', // 30%
						'#2196F3'  // 20%
					],
					borderWidth: 1
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom'
				}
			}
		}
	});
}
