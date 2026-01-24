/* ============================================================
   transacciones.js — CONTROL DE LISTADO, EDITAR Y ELIMINAR
   ============================================================ */

console.log('transacciones.js cargado'); // ← único log

/* ------------------------------------------------------------
   FUNCIÓN: Cargar transacciones en la tabla
------------------------------------------------------------ */
async function cargarTransacciones() {
	try {
		const resp = await fetch('controllers/TransaccionRouter.php?action=listar');
		const json = await resp.json(); // Cambiamos 'data' por 'json' para claridad

		console.log('Transacciones recibidas:', json);

		const tbody = document.querySelector('#tablaTransacciones tbody');
		if (!tbody) return;
		tbody.innerHTML = '';

		// CORRECCIÓN: Accedemos a json.data que es donde están los 17 movimientos
		if (!json.ok || !Array.isArray(json.data) || json.data.length === 0) {
			tbody.innerHTML = "<tr><td colspan='8'>No hay transacciones</td></tr>";
			return;
		}

		json.data.forEach((t) => {
			const id = t.id;
			// Usamos los alias definidos en el Paso 3 del controlador: cat_nombre, sub_nombre, subsub_nombre
			const cat = t.cat_nombre || '-';
			const sub = t.sub_nombre || '-';
			const subsub = t.subsub_nombre || '-';
			const importeClase = t.tipo === 'ingreso' ? 'text-success' : 'text-error';

			tbody.insertAdjacentHTML(
				'beforeend',
				`
                <tr data-id="${id}">
                    <td>${t.fecha ?? ''}</td>
                    <td>${t.descripcion ?? ''}</td>
                    <td>${cat}</td>
                    <td>${sub}</td>
                    <td>${subsub}</td>
                    <td class="${importeClase}"><strong>${parseFloat(t.monto).toFixed(2)} €</strong></td>
                    <td><span class="chip">${t.tipo}</span></td>
                    <td style="text-align:right;">
                        <button class="edit-btn btn btn-link" data-id="${id}" title="Editar">
                            <i class="icon icon-edit"></i>
                        </button>
                        <button class="delete-btn btn btn-link" data-id="${id}" title="Eliminar">
                            <i class="icon icon-delete"></i>
                        </button>
                    </td>
                </tr>
            `
			);
		});
	} catch (err) {
		console.error('Error cargando transacciones:', err);
		document.querySelector('#tablaTransacciones tbody').innerHTML =
			"<tr><td colspan='8'>Error cargando datos</td></tr>";
	}
}
/* ------------------------------------------------------------
   EVENTOS DE BOTONES — EDITAR y ELIMINAR (delegación)
------------------------------------------------------------ */
document.addEventListener('click', (ev) => {
	/* ------ EDITAR ------ */
	const btnEdit = ev.target.closest('.edit-btn');
	if (btnEdit) {
		const id = btnEdit.dataset.id;
		console.log('Editar clic en id:', id);

		// Evento que escucha transacciones_editar.js
		window.dispatchEvent(
			new CustomEvent('tx:editar', {
				detail: { id },
			})
		);
		return;
	}

	/* ------ ELIMINAR ------ */
	const btnDel = ev.target.closest('.delete-btn');
	if (btnDel) {
		const id = btnDel.dataset.id;
		console.log('Eliminar clic en id:', id);

		if (!confirm('¿Seguro que quieres eliminar esta transacción?')) return;

		fetch('controllers/TransaccionRouter.php?action=eliminar', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': window.csrf_token
			},
			body: JSON.stringify({ id }),
		})

			.then((r) => r.json())
			.then((j) => {
				console.log('Respuesta eliminar:', j);
				if (j.ok) cargarTransacciones();
				else alert('Error: ' + j.error);
			})
			.catch((err) => {
				console.error('Error eliminando transacción:', err);
				alert('Error en la petición.');
			});

		return;
	}
});

/* ------------------------------------------------------------
   INICIO
------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
	cargarTransacciones(); // ← se ejecuta UNA sola vez
});
