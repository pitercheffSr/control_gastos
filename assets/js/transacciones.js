/* File: assets/js/transacciones.js
   -------------------------------
   Carga y renderiza la lista de transacciones.
   Provee las acciones: editar (delegado) y eliminar (delegado).
   - Usa fetch a "api_transacciones.php" para obtener datos.
   - Usa POST JSON a "api_eliminar_transaccion.php" para borrar.
   - Event delegation para que los botones siempre funcionen aunque
     la tabla se regenere dinámicamente.
   - Comentado para que sepas qué hace cada bloque.
*/

console.log('transacciones.js cargado');

const API_LIST = 'api_transacciones.php'; // lista
const API_DELETE = 'api_eliminar_transaccion.php'; // borrar

// Util: crea un SVG fino (icono) para usar en botones (retorna string)
function svgIcon(name) {
    if (name === 'edit') {
        return `<svg class="icon-edit" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1.003 1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
        </svg>`;
    }
    if (name === 'delete') {
        return `<svg class="icon-delete" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
        </svg>`;
    }
    return '';
}

// Render de una fila; devuelve <tr> HTML string
function renderRow(t) {
    // datos seguros (evitar "undefined")
    const fecha = t.fecha ?? '';
    const desc = t.descripcion ?? '';
    const cat = t.categoria ?? t.nombre_categoria ?? '-';
    const sub = t.subcategoria ?? '-';
    const monto = t.monto === undefined || t.monto === null ? '0.00' : t.monto;
    const tipo = t.tipo ?? '';

    return `
    <tr data-id="${t.id}">
        <td>${fecha}</td>
        <td>${escapeHtml(desc)}</td>
        <td>${escapeHtml(cat)}</td>
        <td>${escapeHtml(sub)}</td>
        <td>${monto} €</td>
        <td>${tipo}</td>
        <td style="text-align:right">
            <button class="edit-btn" data-id="${
                t.id
            }" aria-label="Editar transacción ${t.id}" title="Editar">
                ${svgIcon('edit')}
            </button>

            <button class="delete-btn" data-id="${
                t.id
            }" aria-label="Eliminar transacción ${t.id}" title="Eliminar">
                ${svgIcon('delete')}
            </button>
        </td>
    </tr>`;
}

// Escape básico para evitar inyección en HTML (para textos)
function escapeHtml(s) {
    if (!s && s !== 0) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ---------- transacciones.js (fragmento a pegar/replace) ---------- */

console.log('transacciones.js cargado');

// carga y pinta las transacciones
async function cargarTransacciones() {
    try {
        const resp = await fetch('api_transacciones.php');
        const data = await resp.json();

        console.log('Transacciones recibidas:', data);

        const tbody = document.querySelector('#tablaTransacciones tbody');
        tbody.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML =
                "<tr><td colspan='7'>No hay transacciones</td></tr>";
            return;
        }

        // Generar filas con data-id reales en botones
        data.forEach((t) => {
            const id = t.id;
            const fecha = t.fecha ?? '';
            const desc = t.descripcion ?? '';
            const cat = t.categoria ?? '-';
            const sub = t.subcategoria ?? '-';
            const monto = t.monto ?? '0.00';
            const tipo = t.tipo ?? '';

            tbody.insertAdjacentHTML(
                'beforeend',
                `
                <tr data-id="${id}">
                    <td>${fecha}</td>
                    <td>${desc}</td>
                    <td>${cat}</td>
                    <td>${sub}</td>
                    <td>${monto} €</td>
                    <td>${tipo}</td>
                    <td style="text-align:right">
                        <button class="edit-btn" data-id="${id}" title="Editar">
                            <i class="icon icon-edit"></i>
                        </button>
                        <button class="delete-btn" data-id="${id}" title="Eliminar">
                            <i class="icon icon-delete"></i>
                        </button>
                    </td>
                </tr>
            `
            );
        });
    } catch (err) {
        console.error('Error cargando transacciones:', err);
        const tbody = document.querySelector('#tablaTransacciones tbody');
        tbody.innerHTML =
            "<tr><td colspan='7'>Error cargando transacciones</td></tr>";
    }
}

// llamar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    cargarTransacciones();
});

function showTableError(msg) {
    const tbody = document.querySelector('#tablaTransacciones tbody');
    if (tbody)
        tbody.innerHTML =
            `<tr><td colspan="7">` + escapeHtml(msg) + `</td></tr>`;
}

