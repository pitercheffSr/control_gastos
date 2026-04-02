/* transacciones_editar.js
   Controla el PANEL LATERAL de edición de transacciones.
   - Carga categorías (3 niveles)
   - Rellena valores al editar
   - Guarda cambios mediante TransaccionRouter.php
*/
/* transacciones_editar.js - Lógica Unificada para Nueva y Editar */

/* transacciones_editar.js - Lógica Unificada para Nueva y Editar */

console.log('transacciones_editar.js cargado');

// -----------------------------------------------------
// REFERENCIAS DOM
// -----------------------------------------------------
const panel = document.getElementById('panelEditar');
const overlay = document.getElementById('overlayPanel');
const sidebar = document.getElementById('mainSidebar');
const panelTitulo = document.getElementById('panelTitulo');

const btnCerrar = document.getElementById('cerrarPanel');
const btnCancelar = document.getElementById('cancelarEdicion');
const btnGuardar = document.getElementById('guardarCambios');

// Campos del formulario
const fFecha = document.getElementById('e_fecha');
const fDesc = document.getElementById('e_desc');
const fMonto = document.getElementById('e_monto');
const fTipo = document.getElementById('e_tipo');
const fCat = document.getElementById('e_cat');
const fSubcat = document.getElementById('e_subcat');
const fSubsub = document.getElementById('e_subsub');

// null = Nueva, id = Editar
window.transaccionActual = null;

// -----------------------------------------------------
// CONTROL DE PANELES (ABRIR / CERRAR)
// -----------------------------------------------------

function abrirPanel() {
	panel.classList.add('visible');
	overlay.classList.add('visible');
}

function cerrarPanel() {
	panel.classList.remove('visible');
	overlay.classList.remove('visible');
	window.transaccionActual = null;
	document.getElementById('formEditar').reset();

	// Limpiamos selects de categorías
	fSubcat.innerHTML = "<option value=''>—</option>";
	fSubsub.innerHTML = "<option value=''>—</option>";
}

// -----------------------------------------------------
// LÓGICA DE LA TECLA ESCAPE Y MENÚ
// -----------------------------------------------------

document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') {
		// 1. Prioridad: cerrar panel de formulario
		if (panel.classList.contains('visible')) {
			cerrarPanel();
		}
		// 2. Cerrar sidebar si está abierto
		if (sidebar && sidebar.classList.contains('visible')) {
			sidebar.classList.remove('visible');
		}
	}
});

// Toggle del Menú Hamburguesa
document.getElementById('toggleMenu')?.addEventListener('click', (e) => {
	e.stopPropagation();
	sidebar.classList.toggle('visible');
});

// Botones de cierre manual
btnCerrar?.addEventListener('click', cerrarPanel);
btnCancelar?.addEventListener('click', cerrarPanel);
overlay?.addEventListener('click', cerrarPanel);

// -----------------------------------------------------
// ACCIÓN: NUEVA TRANSACCIÓN
// -----------------------------------------------------
document.getElementById('btnNuevaTransaccion')?.addEventListener('click', () => {
	window.transaccionActual = null;
	panelTitulo.innerText = "Nueva Transacción";

	// Establecer fecha de hoy por defecto
	fFecha.value = new Date().toISOString().split("T")[0];

	loadCategorias(); // Carga niveles iniciales
	abrirPanel();
});

// -----------------------------------------------------
// CARGAR CATEGORÍAS (3 niveles)
// -----------------------------------------------------
async function loadCategorias() {
	try {
		const resp = await fetch("load_categorias.php?nivel=nivel1");
		const cats = await resp.json();

		fCat.innerHTML = "<option value=''>— Seleccione —</option>";
		cats.forEach(c => fCat.innerHTML += `<option value="${c.id}">${c.nombre}</option>`);

		fCat.onchange = async () => {
			const idCat = fCat.value;
			fSubcat.innerHTML = "<option value=''>—</option>";
			fSubsub.innerHTML = "<option value=''>—</option>";
			if (!idCat) return;

			const r2 = await fetch(`load_categorias.php?nivel=nivel2&padre=${idCat}`);
			const subs = await r2.json();
			fSubcat.innerHTML = "<option value=''>— Seleccione —</option>";
			subs.forEach(c => fSubcat.innerHTML += `<option value="${c.id}">${c.nombre}</option>`);
		};

		fSubcat.onchange = async () => {
			const idSub = fSubcat.value;
			fSubsub.innerHTML = "<option value=''>—</option>";
			if (!idSub) return;

			const r3 = await fetch(`load_categorias.php?nivel=nivel3&padre=${idSub}`);
			const subsubs = await r3.json();
			fSubsub.innerHTML = "<option value=''>— Seleccione —</option>";
			subsubs.forEach(c => fSubsub.innerHTML += `<option value="${c.id}">${c.nombre}</option>`);
		};
	} catch (err) { console.error('Error categorías:', err); }
}

// -----------------------------------------------------
// CARGAR DATOS PARA EDITAR
// -----------------------------------------------------
async function loadTransaccion(id) {
	try {
		const resp = await fetch(`controllers/TransaccionRouter.php?action=obtener&id=${id}`);
		const json = await resp.json();
		if (!json.ok) return;

		const data = json.data;
		fFecha.value = data.fecha;
		fDesc.value = data.descripcion ?? '';
		fMonto.value = data.monto;
		fTipo.value = data.tipo;

		fCat.value = data.id_categoria ?? '';
		await fCat.onchange();

		fSubcat.value = data.id_subcategoria ?? '';
		await fSubcat.onchange();

		fSubsub.value = data.id_subsubcategoria ?? '';
	} catch (err) { console.error(err); }
}

// -----------------------------------------------------
// GUARDAR CAMBIOS (CREAR O EDITAR)
// -----------------------------------------------------
btnGuardar.addEventListener('click', async () => {
	const action = window.transaccionActual ? 'editar' : 'crear';

	const payload = {
		id: window.transaccionActual,
		fecha: fFecha.value,
		descripcion: fDesc.value,
		monto: fMonto.value,
		tipo: fTipo.value,
		id_categoria: fCat.value,
		id_subcategoria: fSubcat.value,
		id_subsubcategoria: fSubsub.value,
	};

	if (!payload.fecha || !payload.monto) {
		alert("Fecha e importe son obligatorios");
		return;
	}

	try {
		const resp = await fetch(`controllers/TransaccionRouter.php?action=${action}`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': window.csrf_token
			},
			body: JSON.stringify(payload)
		});

		const json = await resp.json();
		if (json.ok) {
			cerrarPanel();
			if (typeof cargarTransacciones === 'function') cargarTransacciones();
		} else {
			alert('Error: ' + json.error);
		}
	} catch (err) { console.error(err); }
});

// Escuchar evento desde la tabla
window.addEventListener('tx:editar', async (ev) => {
	const id = ev.detail?.id;
	if (!id) return;

	window.transaccionActual = id;
	panelTitulo.innerText = "Editar Transacción";
	await loadCategorias();
	await loadTransaccion(id);
	abrirPanel();
});
