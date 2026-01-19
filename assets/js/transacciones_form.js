/**
 * transacciones_form.js - Lógica para el formulario de nueva transacción
 */

console.log('transacciones_form.js cargado correctamente');

// === 1. Cargar categorías raíz (Nivel 1) ===
fetch('load_categorias.php?nivel=nivel1')
	.then((r) => r.json())
	.then((cats) => {
		let sel = document.getElementById('f_categoria');
		sel.innerHTML = "<option value=''>--- Seleccione Categoría ---</option>";
		cats.forEach((c) => {
			sel.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
		});
	});

// === 2. Cargar subcategorías (Nivel 2) ===
document.getElementById('f_categoria').addEventListener('change', () => {
	let cid = document.getElementById('f_categoria').value;

	// Limpiamos los selectores inferiores
	document.getElementById('f_subcategoria').innerHTML = "<option value=''>---</option>";
	document.getElementById('f_subsub').innerHTML = "<option value=''>---</option>";

	if (!cid) return;

	fetch('load_categorias.php?nivel=nivel2&padre=' + cid)
		.then((r) => r.json())
		.then((subs) => {
			let sel = document.getElementById('f_subcategoria');
			sel.innerHTML = "<option value=''>--- Seleccione Subcategoría ---</option>";
			subs.forEach((s) => {
				sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
			});
		});
});

// === 3. Cargar sub-subcategorías (Nivel 3) ===
document.getElementById('f_subcategoria').addEventListener('change', () => {
	let sid = document.getElementById('f_subcategoria').value;

	if (!sid) {
		document.getElementById('f_subsub').innerHTML = "<option value=''>---</option>";
		return;
	}

	fetch('load_categorias.php?nivel=nivel3&padre=' + sid)
		.then((r) => r.json())
		.then((subs) => {
			let sel = document.getElementById('f_subsub');
			sel.innerHTML = "<option value=''>--- Seleccione Sub-subcategoría ---</option>";
			subs.forEach((s) => {
				sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
			});
		});
});

// === 4. EVENTO GUARDAR ===
document.getElementById('btnGuardarFull').addEventListener('click', async () => {
	const datos = {
		fecha: document.getElementById('f_fecha').value,
		descripcion: document.getElementById('f_descripcion').value,
		monto: document.getElementById('f_monto').value,
		tipo: document.getElementById('f_tipo').value,
		id_categoria: document.getElementById('f_categoria').value || null,
		id_subcategoria: document.getElementById('f_subcategoria').value || null,
		id_subsubcategoria: document.getElementById('f_subsub').value || null,
	};

	// Validaciones básicas
	if (!datos.fecha || !datos.monto || !datos.tipo) {
		alert('Por favor, rellene Fecha, Importe y Tipo.');
		return;
	}

	console.log('Enviando datos corregidos:', datos);

	try {
		// RUTA RELATIVA para Fedora
		let resp = await fetch('controllers/TransaccionRouter.php?action=crear', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': window.csrf_token
			},
			body: JSON.stringify(datos),
		});

		let json = await resp.json();

		if (json.ok) {
			alert('Transacción guardada con éxito');
			window.location.href = 'transacciones.php';
		} else {
			alert('Error al guardar: ' + json.error);
		}
	} catch (err) {
		console.error('Error en la petición:', err);
		alert('Error de conexión con el servidor.');
	}
});
