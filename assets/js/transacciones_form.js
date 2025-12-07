console.log("transacciones_form.js cargado correctamente");
window.categoriaTieneSubcategorias = false;
window.subcategoriaTieneSubsub = false;

// === 1. Cargar categorías raíz ===
fetch("load_categorias.php?nivel=nivel1")
.then(r => r.json())
.then(cats => {
    let sel = document.getElementById("f_categoria");
    sel.innerHTML = "<option value=''>---</option>";
    cats.forEach(c => {
        sel.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
    });
});

// === 2. Cargar subcategorías ===
document.getElementById("f_categoria").addEventListener("change", () => {
    let cid = document.getElementById("f_categoria").value;

    fetch("load_categorias.php?nivel=nivel2&padre=" + cid)
    .then(r => r.json())
    .then(subs => {
        window.categoriaTieneSubcategorias = subs.length > 0;

        let sel = document.getElementById("f_subcategoria");
        sel.innerHTML = "<option value=''>---</option>";

        subs.forEach(s => {
            sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
        });

        document.getElementById("f_subsub").innerHTML = "<option value=''>---</option>";
    });

});

// === 3. Cargar sub-subcategorías ===
document.getElementById("f_subcategoria").addEventListener("change", () => {
    let sid = document.getElementById("f_subcategoria").value;

    fetch("load_categorias.php?nivel=nivel3&padre=" + sid)
    .then(r => r.json())
    .then(subs => {
        window.subcategoriaTieneSubsub = subs.length > 0;

        let sel = document.getElementById("f_subsub");
        sel.innerHTML = "<option value=''>---</option>";

        subs.forEach(s => {
            sel.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
        });
    });
});

// === 4. EVENTO GUARDAR ===
document.getElementById("btnGuardarFull").addEventListener("click", async () => {

    const fecha = document.getElementById("f_fecha").value;
    if (!fecha) {
        alert("Debe seleccionar una fecha");
        return;
    }
    if (!document.getElementById("f_monto").value) {
        alert("Debes especificar un importe.");
        return;
    }

    if (!document.getElementById("f_tipo").value) {
        alert("Debes seleccionar tipo: ingreso o gasto.");
        return;
    }

    if (!document.getElementById("f_categoria").value) {
        alert("Debes seleccionar una categoría.");
        return;
    }
    // Validar subcategoría si la categoría tiene hijas
    if (window.categoriaTieneSubcategorias === true) {
        let sub = document.getElementById("f_subcategoria").value;
        if (!sub) {
            alert("Debes seleccionar una subcategoría.");
            return;
        }
    }
    // Si la subcategoría tiene sub-subcategorías → obligar a elegir una
    if (window.subcategoriaTieneSubsub === true) {
        let sub2 = document.getElementById("f_subsub").value;
        if (!sub2) {
            alert("Debes seleccionar una sub-subcategoría.");
            return;
        }
    }

    const datos = {
        fecha: fecha,
        descripcion: document.getElementById("f_descripcion").value,
        monto: document.getElementById("f_monto").value,
        tipo: document.getElementById("f_tipo").value,
        categoria: document.getElementById("f_categoria").value,
        subcategoria: document.getElementById("f_subcategoria").value,
        subsub: document.getElementById("f_subsub").value
    };

    console.log("Enviando datos:", datos);

    let resp = await fetch("procesar_transaccion.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(datos)
    });

    let json = await resp.json();
    console.log("Respuesta del servidor:", json);

    if (json.ok) {
        alert("Guardado correctamente");
        window.location.href = "transacciones.php";
    } else {
        alert("Error: " + json.error);
    }
});
