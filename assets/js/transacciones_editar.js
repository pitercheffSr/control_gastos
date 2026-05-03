/* transacciones_editar.js
   Controls the sidebar panel for editing transactions.
   - Loads categories
   - Fills values for editing (edit mode)
   - Creates new transactions (new mode)
   - Saves changes using TransactionRouter.php */

console.log('transacciones_editar.js loaded');

// ---------------------------------------------------------
// DOM REFERENCES
// ---------------------------------------------------------
const panel = document.getElementById('panelEditar');
const overlay = document.getElementById('overlayPanel');
const sidebar = document.getElementById('mainSidebar');
const panelTitulo = document.getElementById('panelTitulo');

const btnCerrar = document.getElementById('cerrarPanel');
const btnCancelar = document.getElementById('cancelarEdicion');
const btnGuardar = document.getElementById('guardarCambios');

// Form fields
const fFecha = document.getElementById('e_fecha');
const fDesc = document.getElementById('e_desc');
const fMonto = document.getElementById('e_monto');
const fTipo = document.getElementById('e_tipo');
const fCat = document.getElementById('e_cat');
const fSubcat = document.getElementById('e_subcat');
const fSubsub = document.getElementById('e_subsub');

// null = New, id = Edit
window.currentTransactionId = null;

// ---------------------------------------------------------
// PANEL CONTROL (OPEN / CLOSE)
// ---------------------------------------------------------

function abrirPanel() {
	panel.classList.remove('translate-x-full');
	overlay.classList.remove('hidden');
}

function cerrarPanel() {
	panel.classList.add('translate-x-full');
	overlay.classList.add('hidden');
	window.currentTransactionId = null;
	document.getElementById('formEditar').reset();

	// Clean category selects
	fSubcat.innerHTML = "<option value=''>—</option>";
	fSubsub.innerHTML = "<option value=''>—</option>";
}

// ---------------------------------------------------------
// ESCAPE KEY & MENU LOGIC
// ---------------------------------------------------------

document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') {
		// Priority: close form panel if open
		if (!panel.classList.contains('translate-x-full')) {
			cerrarPanel();
		}
		// Close sidebar if open
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

// Manual close buttons
btnCerrar?.addEventListener('click', cerrarPanel);
btnCancelar?.addEventListener('click', cerrarPanel);
overlay?.addEventListener('click', cerrarPanel);

// ---------------------------------------------------------
// ACTION: NEW TRANSACTION
// ---------------------------------------------------------
document.getElementById('btnNuevaTransaccion')?.addEventListener('click', () => {
	window.currentTransactionId = null;
	panelTitulo.innerText = "New Transaction";
	panel.classList.remove('mode-edit');
	panel.classList.add('mode-new');

	// Set today's date by default
	fFecha.value = new Date().toISOString().split("T")[0];

	loadCategorias(); // Carga niveles iniciales
	abrirPanel();
});

// ---------------------------------------------------------
// LOAD CATEGORIES (3 levels)
// ---------------------------------------------------------
async function loadCategorias() {
    await initializeCascadingCategories({
        cat: fCat,
        subcat: fSubcat,
        subsubcat: fSubsub
    });
}

// ---------------------------------------------------------
// LOAD DATA FOR EDITING
// ---------------------------------------------------------
async function loadTransaccion(id) {
    try {
        // 1. Call the new endpoint 'getById'
        const resp = await fetch(`controllers/TransactionRouter.php?action=getById&id=${id}`);
        const json = await resp.json();

        if (!json.success) {
            alert('Error loading transaction: ' + (json.error || 'Unknown'));
            cerrarPanel();
            return;
        }

        const data = json.data;

        // 2. Fill basic form fields mapped to English DB keys
        fFecha.value = data.date;
        fDesc.value = data.description ?? '';
        fMonto.value = data.amount;
        fTipo.value = data.type;

        // 3. Fill cascading categories
        // The backend gives us a 'category_path' array.
        const path = data.category_path || [];

        if (path.length > 0) {
            fCat.value = path[0];
            if (fCat.onchange) await fCat.onchange();
        }

        if (path.length > 1) {
            fSubcat.value = path[1];
            if (fSubcat.onchange) await fSubcat.onchange();
        }

        if (path.length > 2) {
            fSubsub.value = path[2];
        }

    } catch (err) { console.error('Error loading transaction data:', err); }
}

// ---------------------------------------------------------
// SAVE CHANGES (CREATE OR EDIT)
// ---------------------------------------------------------
btnGuardar.addEventListener('click', async () => {
	const categoriaFinalId = fSubsub.value || fSubcat.value || fCat.value || null;

	const payload = {
		id: window.currentTransactionId,
		date: fFecha.value,
		description: fDesc.value,
		amount: fMonto.value,
		type: fTipo.value,
		// Send a single category ID, the most specific one.
		category_id: categoriaFinalId,
	};

	if (!payload.date || !payload.amount) {
		alert("Date and amount are required.");
		return;
	}

	try {
		const resp = await fetch(`controllers/TransactionRouter.php?action=save`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': window.csrf_token
			},
			body: JSON.stringify(payload)
		});

		const json = await resp.json();
		if (json.success) {
			cerrarPanel();
			// Dispatch global event so the main table can reload
			window.dispatchEvent(new CustomEvent('tx:saved', { detail: { transaction: json.data } }));
		} else {
			alert('Error: ' + json.error);
		}
	} catch (err) { console.error(err); }
});

// Listen to edit events from the table
window.addEventListener('tx:edit', async (ev) => {
	const id = ev.detail?.id;
	if (!id) return;

	window.currentTransactionId = id;
	panelTitulo.innerText = "Edit Transaction";
	panel.classList.remove('mode-new');
	panel.classList.add('mode-edit');
	await loadCategorias();
	await loadTransaccion(id);
	abrirPanel();
});
