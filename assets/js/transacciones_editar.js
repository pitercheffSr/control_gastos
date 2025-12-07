console.log("transacciones_editar.js cargado");

// -------------------------------
// REFERENCIAS DOM
// -------------------------------
const panel = document.getElementById("panelEditar");
const btnCerrar = document.getElementById("cerrarPanel");
const btnCancelar = document.getElementById("cancelarEdicion");
const fFecha = document.getElementById("e_fecha");
const fDesc = document.getElementById("e_desc");
const fMonto = document.getElementById("e_monto");
const fTipo = document.getElementById("e_tipo");
const fCat = document.getElementById("e_cat");
const fSubcat = document.getElementById("e_subcat");
const fSubsub = document.getElementById("e_subsub");

const btnGuardar = document.getElementById("guardarCambios");

window.transaccionActual = null;

// -------------------------------
// CERRAR PANEL
// -------------------------------
if (btnCerrar) {
    btnCerrar.addEventListener("click", () => {
        panel.classList.remove("visible");
        window.transaccionActual = null;
    });
}
// -------------------------------
// CANCELAR EDICIÓN
// -------------------------------
if (btnCancelar) {
    btnCancelar.addEventListener("click", () => {
        panel.classList.remove("visible");
        window.transaccionActual = null;
    });
}
// -------------------------------
// CLICK EN BOTÓN EDITAR (delegado)
// -------------------------------
document.addEventListener("click", (ev) => {
    const btn = ev.target.closest(".edit-btn");
    if (!btn) return;

    const id = btn.dataset.id;
    console.log("Editar clic en id:", id);
    
    if (!id) {
        console.error("No hay data-id en botón edit");
        return;
    }

    abrirPanelEdicion(id);
    ev.stopPropagation();
});

// -------------------------------
// ABRIR PANEL Y CARGAR DATOS
// -------------------------------
async function abrirPanelEdicion(id) {
    console.log("Abrir edición para id:", id);
    
    if (!panel) {
        console.error("Panel no existe en DOM");
        return;
    }
    
    panel.classList.add("visible");
    window.transaccionActual = id;

    try {
        const resp = await fetch("get_transaccion.php?id=" + id);
        
        if (!resp.ok) {
            console.error("Respuesta HTTP no OK:", resp.status);
            alert("Error al obtener transacción (HTTP " + resp.status + ")");
            return;
        }
        
        const data = await resp.json();
        console.log("Datos recibidos:", data);

        if (!data || data.error) {
            alert("Error: " + (data.error || "No se pudo obtener la transacción"));
            return;
        }

        // Rellenar campos (con validación)
        if (fFecha) fFecha.value = data.fecha || '';
        if (fDesc) fDesc.value = data.descripcion || '';
        if (fMonto) fMonto.value = data.monto || '';
        if (fTipo) fTipo.value = data.tipo || 'gasto';
        if (fCat) fCat.value = data.id_categoria || '';
        if (fSubcat) fSubcat.value = data.id_subcategoria || '';
        if (fSubsub) fSubsub.value = data.id_subsubcategoria || '';

    } catch (err) {
        console.error("Error cargando transacción:", err);
        alert("Error: " + err.message);
    }
}

// -------------------------------
// GUARDAR CAMBIOS
// -------------------------------
if (btnGuardar) {
    btnGuardar.addEventListener("click", async () => {
        if (!window.transaccionActual) {
            console.error("No hay transacción seleccionada");
            return;
        }

        const payload = {
            id: window.transaccionActual,
            fecha: fFecha ? fFecha.value : '',
            descripcion: fDesc ? fDesc.value : '',
            monto: fMonto ? fMonto.value : '0',
            tipo: fTipo ? fTipo.value : 'gasto',
            categoria: fCat ? fCat.value : '',
            subcategoria: fSubcat ? fSubcat.value : '',
            subsub: fSubsub ? fSubsub.value : ''
        };

        console.log("Enviando actualización:", payload);

        try {
            const resp = await fetch("procesar_transaccion_editar.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload)
            });

            if (!resp.ok) {
                console.error("Respuesta HTTP no OK:", resp.status);
                alert("Error HTTP " + resp.status);
                return;
            }

            const data = await resp.json();
            console.log("Respuesta update:", data);

            if (data.success) {
                alert("Transacción actualizada");
                location.reload();
            } else {
                alert("Error: " + (data.error || "Error desconocido"));
            }
        } catch (err) {
            console.error("Error al guardar:", err);
            alert("Error: " + err.message);
        }
    });
}
