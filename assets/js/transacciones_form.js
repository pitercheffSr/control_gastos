console.log('transacciones_form.js cargado correctamente');

// === 1. Cargar categorías raíz ===
fetch('load_categorias.php?nivel=nivel1')
	.then((r) => r.json())
	.then((cats) => {
		let sel = document.getElementById('f_categoria');
		sel.innerHTML = "<option value=''>---</option>";
		cats.forEach((c) => {
			sel.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
		});
	});

// === 2. Cargar subcategorías ===
document.getElementById('f_categoria').addEventListener('change', () => {
	let cid = document.getElementById('f_categoria').value;

	// Resetear estado al cambiar categoría

	fetch('load_categorias.php?nivel=nivel2&padre=' + cid)
		.then((r) => r.json())
		.then((subs) => {

			let sel = document.getElementById('f_subcategoria');
			sel.innerHTML = "<option value=''>---</option>";

			subs.forEach((s) => {
				sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
			});

			document.getElementById('f_subsub').innerHTML =
				"<option value=''>---</option>";
		});
});

// === 3. Cargar sub-subcategorías ===
document.getElementById('f_subcategoria').addEventListener('change', () => {
	let sid = document.getElementById('f_subcategoria').value;

	// Si no hay subcategoría seleccionada, no exigir sub-subcategoría
	if (!sid) {
		document.getElementById('f_subsub').innerHTML = "<option value=''>---</option>";
		return;
	}

	fetch('load_categorias.php?nivel=nivel3&padre=' + sid)
		.then((r) => r.json())
		.then((subs) => {

			let sel = document.getElementById('f_subsub');
			sel.innerHTML = "<option value=''>---</option>";

			subs.forEach((s) => {
				sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
			});
		});
});

// === 4. EVENTO GUARDAR (validación limpia) ===
document.getElementById('btnGuardarFull').addEventListener('click', async () => {
	const fecha = document.getElementById('f_fecha').value;
	const monto = document.getElementById('f_monto').value;
	const tipo = document.getElementById('f_tipo').value;

	const categoria = document.getElementById('f_categoria').value;
	const subcategoria = document.getElementById('f_subcategoria').value;
	const subsub = document.getElementById('f_subsub').value;

	if (!fecha) {
		alert('Debe seleccionar una fecha');
		return;
	}

	if (monto === '') {
		alert('Debes especificar un importe.');
		return;
	}

	if (!tipo) {
		alert('Debes seleccionar tipo: ingreso o gasto.');
		return;
	}

	// VALIDACIÓN CORRECTA Y REAL
	const subcatOptions = document.getElementById('f_subcategoria').options.length > 1;
	const subsubOptions = document.getElementById('f_subsub').options.length > 1;

	// Si hay categoría y existen subcategorías → exigir subcategoría
	if (categoria && subcatOptions && !subcategoria) {
		alert('Debes seleccionar una subcategoría.');
		return;
	}

	// Si hay subcategoría y existen sub-subcategorías → exigir sub-subcategoría
	if (subcategoria && subsubOptions && !subsub) {
		alert('Debes seleccionar una sub-subcategoría.');
		return;
	}

	const datos = {
		fecha: fecha,
		descripcion: document.getElementById('f_descripcion').value,
		monto: monto,
		tipo: tipo,
		categoria: categoria || null,
		subcategoria: subcategoria || null,
		subsub: subsub || null,
	};

	console.log('Enviando datos:', datos);

	let resp = await fetch('/control_gastos/controllers/TransaccionRouter.php?action=crear', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-CSRF-TOKEN': window.csrf_token
		},
		body: JSON.stringify(datos),
	});


	let json = await resp.json();
	console.log('Respuesta del servidor:', json);

	if (json.ok) {
		alert('Guardado correctamente');
		window.location.href = 'transacciones.php';
	} else {
		alert('Error: ' + json.error);
	}
});
