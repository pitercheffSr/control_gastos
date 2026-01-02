/**
 * ============================================================
 * widgetKpis.js
 * ============================================================
 * Widget responsable de pintar:
 * - Ingresos
 * - Gastos
 * - Balance
 *
 * RESPONSABILIDADES:
 * - Recibe datos ya calculados
 * - Valida estructura mínima
 * - Pinta valores en el DOM
 *
 * NO hace:
 * - Fetch
 * - Cálculos
 * - Lógica de negocio
 * ============================================================
 */

export function renderKpis(data) {
	// Defensa básica: estructura esperada
	if (
		!data ||
		typeof data.ingresos !== 'number' ||
		typeof data.gastos !== 'number' ||
		typeof data.balance !== 'number'
	) {
		throw new Error('Datos inválidos para renderKpis');
	}

	// -----------------------------
	// KPI INGRESOS
	// -----------------------------
	const ingresosEl = document.getElementById('kpi-ingresos');
	if (ingresosEl) {
		ingresosEl.textContent = data.ingresos.toFixed(2) + ' €';
	}

	// -----------------------------
	// KPI GASTOS
	// -----------------------------
	const gastosEl = document.getElementById('kpi-gastos');
	if (gastosEl) {
		gastosEl.textContent = data.gastos.toFixed(2) + ' €';
	}

	// -----------------------------
	// KPI BALANCE
	// -----------------------------
	const balanceEl = document.getElementById('kpi-balance');
	if (balanceEl) {
		balanceEl.textContent = data.balance.toFixed(2) + ' €';

		// Estilo visual según signo
		balanceEl.classList.toggle('text-success', data.balance >= 0);
		balanceEl.classList.toggle('text-error', data.balance < 0);
	}
}
