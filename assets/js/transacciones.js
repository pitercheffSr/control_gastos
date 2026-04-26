// ============================================
// LÓGICA DE LA TABLA (FILTRADO, PAGINACIÓN, EDICIÓN)
// ============================================
const escapeHtml = (unsafe) => (unsafe == null) ? '' : String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");

const estadoPaginacion = {
    paginaActual: 1,
    limite: 10, // Número de items por página
    totalItems: 0
};
const estadoOrdenacion = {
    sortBy: 'fecha',
    sortOrder: 'DESC'
};

// Función para mostrar una notificación flotante moderna y no intrusiva
function mostrarNotificacion(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-xl shadow-2xl font-bold z-50 transition-all duration-300 transform translate-y-10 opacity-0';
    toast.innerText = mensaje;
    document.body.appendChild(toast);

    requestAnimationFrame(() => { toast.classList.remove('translate-y-10', 'opacity-0'); });

    setTimeout(() => {
        toast.classList.add('translate-y-10', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

let seleccionados = new Set(); // Almacena los IDs de las filas seleccionadas
let filaEnEdicion = null; // Para evitar editar múltiples filas a la vez

function limpiarFiltros() {
    document.getElementById('filtroMes').value = '';
    document.getElementById('filtroInicio').value = '';
    document.getElementById('filtroFin').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('filtroTexto').value = '';
    const selectOrden = document.getElementById('filtroOrden');
    if (selectOrden) selectOrden.value = 'fecha-DESC';
    estadoOrdenacion.sortBy = 'fecha';
    estadoOrdenacion.sortOrder = 'DESC';
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
}

function aplicarFiltroMes() {
    const mesVal = document.getElementById('filtroMes').value;
    if (!mesVal) {
        limpiarFiltros();
        return;
    }

    const [yearStr, monthStr] = mesVal.split('-');
    let year = parseInt(yearStr);
    let month = parseInt(monthStr);
    let fInicio, fFin;

    if (DIA_INICIO === 1) {
        fInicio = `${year}-${monthStr}-01`;
        let lastDay = new Date(year, month, 0).getDate();
        fFin = `${year}-${monthStr}-${lastDay.toString().padStart(2, '0')}`;
    } else {
        let prevMonth = month - 1; let prevYear = year;
        if (prevMonth === 0) { prevMonth = 12; prevYear--; }
        fInicio = `${prevYear}-${prevMonth.toString().padStart(2, '0')}-${DIA_INICIO.toString().padStart(2, '0')}`;
        let dFin = new Date(year, month - 1, DIA_INICIO - 1);
        let finM = (dFin.getMonth() + 1).toString().padStart(2, '0'); let finD = dFin.getDate().toString().padStart(2, '0');
        fFin = `${dFin.getFullYear()}-${finM}-${finD}`;
    }

    document.getElementById('filtroInicio').value = fInicio;
    document.getElementById('filtroFin').value = fFin;
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
}

async function cargarTransacciones() {
    seleccionados.clear();
    updateBulkActionsBar();

    const tbody = document.getElementById('tablaMovimientos');
    const fInicio = document.getElementById('filtroInicio').value;
    const fFin = document.getElementById('filtroFin').value;
    const fCategoria = document.getElementById('filtroCategoria').value;
    const fTexto = document.getElementById('filtroTexto').value;
    const fTipoGlobal = document.getElementById('filtroTipoGlobal').value;

    const params = new URLSearchParams({
        page: estadoPaginacion.paginaActual,
        limit: estadoPaginacion.limite,
        startDate: fInicio,
        endDate: fFin,
        categoryId: fCategoria,
        searchText: fTexto,
        sortBy: estadoOrdenacion.sortBy,
        sortOrder: estadoOrdenacion.sortOrder,
        tipo: fTipoGlobal
    });

    // En lugar de vaciar la tabla y perder la posición de scroll, la difuminamos
    tbody.style.opacity = '0.5';
    tbody.style.pointerEvents = 'none';

    try {
        console.log('Fetching transactions with params:', params.toString());
        const resp = await fetch(`controllers/TransaccionRouter.php?action=getPaginated&${params.toString()}&_=${new Date().getTime()}`); // Añadir cache-buster
        const json = await resp.json();

        tbody.style.opacity = '1';
        tbody.style.pointerEvents = 'auto';
        tbody.innerHTML = ''; // Limpiar "Cargando..."

        if (!json.success) throw new Error(json.error || 'Error en la respuesta del servidor.');

        console.log('Received transactions data:', json.data);
        estadoPaginacion.totalItems = json.total;
        renderPaginacion();
        actualizarIndicadoresOrden();

        if (json.data.length === 0) {
            const hayFiltros = fInicio || fFin || fTexto || fCategoria;
            tbody.innerHTML = hayFiltros
                ? '<tr><td colspan="6" class="p-8 text-center text-gray-500 font-medium italic">No se encontraron movimientos para los filtros aplicados.</td></tr>'
                : '<tr><td colspan="6" class="p-8 text-center text-gray-400">No hay movimientos registrados.</td></tr>';
            return;
        }

        console.log('Calling renderTabla...');
        renderTabla(json.data, tbody);

    } catch (err) {
        tbody.style.opacity = '1';
        tbody.style.pointerEvents = 'auto';
        tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-500">Error al cargar: ${err.message}</td></tr>`;
    }
}

function updateBulkActionsBar() {
    const bar = document.getElementById('bulk-actions-bar');
    const countSpan = document.getElementById('selection-count');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    if (seleccionados.size > 0) {
        bar.classList.remove('hidden', 'translate-y-full');
        countSpan.textContent = seleccionados.size;
    } else {
        bar.classList.add('translate-y-full');
        // Esperar a que termine la animación para ocultarlo
        setTimeout(() => { if (seleccionados.size === 0) bar.classList.add('hidden'); }, 300);
    }

    const totalVisibleCheckboxes = document.querySelectorAll('#tablaMovimientos .row-checkbox').length;
    if (totalVisibleCheckboxes > 0 && seleccionados.size === totalVisibleCheckboxes) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = seleccionados.size > 0;
    }
}

function actualizarIndicadoresOrden() {
    document.querySelectorAll('th[data-sort]').forEach(th => {
        // Limpiar indicadores previos
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.remove();

        // Añadir indicador si es la columna activa
        if (th.dataset.sort === estadoOrdenacion.sortBy) {
            const iconSpan = document.createElement('span');
            iconSpan.className = 'sort-icon ml-1';
            // Usamos innerText para seguridad, aunque aquí no es crítico
            iconSpan.innerText = estadoOrdenacion.sortOrder === 'ASC' ? '▲' : '▼';
            th.appendChild(iconSpan);
        }
    });
}

function renderPaginacion() {
    const contenedor = document.getElementById('paginacionContenedor');
    const totalPaginas = Math.ceil(estadoPaginacion.totalItems / estadoPaginacion.limite);
    const paginaActual = estadoPaginacion.paginaActual;

    if (totalPaginas <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    const primerItem = (paginaActual - 1) * estadoPaginacion.limite + 1;
    const ultimoItem = Math.min(paginaActual * estadoPaginacion.limite, estadoPaginacion.totalItems);

    let html = `
        <div class="flex-1 flex justify-between sm:hidden">
            <p class="text-sm text-gray-700">
                Mostrando
                <span class="font-medium">${primerItem}</span>
                a
                <span class="font-medium">${ultimoItem}</span>
                de
                <span class="font-medium">${estadoPaginacion.totalItems}</span>
                resultados
            </p>
            <div class="flex">
                <button id="btnPrevMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${paginaActual <= 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${paginaActual <= 1 ? 'disabled' : ''}>
                    Anterior
                </button>
                <button id="btnNextMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${paginaActual >= totalPaginas ? 'opacity-50 cursor-not-allowed' : ''}" ${paginaActual >= totalPaginas ? 'disabled' : ''}>
                    Siguiente
                </button>
            </div>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando
                    <span class="font-medium">${primerItem}</span>
                    a
                    <span class="font-medium">${ultimoItem}</span>
                    de
                    <span class="font-medium">${estadoPaginacion.totalItems}</span>
                    resultados
                </p>
            </div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button id="btnPrev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${paginaActual <= 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${paginaActual <= 1 ? 'disabled' : ''}>
                    <span class="sr-only">Anterior</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
    `;

    // Lógica para los números de página
    const maxPagesToShow = 5;
    let startPage = Math.max(1, paginaActual - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPaginas, startPage + maxPagesToShow - 1);

    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, totalPaginas - maxPagesToShow + 1);
    }

    if (startPage > 1) {
        html += `
            <button data-page="1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                1
            </button>
        `;
        if (startPage > 2) {
            html += `
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                    ...
                </span>
            `;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button data-page="${i}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ${i === paginaActual ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                ${i}
            </button>
        `;
    }

    if (endPage < totalPaginas) {
        if (endPage < totalPaginas - 1) {
            html += `
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                    ...
                </span>
            `;
        }
        html += `
            <button data-page="${totalPaginas}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                ${totalPaginas}
            </button>
        `;
    }

    html += `
                    <button id="btnNext" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${paginaActual >= totalPaginas ? 'opacity-50 cursor-not-allowed' : ''}" ${paginaActual >= totalPaginas ? 'disabled' : ''}>
                        <span class="sr-only">Siguiente</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    `;

    contenedor.innerHTML = html;

    document.getElementById('btnPrev')?.addEventListener('click', () => {
        if (estadoPaginacion.paginaActual > 1) {
            estadoPaginacion.paginaActual--;
            cargarTransacciones();
        }
    });
    document.getElementById('btnNext')?.addEventListener('click', () => {
        if (estadoPaginacion.paginaActual < totalPaginas) {
            estadoPaginacion.paginaActual++;
            cargarTransacciones();
        }
    });
    document.getElementById('btnPrevMobile')?.addEventListener('click', () => {
        if (estadoPaginacion.paginaActual > 1) {
            estadoPaginacion.paginaActual--;
            cargarTransacciones();
        }
    });
    document.getElementById('btnNextMobile')?.addEventListener('click', () => {
        if (estadoPaginacion.paginaActual < totalPaginas) {
            estadoPaginacion.paginaActual++;
            cargarTransacciones();
        }
    });

    contenedor.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page, 10);
            if (page !== estadoPaginacion.paginaActual) {
                estadoPaginacion.paginaActual = page;
                cargarTransacciones();
            }
        });
    });
}

function activarEdicionEnFila(tr) {
    if (filaEnEdicion && filaEnEdicion !== tr) {
        cargarTransacciones();
        return;
    }

    filaEnEdicion = tr;
    tr.classList.add('edit-mode', 'bg-indigo-50');

    const data = JSON.parse(tr.dataset.transaction);
    const cells = tr.querySelectorAll('td');
    const inputClasses = "w-full p-2 border border-indigo-300 rounded-md focus:ring-2 focus:ring-indigo-500 outline-none text-sm";

    cells[0].innerHTML = `<input type="date" class="edit-fecha ${inputClasses}" value="${data.fecha}">`;
    cells[1].innerHTML = `<input type="text" class="edit-descripcion ${inputClasses}" value="${escapeHtml(data.descripcion)}">`;
    cells[2].innerHTML = `<select class="edit-categoria ${inputClasses}">${opcionesCategoriaHTML}</select>`;
    cells[2].querySelector('select').value = data.categoria_id || "";
    cells[3].innerHTML = `<input type="number" step="0.01" class="edit-importe ${inputClasses} text-right" value="${data.importe}">`;
    cells[3].classList.remove('text-red-500', 'text-green-500');
    cells[4].innerHTML = `
        <div class="flex justify-center gap-1">
            <button onclick="guardarEdicionEnFila(this.closest('tr'))" class="text-green-600 p-1.5 rounded hover:bg-green-100" title="Guardar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
            </button>
            <button onclick="cancelarEdicionEnFila()" class="text-red-500 p-1.5 rounded hover:bg-red-100" title="Cancelar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.607a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </div>
    `;
}

function cancelarEdicionEnFila() {
    filaEnEdicion = null;
    cargarTransacciones();
}

async function guardarEdicionEnFila(tr) {
    const originalData = JSON.parse(tr.dataset.transaction);

    const payload = {
        id: originalData.id,
        fecha: tr.querySelector('.edit-fecha').value,
        descripcion: tr.querySelector('.edit-descripcion').value,
        importe: tr.querySelector('.edit-importe').value,
        categoria_id: tr.querySelector('.edit-categoria').value || null
    };

    if (!payload.fecha || !payload.importe || !payload.descripcion) {
        alert('Fecha, Descripción e Importe no pueden estar vacíos.');
        return;
    }

    try {
        const resp = await fetch(`controllers/TransaccionRouter.php?action=save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (json.success) {
            filaEnEdicion = null;
            cargarTransacciones();
        } else {
            alert('Error al guardar: ' + (json.error || 'Error desconocido'));
        }
    } catch (err) {
        console.error('Error al guardar en línea:', err);
        alert('Error de conexión al guardar.');
    }
}

function generarContenidoHtmlFila(m) {
    const formatter = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
    const isGasto = m.importe < 0;
    const fechaF = new Date(m.fecha + 'T00:00:00').toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

    const textoBuscado = document.getElementById('filtroTexto').value.trim();
    const highlight = (text) => {
        const safeText = escapeHtml(text || '');
        if (!textoBuscado) return safeText;

        const safeQuery = escapeHtml(textoBuscado).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safeQuery})`, 'gi');
        return safeText.replace(regex, '<mark class="bg-yellow-200 text-yellow-900 font-extrabold rounded px-0.5">$1</mark>');
    };

    return `
        <td class="p-4 w-4">
            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer" data-id="${m.id}">
        </td>
        <td class="p-4 text-sm text-gray-500 font-medium">${fechaF}</td>
        <td class="p-4 font-bold text-gray-800">${highlight(m.descripcion)}</td>
        <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-xs font-bold">${highlight(m.categoria_nombre || 'Por clasificar')}</span></td>
        <td class="p-4 text-right font-extrabold ${isGasto ? 'text-red-500' : 'text-green-500'}">
            ${formatter.format(Math.abs(m.importe))}
        </td>
        <td class="p-4 text-center">
            <button onclick="activarEdicionEnFila(this.closest('tr'))" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition" title="Editar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </button>
            <button onclick="eliminarTransaccion(${m.id})" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition" title="Eliminar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </td>
    `;
}

function reemplazarContenidoFila(tr, m) {
    tr.classList.remove('edit-mode', 'bg-indigo-50');
    tr.dataset.transaction = JSON.stringify(m);
    tr.innerHTML = generarContenidoHtmlFila(m);
}

function renderTabla(movimientos, tbody) {
    movimientos.forEach(m => {
        const tr = document.createElement('tr');
        tr.className = 'fila-movimiento hover:bg-gray-50 transition';
        tr.draggable = true;
        tr.classList.add('cursor-move');
        reemplazarContenidoFila(tr, m);
        tbody.appendChild(tr);
    });
}

async function eliminarTransaccion(id) {
    if (!confirm("¿Seguro que quieres borrar este movimiento?")) return;
    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrf_token
            }
        });
        const data = await res.json();
        if (data.success) {
            await cargarTransacciones();
            mostrarNotificacion("¡Hecho! Movimiento eliminado.");
        } else {
            alert("Error al borrar: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        alert("Error de comunicación.");
    }
}

async function eliminarSeleccionados() {
    const ids = Array.from(seleccionados);
    if (ids.length === 0) return;

    if (!confirm(`¿Seguro que quieres borrar ${ids.length} movimiento(s)? Esta acción no se puede deshacer.`)) return;

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=deleteMultiple', {
            method: 'POST',
            body: JSON.stringify({ ids }),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token }
        });
        const data = await res.json();
        if (data.success) {
            await cargarTransacciones();
            mostrarNotificacion("¡Hecho! Movimientos eliminados correctamente.");
        } else {
            alert("Error al borrar: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        alert("Error de comunicación al intentar borrar.");
    }
}

async function cambiarCategoriaSeleccionados() {
    const ids = Array.from(seleccionados);
    const categoriaId = document.getElementById('bulk-category-select').value;

    if (ids.length === 0) {
        alert("No hay transacciones seleccionadas.");
        return;
    }

    if (!confirm(`¿Seguro que quieres cambiar la categoría de ${ids.length} movimiento(s)?`)) return;

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=updateCategoryMultiple', {
            method: 'POST',
            body: JSON.stringify({ ids: ids, categoria_id: categoriaId || null }),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token }
        });
        const data = await res.json();
        if (data.success) {
            await cargarTransacciones();
            mostrarNotificacion("¡Hecho! Categorías actualizadas correctamente.");
        } else {
            alert("Error al actualizar categorías: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        alert("Error de comunicación al intentar actualizar.");
    }
}

async function reassignTransactionCategory(transactionId, categoryId) {
    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=reassignCategory', {
            method: 'POST',
            body: JSON.stringify({ transactionId, categoryId: categoryId || null }),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token }
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('filtroCategoria').value = '';
            await cargarTransacciones();
            mostrarNotificacion("¡Hecho! Categoría reasignada.");
        } else {
            alert("Error al reasignar categoría: " + (data.error || "Desconocido"));
            cargarTransacciones();
        }
    } catch (err) {
        console.error("Error de comunicación al reasignar:", err);
        alert("Error de comunicación al reasignar.");
    }
}

async function autoClasificar(ids = []) {
    const text = ids.length > 0
        ? `¿Forzar auto-clasificación en ${ids.length} movimiento(s) seleccionado(s) evaluando de nuevo tus reglas entre paréntesis?`
        : `¿Aplicar tus reglas automáticamente a todos los movimientos que estén "Por clasificar"?`;

    if (!confirm(text)) return;

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=autoClassify', {
            method: 'POST',
            body: JSON.stringify({ ids }),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token }
        });
        const data = await res.json();
        if (data.success) {
            await cargarTransacciones();
            mostrarNotificacion(`¡Magia aplicada! ${data.updated} movimiento(s) clasificado(s).`);
        } else {
            alert("Error al auto-clasificar: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        alert("Error de comunicación al intentar auto-clasificar.");
    }
}

// Event Listeners para los filtros
document.getElementById('filtroMes').addEventListener('change', aplicarFiltroMes);
document.getElementById('filtroInicio').addEventListener('change', () => {
    document.getElementById('filtroMes').value = '';
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});
document.getElementById('filtroFin').addEventListener('change', () => {
    document.getElementById('filtroMes').value = '';
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});

document.getElementById('filtroCategoria').addEventListener('change', () => {
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});

document.getElementById('filtroOrden').addEventListener('change', (e) => {
    const [sortBy, sortOrder] = e.target.value.split('-');
    estadoOrdenacion.sortBy = sortBy;
    estadoOrdenacion.sortOrder = sortOrder;
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});

let debounceTimer;
document.getElementById('filtroTexto').addEventListener('keyup', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        estadoPaginacion.paginaActual = 1;
        cargarTransacciones();
    }, 400);
});

document.getElementById('filtroTipoGlobal').addEventListener('change', () => {
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const categoryIdFromUrl = urlParams.get('categoryId');
    const startDateFromUrl = urlParams.get('startDate');
    const endDateFromUrl = urlParams.get('endDate');

    if (categoryIdFromUrl) document.getElementById('filtroCategoria').value = categoryIdFromUrl;
    if (startDateFromUrl) document.getElementById('filtroInicio').value = startDateFromUrl;
    if (endDateFromUrl) document.getElementById('filtroFin').value = endDateFromUrl;

    cargarTransacciones();

    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const newSortBy = th.dataset.sort;
            if (estadoOrdenacion.sortBy === newSortBy) {
                estadoOrdenacion.sortOrder = estadoOrdenacion.sortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                estadoOrdenacion.sortBy = newSortBy;
                estadoOrdenacion.sortOrder = (newSortBy === 'descripcion' || newSortBy === 'categoria_nombre') ? 'ASC' : 'DESC';
            }
            const selectOrden = document.getElementById('filtroOrden');
            if (selectOrden) selectOrden.value = `${estadoOrdenacion.sortBy}-${estadoOrdenacion.sortOrder}`;
            estadoPaginacion.paginaActual = 1;
            cargarTransacciones();
        });
    });

    document.getElementById('btnAutoClasificar')?.addEventListener('click', () => autoClasificar([]));
    document.getElementById('btnAutoClasificarSeleccionados')?.addEventListener('click', () => autoClasificar(Array.from(seleccionados)));
    document.getElementById('btnEliminarSeleccionados').addEventListener('click', eliminarSeleccionados);
    document.getElementById('btnAplicarCategoria').addEventListener('click', cambiarCategoriaSeleccionados);

    document.getElementById('selectAllCheckbox').addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('#tablaMovimientos .row-checkbox');
        checkboxes.forEach(checkbox => {
            const id = checkbox.dataset.id;
            if (e.target.checked) {
                checkbox.checked = true;
                seleccionados.add(id);
            } else {
                checkbox.checked = false;
                seleccionados.delete(id);
            }
        });
        updateBulkActionsBar();
    });

    document.getElementById('tablaMovimientos').addEventListener('change', (e) => {
        if (!e.target.classList.contains('row-checkbox')) return;
        e.target.checked ? seleccionados.add(e.target.dataset.id) : seleccionados.delete(e.target.dataset.id);
        updateBulkActionsBar();
    });

    const dropZone = document.getElementById('category-drop-zone');
    const tablaMovimientos = document.getElementById('tablaMovimientos');

    tablaMovimientos.addEventListener('dragstart', (e) => {
        const tr = e.target.closest('.fila-movimiento');
        if (tr) {
            const transactionData = JSON.parse(tr.dataset.transaction);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', transactionData.id.toString());
            setTimeout(() => { tr.classList.add('opacity-40'); }, 0);
        }
    });

    tablaMovimientos.addEventListener('dragend', (e) => {
        document.querySelectorAll('.fila-movimiento.opacity-40').forEach(el => el.classList.remove('opacity-40'));
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        const targetCategory = e.target.closest('.droppable-category');
        if (targetCategory) {
            dropZone.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            targetCategory.classList.add('drag-over');
        }
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.target.closest('.droppable-category')?.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.target.closest('.droppable-category')?.classList.remove('drag-over');
        const categoryId = e.target.closest('.droppable-category')?.dataset.categoryId;
        const transactionIdToReassign = e.dataTransfer.getData('text/plain');
        if (transactionIdToReassign && categoryId !== undefined) {
            reassignTransactionCategory(transactionIdToReassign, categoryId);
        }
    });
});

window.addEventListener('tx:saved', (e) => {
    cargarTransacciones();
});

document.getElementById('btnExportarCSV').addEventListener('click', () => {
    const fInicio = document.getElementById('filtroInicio').value;
    const fFin = document.getElementById('filtroFin').value;
    const fCategoria = document.getElementById('filtroCategoria').value;
    const fTexto = document.getElementById('filtroTexto').value;
    const fTipoGlobal = document.getElementById('filtroTipoGlobal').value;

    const params = new URLSearchParams({
        startDate: fInicio,
        endDate: fFin,
        categoryId: fCategoria,
        searchText: fTexto,
        sortBy: estadoOrdenacion.sortBy,
        sortOrder: estadoOrdenacion.sortOrder,
        tipo: fTipoGlobal
    });

    window.location.href = `controllers/ExportarRouter.php?${params.toString()}`;
});
