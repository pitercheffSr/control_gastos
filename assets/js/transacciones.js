/* ============================================================
   transacciones.js — CONTROL DE LISTADO, EDITAR Y ELIMINAR
   ============================================================ */

console.log('transacciones.js cargado'); // ← único log

/* ------------------------------------------------------------
   FUNCIÓN: Cargar transacciones en la tabla
------------------------------------------------------------ */
async function cargarTransacciones() {
    try {
        const resp = await fetch('/control_gastos/controllers/TransaccionRouter.php?action=listar');
        ;
        const data = await resp.json();

        console.log('Transacciones recibidas:', data);

        const tbody = document.querySelector('#tablaTransacciones tbody');
        tbody.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML =
                "<tr><td colspan='7'>No hay transacciones</td></tr>";
            return;
        }

        data.forEach((t) => {
            const id = t.id;

            tbody.insertAdjacentHTML(
                'beforeend',
                `
                <tr data-id="${id}">
                    <td>${t.fecha ?? ''}</td>
                    <td>${t.descripcion ?? ''}</td>
                    <td>${t.categoria ?? '-'}</td>
                    <td>${t.subcategoria ?? '-'}</td>
                    <td>${t.monto} €</td>
                    <td>${t.tipo}</td>
                    <td style="text-align:right;">
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
        document.querySelector('#tablaTransacciones tbody').innerHTML =
            "<tr><td colspan='7'>Error cargando datos</td></tr>";
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

        fetch('/control_gastos/controllers/TransaccionRouter.php?action=eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
