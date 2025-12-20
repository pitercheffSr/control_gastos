/* transacciones_editar.js
   Controla el PANEL LATERAL de edición de transacciones.
   - Carga categorías (3 niveles)
   - Rellena valores al editar
   - Guarda cambios mediante procesar_transaccion_editar.php
*/

console.log('transacciones_editar.js cargado');

// -----------------------------------------------------
// REFERENCIAS DOM
// -----------------------------------------------------
const panel = document.getElementById('panelEditar');
const overlay = document.getElementById('overlayPanel');

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

// Guardamos id editando
window.transaccionActual = null;

// -----------------------------------------------------
// FUNCIÓN: Abrir panel
// -----------------------------------------------------
function abrirPanel() {
    panel.classList.add('visible');
    overlay.classList.add('visible');
}

// -----------------------------------------------------
// FUNCIÓN: Cerrar panel + limpiar campos
// -----------------------------------------------------
function cerrarPanel() {
    panel.classList.remove('visible');
    overlay.classList.remove('visible');

    window.transaccionActual = null;

    fFecha.value = '';
    fDesc.value = '';
    fMonto.value = '';
    fTipo.value = 'gasto';
    fCat.value = '';
    fSubcat.value = '';
    fSubsub.value = '';
}

// Botones cerrar
btnCerrar?.addEventListener('click', cerrarPanel);
btnCancelar?.addEventListener('click', cerrarPanel);

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarPanel();
});

// -----------------------------------------------------
// CARGAR CATEGORÍAS (3 niveles)
// -----------------------------------------------------
async function loadCategorias() {
    try {
        const resp = await fetch("/control_gastos/api/categorias/listar.php")
            ;
        if (!resp.ok) throw new Error('Error HTTP ' + resp.status);

        const cats = await resp.json();

        console.log('Categorías recibidas:', cats);

        // Limpiar selects
        fCat.innerHTML = '';
        fSubcat.innerHTML = "<option value=''>—</option>";
        fSubsub.innerHTML = "<option value=''>—</option>";

        // Nivel 1 (padre null)
        const nivel1 = cats.filter((c) => c.parent_id === null);

        nivel1.forEach((c) => {
            fCat.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
        });

        // Al cambiar categoría, cargar nivel 2
        fCat.onchange = () => {
            const idCat = parseInt(fCat.value);

            const nivel2 = cats.filter((c) => c.parent_id === idCat);

            fSubcat.innerHTML = "<option value=''>—</option>";
            fSubsub.innerHTML = "<option value=''>—</option>";

            nivel2.forEach((c) => {
                fSubcat.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
            });
        };

        // Al cambiar subcategoría, cargar nivel 3
        fSubcat.onchange = () => {
            const idSub = parseInt(fSubcat.value);
            const nivel3 = cats.filter((c) => c.parent_id === idSub);

            fSubsub.innerHTML = "<option value=''>—</option>";

            nivel3.forEach((c) => {
                fSubsub.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
            });
        };
    } catch (err) {
        console.error('Error cargando categorías:', err);
        alert('No se pudieron cargar las categorías.');
    }
}

// -----------------------------------------------------
// CARGAR TRANSACCIÓN EXISTENTE PARA EDITAR
// -----------------------------------------------------
async function loadTransaccion(id) {
    try {
        const resp = await fetch(`/control_gastos/controllers/TransaccionRouter.php?action=obtener&id=${id}`);


        if (!resp.ok) throw new Error('HTTP ' + resp.status);

        const json = await resp.json();
        console.log('Datos recibidos para editar:', json);

        if (!json.ok) throw new Error(json.error || 'Error backend');

        const data = json.data;

        // Rellenar campos
        fFecha.value = data.fecha;
        fDesc.value = data.descripcion ?? '';
        fMonto.value = data.monto;
        fTipo.value = data.tipo;

        // Seleccionar categoría, subcat, subsub
        setTimeout(() => {
            fCat.value = data.id_categoria ?? '';
            fCat.dispatchEvent(new Event('change'));

            setTimeout(() => {
                fSubcat.value = data.id_subcategoria ?? '';
                fSubcat.dispatchEvent(new Event('change'));

                setTimeout(() => {
                    fSubsub.value = data.id_subsubcategoria ?? '';
                }, 80);
            }, 80);
        }, 80);
    } catch (err) {
        console.error('Error cargando transacción:', err);
        alert('Error cargando datos de la transacción.');
    }
}

// -----------------------------------------------------
// GUARDAR CAMBIOS (EDITAR)
// -----------------------------------------------------
btnGuardar.addEventListener('click', async () => {
    if (!window.transaccionActual) {
        alert('No hay transacción seleccionada.');
        return;
    }

    const payload = {
        id: window.transaccionActual,
        fecha: fFecha.value,
        descripcion: fDesc.value,
        monto: fMonto.value,
        tipo: fTipo.value,
        categoria: fCat.value,
        subcategoria: fSubcat.value,
        subsub: fSubsub.value,
    };

    console.log('Enviando actualización:', payload);

    try {
        const response = await fetch(
            "/control_gastos/controllers/TransaccionRouter.php?action=editar",
            {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            }
        );

        const json = await response.json();
        console.log('Respuesta update:', json);

        if (!json.ok) {
            throw new Error(json.error || 'Error backend');
        }

        alert('Transacción actualizada.');
        cerrarPanel();

        // ⬇️ refrescar tabla SIN recargar página
        if (typeof cargarTransacciones === 'function') {
            cargarTransacciones();
        }
    } catch (err) {
        console.error(err);
        alert('Error al guardar: ' + err.message);
    }
});

// -----------------------------------------------------
// ESCUCHAR CLICK EN ICONO EDITAR DESDE transacciones.js
// -----------------------------------------------------
window.addEventListener('tx:editar', async (ev) => {
    const id = ev.detail?.id;
    if (!id) return;

    window.transaccionActual = id;

    await loadCategorias();
    await loadTransaccion(id);

    abrirPanel();
});