// Delegated click handler (editar / borrar)
document.addEventListener('click', function (ev) {
    // Edit
    const btnEdit = ev.target.closest && ev.target.closest('.edit-btn');
    if (btnEdit) {
        ev.preventDefault();
        const id = btnEdit.dataset.id;
        if (!id) {
            console.error('edit-btn sin data-id');
            return;
        }
        // Abrir panel: el otro script transacciones_editar.js escucha clicks .edit-btn
        // Pero podemos también disparar una llamada directa si queremos (opcional)
        console.log('Editar clic en id:', id);
        // Si quieres abrir manualmente aquí en vez de depender de transacciones_editar.js:
        // window.openEditPanel && window.openEditPanel(id);
        return;
    }

    // Delete
    const btnDel = ev.target.closest && ev.target.closest('.delete-btn');
    if (btnDel) {
        ev.preventDefault();
        const id = btnDel.dataset.id;
        if (!id) {
            console.error('delete-btn sin data-id');
            return;
        }
        solicitarEliminarTransaccion(id);
        return;
    }
});

// Confirmar y llamar API para eliminar
async function solicitarEliminarTransaccion(id) {
    if (!confirm('¿Seguro que quieres eliminar la transacción #' + id + '?'))
        return;

    try {
        const resp = await fetch(API_DELETE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(id) }),
            cache: 'no-store',
        });

        if (!resp.ok) {
            console.error('Error HTTP al borrar:', resp.status);
            alert('Error al borrar (HTTP ' + resp.status + ')');
            return;
        }

        const json = await resp.json();
        console.log('Respuesta eliminar:', json);

        // Aceptamos { ok: true } o { success: true } (compatibilidad)
        if (json.ok || json.success) {
            // actualización sin recargar toda la página
            await cargarTransacciones();
            // opcional: pequeño feedback
            showTemporaryToast('Transacción eliminada');
        } else {
            const e = json.error || json.message || 'Error desconocido';
            alert('No se pudo borrar: ' + e);
        }
    } catch (err) {
        console.error('Error borrando transacción:', err);
        alert('Error al borrar: ' + err.message);
    }
}

// Simple toast temporal en pantalla (no intrusivo)
function showTemporaryToast(text, time = 1600) {
    const t = document.createElement('div');
    t.textContent = text;
    t.style.position = 'fixed';
    t.style.right = '18px';
    t.style.bottom = '18px';
    t.style.padding = '10px 14px';
    t.style.background = 'rgba(0,0,0,0.76)';
    t.style.color = 'white';
    t.style.borderRadius = '8px';
    t.style.zIndex = 12000;
    t.style.fontSize = '0.95rem';
    document.body.appendChild(t);
    setTimeout(() => {
        t.style.transition = 'opacity 280ms';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 300);
    }, time);
}

// Iniciar cuando DOM listo
document.addEventListener('DOMContentLoaded', () => {
    cargarTransacciones();
});
/* Delegated handlers para Editar / Eliminar (un solo listener, eficiente) */
document.addEventListener('click', (ev) => {
    const btnEdit = ev.target.closest && ev.target.closest('.edit-btn');
    if (btnEdit) {
        const id = btnEdit.dataset.id;
        console.log('Editar clic en id:', id);
        // Disparamos evento que escucha transacciones_editar.js
        window.dispatchEvent(new CustomEvent('tx:editar', { detail: { id } }));
        ev.preventDefault();
        return;
    }

    const btnDel = ev.target.closest && ev.target.closest('.delete-btn');
    if (btnDel) {
        const id = btnDel.dataset.id;
        console.log('Eliminar clic en id:', id);

        // confirm y petición a API/eliminar
        if (!confirm('¿Seguro que quieres eliminar esta transacción?')) return;

        // Llamada a tu endpoint existente (ajusta si tu endpoint difiere)
        fetch('api_eliminar_transaccion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id }),
        })
            .then((r) => r.json())
            .then((j) => {
                if (j.ok) {
                    alert('Transacción eliminada');
                    cargarTransacciones();
                } else {
                    alert('Error eliminando: ' + (j.error || 'error'));
                }
            })
            .catch((err) => {
                console.error('Error eliminar:', err);
                alert('Error al eliminar transacción.');
            });

        ev.preventDefault();
        return;
    }
});
