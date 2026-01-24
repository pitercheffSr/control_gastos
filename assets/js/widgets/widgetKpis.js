/**
 * widgetKpis.js - Actualiza las tarjetas de resumen (Ingresos, Gastos, Balance)
 */
export function renderKpis(data) {
	// Verificamos que los datos existan (usando los nombres de tu controlador PHP)
	if (!data || typeof data.ingresos_mes === 'undefined') {
		console.error("Error: El objeto de datos no contiene 'ingresos_mes'", data);
		throw new Error("Datos inválidos para renderKpis");
	}

	// Actualizamos el texto de los elementos en el HTML
	// Asegúrate de que en dashboard.php los IDs coincidan: kpi-ingresos, kpi-gastos, kpi-balance
	const elIngresos = document.getElementById('kpi-ingresos');
	const elGastos = document.getElementById('kpi-gastos');
	const elBalance = document.getElementById('kpi-balance');

	if (elIngresos) elIngresos.textContent = data.ingresos_mes.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';
	if (elGastos) elGastos.textContent = data.gastos_mes.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';
	if (elBalance) elBalance.textContent = data.balance_mes.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';

	// Cambiamos el color del balance si es negativo
	if (elBalance) {
		elBalance.classList.remove('text-success', 'text-error');
		elBalance.classList.add(data.balance_mes >= 0 ? 'text-success' : 'text-error');
	}
}
