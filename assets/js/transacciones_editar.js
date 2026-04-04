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
    // La lógica ahora está centralizada en la función reutilizable.
    // Pasamos los elementos <select> específicos de este panel.
    // La función `initializeCascadingCategories` debe estar disponible globalmente.
    await initializeCascadingCategories({
        cat: fCat,
        subcat: fSubcat,
        subsubcat: fSubsub
    });
}

// -----------------------------------------------------
// CARGAR DATOS PARA EDITAR
// -----------------------------------------------------
async function loadTransaccion(id) {
    try {
        // 1. Llamar al nuevo endpoint 'getById' que devuelve la transacción y la ruta de categorías.
        const resp = await fetch(`controllers/TransaccionRouter.php?action=getById&id=${id}`);
        const json = await resp.json();

        if (!json.success) {
            alert('Error al cargar la transacción: ' + (json.error || 'Desconocido'));
            cerrarPanel();
            return;
        }

        const data = json.data;

        // 2. Rellenar los campos básicos del formulario.
        fFecha.value = data.fecha;
        fDesc.value = data.descripcion ?? '';
        fMonto.value = data.importe; // El backend devuelve el importe en positivo.
        fTipo.value = data.tipo;     // El backend determina si es 'ingreso' o 'gasto'.

        // 3. Rellenar las categorías en cascada de forma inteligente.
        // El backend nos da un array 'categoria_path' con los IDs desde la raíz. Ej: [2, 7, 15]
        const path = data.categoria_path || [];

        if (path.length > 0) {
            fCat.value = path[0];
            // Disparamos el 'onchange' para cargar las subcategorías y esperamos a que termine.
            if (fCat.onchange) await fCat.onchange();
        }

        if (path.length > 1) {
            fSubcat.value = path[1];
            // Hacemos lo mismo para el siguiente nivel.
            if (fSubcat.onchange) await fSubcat.onchange();
        }

        if (path.length > 2) {
            fSubsub.value = path[2];
        }

    } catch (err) { console.error('Error al cargar datos de transacción:', err); }
}

// -----------------------------------------------------
// GUARDAR CAMBIOS (CREAR O EDITAR)
// -----------------------------------------------------
btnGuardar.addEventListener('click', async () => {
	// Determinar la categoría más específica seleccionada.
	// Si no se selecciona ninguna, el valor será null, lo que es correcto para la BD.
	const categoriaFinalId = fSubsub.value || fSubcat.value || fCat.value || null;

	const payload = {
		id: window.transaccionActual,
		fecha: fFecha.value,
		descripcion: fDesc.value,
		monto: fMonto.value,
		tipo: fTipo.value,
		// Se envía una única ID de categoría, la más específica.
		// Esto simplifica el backend y evita errores con strings vacíos.
		categoria_id: categoriaFinalId,
	};

	if (!payload.fecha || !payload.monto) {
		alert("Fecha e importe son obligatorios");
		return;
	}

	try {
		const resp = await fetch(`controllers/TransaccionRouter.php?action=save`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': window.csrf_token
			},
			body: JSON.stringify(payload)
		});

		const json = await resp.json();
		// CORRECCIÓN: El backend (TransaccionRouter.php) devuelve 'success', no 'ok'.
		// Ajustamos la condición para que coincida con la respuesta del servidor.
		if (json.success) {
			cerrarPanel(); // 1. Cierra el panel.
			// 2. Dispara un evento global para que la página principal (transacciones.php)
			// sepa que debe recargar los datos, sin acoplar este script a esa página.
			window.dispatchEvent(new CustomEvent('tx:saved', { detail: { transaction: json.data } }));
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
