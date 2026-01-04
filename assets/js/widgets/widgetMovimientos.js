/**
 * ------------------------------------------------------------
 * widgetMovimientos.js
 * ------------------------------------------------------------
 * Widget reutilizable para listar transacciones.
 *
 * Responsabilidades:
 * - Pedir movimientos al backend
 * - Pintar filas en una tabla existente
 * - Gestionar paginación básica
 *
 * Requisitos HTML:
 * - Tabla con <tbody id="transactionsTableBody">
 * - Botones #prevPage y #nextPage
 * - Span #pageInfo
 * ------------------------------------------------------------
 */

let paginaActual = 1;
const limite = 10;

/**
 * Renderiza filas en la tabla
 */
function renderMovimientos(rows) {
	const tbody = document.getElementById('transactionsTableBody');
	if (!tbody) return;

	tbody.innerHTML = '';

	if (!rows.length) {
		tbody.innerHTML =
			"<tr><td colspan='6'>No hay movimientos</td></tr>";
		return;
	}

	rows.forEach((t) => {
		tbody.insertAdjacentHTML(
			'beforeend',
			`
			<tr>
				<td>${t.fecha}</td>
				<td>${t.descripcion || ''}</td>
				<td>${t.categoria || '-'}</td>
				<td>${t.subcategoria || '-'}</td>
				<td>${t.monto} €</td>
				<td>${t.tipo}</td>
			</tr>
		`
		);
	});
}

/**
 * Carga movimientos desde el backend
 */
export async function cargarMovimientos(pagina = 1) {
	try {
		const resp = await fetch(
			`/control_gastos/controllers/DashboardRouter.php?action=movimientos&page=${pagina}&limit=${limite}`,
			{ credentials: 'same-origin' }
		);

		if (!resp.ok) throw new Error('HTTP ' + resp.status);

		const json = await resp.json();

		if (!json.ok) {
			throw new Error(json.error || 'Error backend');
		}

		paginaActual = pagina;
		renderMovimientos(json.data);

		// Info paginación
		const info = document.getElementById('pageInfo');
		if (info) {
			info.textContent = `Página ${paginaActual}`;
		}

	} catch (err) {
		console.error('Error cargando movimientos:', err);
	}
}

/**
 * Inicializa botones de paginación
 */
export function initMovimientos() {
	const prev = document.getElementById('prevPage');
	const next = document.getElementById('nextPage');

	if (!prev || !next) return;

	prev.addEventListener('click', () => {
		if (paginaActual > 1) {
			cargarMovimientos(paginaActual - 1);
		}
	});

	next.addEventListener('click', () => {
		cargarMovimientos(paginaActual + 1);
	});
}
