/* ============================================================
   categorias.js
   Gestión visual e interactiva de categorías
   - Árbol jerárquico plegable
   - Colores por nivel
   - Crear / Editar / Borrar desde un único formulario
============================================================ */

console.log('categorias.js cargado');

/* ============================================================
   ESTADO GLOBAL
============================================================ */
let categoriaSeleccionada = null;

/* ============================================================
   UTILIDADES
============================================================ */
function el(tag, attrs = {}, html = '') {
    const e = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
    if (html) e.innerHTML = html;
    return e;
}

/* ============================================================
   CARGA DE CATEGORÍAS DESDE API
============================================================ */
async function cargarCategorias() {
    try {
        const resp = await fetch('/control_gastos/api/categorias.php');
        const data = await resp.json();

        const tree = buildTree(data);
        renderCategorias(tree);
        rellenarSelectPadre(data);
    } catch (e) {
        console.error(e);
        alert('Error cargando categorías');
    }
}

/* ============================================================
   CONSTRUIR ÁRBOL JERÁRQUICO
============================================================ */
function buildTree(list, parentId = null) {
    return list
        .filter((c) => c.parent_id == parentId)
        .map((c) => ({
            ...c,
            hijos: buildTree(list, c.id),
        }));
}

/* ============================================================
   RENDER DEL ÁRBOL COMPLETO
   (solo categorías raíz inicialmente)
============================================================ */
function renderCategorias(tree) {
    const cont = document.getElementById('estructuraCategorias');
    if (!cont) return;

    cont.innerHTML = '';
    tree.forEach((cat) => {
        cont.appendChild(renderNodo(cat, 1));
    });
}

/* ============================================================
   RENDER DE UN NODO (PLEGABLE)
============================================================ */
function renderNodo(cat, nivel) {
    const wrapper = el('div', { class: 'cat-wrapper' });

    const fila = el('div', { class: 'cat-nodo nivel-' + nivel });

    const izquierda = el('div', { class: 'cat-left' });
    const derecha = el('div', { class: 'cat-actions' });

    // Flecha desplegar
    let toggle = el('span', { class: 'cat-toggle' }, '');
    if (cat.hijos && cat.hijos.length) {
        toggle.textContent = '▸';
        toggle.style.cursor = 'pointer';
    }

    const nombre = el('span', { class: 'cat-nombre' }, cat.nombre);
    izquierda.append(toggle, nombre);

    // Botones
    const btnEdit = el(
        'button',
        { class: 'btn btn-link', title: 'Editar' },
        '✏️'
    );
    const btnDel = el(
        'button',
        { class: 'btn btn-link', title: 'Eliminar' },
        '🗑️'
    );

    btnEdit.onclick = (e) => {
        e.stopPropagation();
        seleccionarCategoria(cat);
    };

    btnDel.onclick = async (e) => {
        e.stopPropagation();
        if (!confirm('¿Eliminar categoría y todas sus hijas?')) return;

        const r = await fetch(
            '/control_gastos/api/categorias.php?id=' + cat.id,
            { method: 'DELETE' }
        );
        const j = await r.json();
        if (j.ok) cargarCategorias();
        else alert(j.error || 'Error eliminando');
    };

    derecha.append(btnEdit, btnDel);
    fila.append(izquierda, derecha);

    // Contenedor de hijos (plegable)
    const hijosCont = el('div', {
        class: 'cat-hijos',
        style: 'display:none',
    });

    if (cat.hijos && cat.hijos.length) {
        cat.hijos.forEach((h) =>
            hijosCont.appendChild(renderNodo(h, nivel + 1))
        );

        toggle.onclick = () => {
            const abierto = hijosCont.style.display === 'block';
            hijosCont.style.display = abierto ? 'none' : 'block';
            toggle.textContent = abierto ? '▸' : '▾';
        };
    }

    wrapper.append(fila, hijosCont);
    return wrapper;
}

/* ============================================================
   SELECT "DEPENDE DE"
============================================================ */
function rellenarSelectPadre(lista) {
    const sel = document.getElementById('cat_parent');
    if (!sel) return;

    sel.innerHTML = '<option value="">— Categoría raíz —</option>';
    lista.forEach((c) => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.nombre;
        sel.appendChild(o);
    });
}

/* ============================================================
   FORMULARIO (CREAR / EDITAR)
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    cargarCategorias();

    const form = document.getElementById('formCategoria');
    const btnCancelar = document.getElementById('btnCancelar');
    if (!form) return;

    btnCancelar?.addEventListener('click', () => {
        limpiarFormulario();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            nombre: cat_nombre.value.trim(),
            tipo: cat_tipo.value,
            parent_id: cat_parent.value || null,
        };

        let method = 'POST';
        if (categoriaSeleccionada) {
            payload.id = categoriaSeleccionada.id;
            method = 'PUT';
        }

        const r = await fetch('/control_gastos/api/categorias.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const j = await r.json();
        if (j.ok || j.success) {
            limpiarFormulario();
            cargarCategorias();
        } else {
            alert(j.error || 'Error guardando');
        }
    });
});

/* ============================================================
   SELECCIÓN / LIMPIEZA
============================================================ */
function seleccionarCategoria(cat) {
    categoriaSeleccionada = cat;

    cat_nombre.value = cat.nombre;
    cat_tipo.value = cat.tipo;
    cat_parent.value = cat.parent_id || '';

    document.getElementById('btnCancelar').style.display = 'inline-block';
}

function limpiarFormulario() {
    categoriaSeleccionada = null;
    document.getElementById('formCategoria').reset();
    document.getElementById('btnCancelar').style.display = 'none';
}
