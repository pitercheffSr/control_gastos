/**
 * widgetMovimientos.js - Gestiona la tabla de transacciones
 */

let paginaActual = 1;
const limite = 5; // Mantenemos tu límite de 5

/**
 * Renderiza las filas en la tabla
 */
function renderMovimientos(rows) {
	const tbody = document.getElementById('transactionsTableBody');
	if (!tbody) return;

	tbody.innerHTML = '';

	// Si no hay datos, ocupamos las 7 columnas (Fecha, Desc, Cat, Sub, SubSub, Importe, Tipo)
	if (!rows || rows.length === 0) {
		tbody.innerHTML = "<tr><td colspan='7' class='text-center'>No hay movimientos registrados</td></tr>";
		return;
	}

	rows.forEach((t) => {
		// Determinamos el color según el tipo de transacción
		const importeClase = t.tipo === 'ingreso' ? 'text-success' : 'text-error';

		// Aseguramos que el monto sea un número para evitar errores de .toFixed()
		const montoNum = parseFloat(t.monto || 0);

		// Extraemos los nombres de las categorías con nombres seguros
		const cat = t.categoria_nombre || '-';
		const sub = t.subcategoria_nombre || '-';
		const subsub = t.subsubcategoria_nombre || '<span class="text-gray">-</span>';

		tbody.insertAdjacentHTML(
			'beforeend',
			`
            <tr>
                <td>${t.fecha || ''}</td>
                <td>${t.descripcion || ''}</td>
                <td>${cat}</td>
                <td>${sub}</td>
                <td>${subsub}</td>
                <td class="${importeClase}"><strong>${montoNum.toFixed(2)} €</strong></td>
                <td><span class="chip">${t.tipo || ''}</span></td>
            </tr>
            `
		);
	});
}

/**
 * Carga los movimientos desde el controlador
 */
export async function cargarMovimientos(pagina = 1) {
	try {
		// Usamos ruta relativa para Fedora
		const resp = await fetch(`controllers/DashboardRouter.php?action=movimientos&page=${pagina}&limit=${limite}`, {
			credentials: 'same-origin'
		});

		if (!resp.ok) throw new Error('Error HTTP ' + resp.status);

		const json = await resp.json();
		if (!json.ok) throw new Error(json.error || 'Error en el servidor');

		paginaActual = pagina;
		renderMovimientos(json.data);

		// Actualizar el indicador de página si existe
		const info = document.getElementById('pageInfo');
		if (info) info.textContent = `Página ${paginaActual}`;

	} catch (err) {
		console.error('Error cargando movimientos:', err);
	}
}

/**
 * Inicializa los botones de navegación
 */
export function initMovimientos() {
	const prev = document.getElementById('prevPage');
	const next = document.getElementById('nextPage');

	if (prev) {
		prev.onclick = () => {
			if (paginaActual > 1) cargarMovimientos(paginaActual - 1);
		};
	}

	if (next) {
		next.onclick = () => cargarMovimientos(paginaActual + 1);
	}
}
