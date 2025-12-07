// assets/js/transacciones.js
console.log("transacciones.js cargado");

async function cargarTransacciones() {
    try {
        const resp = await fetch("api_transacciones.php");
        const data = await resp.json();
        console.log("Transacciones recibidas:", data);

        const tbody = document.querySelector("#tablaTransacciones tbody");
        if (!tbody) {
            console.warn("No existe #tablaTransacciones tbody en DOM");
            return;
        }
        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7">No hay transacciones</td></tr>`;
            return;
        }

        data.forEach(t => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${t.fecha ?? ''}</td>
                <td>${t.descripcion ?? ''}</td>
                <td>${t.categoria ?? '-'}</td>
                <td>${t.subcategoria ?? '-'}</td>
                <td>${t.monto ?? ''} €</td>
                <td>${t.tipo ?? ''}</td>
                <td style="text-align:right; white-space:nowrap;">
                    <button class="btn btn-link btn-sm edit-btn" data-id="${t.id}" title="Editar">
                        <svg width="20" height="20" viewBox="0 0 24 24" class="icon-edit" aria-hidden="true">
                          <path fill="#1E88E5" d="M3 17.25V21h3.75l11.06-11.06-3.75-3.75L3 17.25zm15.71-9.04c.39-.39.39-1.02 0-1.41L16.2 4.29c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.99-2.66z"/>
                        </svg>
                    </button>

                    <button class="btn btn-link btn-sm delete-btn" data-id="${t.id}" title="Eliminar">
                        <svg width="20" height="20" viewBox="0 0 24 24" class="icon-delete" aria-hidden="true">
                          <path fill="#1E88E5" d="M9 3c0-.552.448-1 1-1h4c.552 0 1 .448 1 1v1h5v2H4V4h5V3Zm1 3v12h2V6h-2Zm4 0v12h2V6h-2ZM7 6h2v12H7V6Zm10 12c0 .552-.448 1-1 1H8c-.552 0-1-.448-1-1V6h2v12h6V6h2v12Z"/>
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (err) {
        console.error("Error cargando transacciones:", err);
    }
}

// Delegate event listeners (works after rows are injected)
document.addEventListener("click", function (e) {
    // eliminar
    const del = e.target.closest && e.target.closest(".delete-btn");
    if (del) {
        const id = del.dataset.id;
        if (id) {
            eliminarTransaccion(id);
        }
        return;
    }

    // editar (abrirá el panel, transacciones_editar.js hará el resto por delegación también)
    const ed = e.target.closest && e.target.closest(".edit-btn");
    if (ed) {
        // No hacemos nada aquí porque transacciones_editar.js escucha clicks y hace el trabajo.
        // Pero dejamos un log para debugging.
        console.log("Editar clic en id:", ed.dataset.id);
        return;
    }
});

// función de eliminación global (si ya la tenías, esta sobrescribe para seguridad)
async function eliminarTransaccion(id) {
    if (!confirm("¿Seguro que deseas eliminar esta transacción?")) return;

    try {
        const resp = await fetch("api_eliminar_transaccion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        });
        const json = await resp.json();
        if (json.ok) {
            // recargar datos
            if (typeof cargarTransacciones === "function") {
                cargarTransacciones();
            } else {
                location.reload();
            }
        } else {
            alert("Error al eliminar: " + (json.error || "respuesta no esperada"));
        }
    } catch (err) {
        console.error("Error al eliminar:", err);
        alert("Error al eliminar (mira consola).");
    }
}

// arrancar al cargar
document.addEventListener("DOMContentLoaded", cargarTransacciones);
