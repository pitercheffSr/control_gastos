function abrirModalCategoria(id = null, nombre = '', tipo = 'gasto', parent_id = '') {
    document.getElementById('formCategoria').reset();
    document.getElementById('categoria_id').value = id || '';

    const parentSelect = document.getElementById('cat_parent');
    Array.from(parentSelect.options).forEach(opt => opt.disabled = false);

    if(id) {
        document.getElementById('cat_nombre').value = nombre;
        document.getElementById('cat_tipo').value = tipo;

        const selfOption = parentSelect.querySelector(`option[value="${id}"]`);
        if (selfOption) selfOption.disabled = true;
    }

    parentSelect.value = parent_id || '';

    document.getElementById('modalCategoria').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalCategoriaContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalCategoria() {
    const content = document.getElementById('modalCategoriaContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { const modal = document.getElementById('modalCategoria'); if(modal) modal.classList.add('hidden'); }, 300);
}

document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'expandedCategories';
    const startDateInput = document.getElementById('totalsStartDate');
    const endDateInput = document.getElementById('totalsEndDate');

    function getExpandedState() {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored ? new Set(JSON.parse(stored)) : new Set();
    }

    function saveExpandedState(expandedSet) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(expandedSet)));
    }

    function toggleCategory(categoryId, shouldSave = true) {
        const listItem = document.querySelector(`li[data-id="${categoryId}"]`);
        if (!listItem) return;

        const toggleBtn = listItem.querySelector('.category-toggle-btn');
        const childList = listItem.querySelector('ul.list-group');
        const expandedState = getExpandedState();

        if (childList) {
            const isHidden = childList.classList.toggle('hidden');
            toggleBtn?.querySelector('svg').classList.toggle('rotate-90', !isHidden);

            if (shouldSave) {
                isHidden ? expandedState.delete(categoryId) : expandedState.add(categoryId);
                saveExpandedState(expandedState);
            }
        }
    }

    function setDefaultDates() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        startDateInput.value = firstDay.toISOString().split('T')[0];
        endDateInput.value = lastDay.toISOString().split('T')[0];
    }

    async function loadCategoryTotals() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        if (!startDate || !endDate) return;

        document.querySelectorAll('.category-total').forEach(el => {
            el.innerHTML = '<span class="text-xs text-gray-400 italic">...</span>';
        });

        try {
            const res = await fetch(`controllers/CategoriaRouter.php?action=getTotals&startDate=${startDate}&endDate=${endDate}`);
            const data = await res.json();

            document.querySelectorAll('.category-total').forEach(el => el.textContent = '');

            if (data.success) {
                const formatter = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
                for (const categoryId in data.totals) {
                    const total = data.totals[categoryId];
                    if (total !== 0) {
                        const el = document.querySelector(`li[data-id="${categoryId}"] .category-total`);
                        if (el) el.textContent = formatter.format(Math.abs(total));
                    }
                }
            }
        } catch (err) {
            console.error("Error cargando totales de categoría:", err);
        }
    }

    function applyInitialState() {
        const expandedState = getExpandedState();
        expandedState.forEach(id => {
            toggleCategory(id, false);
        });
    }

    const lists = document.querySelectorAll('.list-group');
    lists.forEach(list => {
        new Sortable(list, {
            group: 'nested',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle',
            onEnd: async function (evt) {
                const movedItemId = evt.item.dataset.id;
                const newParentEl = evt.to;
                const newParentId = newParentEl.dataset.parentId;

                const siblingElements = Array.from(newParentEl.children);
                const siblingIds = siblingElements.map(child => child.dataset.id);

                try {
                    const res = await fetch('controllers/CategoriaRouter.php?action=updateOrder', {
                        method: 'POST',
                        body: JSON.stringify({
                            movedId: movedItemId,
                            newParentId: newParentId,
                            siblingIds: siblingIds
                        }),
                        headers: {'Content-Type': 'application/json'}
                    });
                    const result = await res.json();
                    if(!result.success) {
                        alert("Error al guardar el orden: " + (result.error || "Desconocido"));
                        location.reload();
                    }
                } catch (err) {
                    alert("Error de comunicación al guardar el orden.");
                    location.reload();
                }
            }
        });
    });

    document.getElementById('category-list-root').addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('.category-toggle-btn');
        if (toggleBtn) {
            const categoryId = toggleBtn.closest('li[data-id]').dataset.id;
            toggleCategory(categoryId);
        }
    });

    startDateInput.addEventListener('change', loadCategoryTotals);
    endDateInput.addEventListener('change', loadCategoryTotals);
    document.addEventListener('keydown', (e) => { if(e.key === "Escape") cerrarModalCategoria(); });

    setDefaultDates();
    loadCategoryTotals();
    applyInitialState();
});

document.getElementById('formCategoria').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nombreCat = document.getElementById('cat_nombre').value;
    const data = {
        id: document.getElementById('categoria_id').value,
        nombre: nombreCat,
        tipo_fijo: document.getElementById('cat_tipo').value,
        parent_id: document.getElementById('cat_parent').value || null
    };
    try {
        const res = await fetch('controllers/CategoriaRouter.php?action=save', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {'Content-Type': 'application/json'}
        });
        const result = await res.json();

        if(result.success) {
            const tieneReglas = /\(.*?\)/.test(nombreCat);
            if (tieneReglas) {
                if (confirm(`Has incluido reglas de auto-clasificación (palabras entre paréntesis).\n\n¿Quieres revisar ahora mismo todos tus movimientos "Por clasificar" y aplicarles esta regla automáticamente?`)) {
                    try {
                        const resAuto = await fetch('controllers/TransaccionRouter.php?action=autoClassify', {
                            method: 'POST',
                            body: JSON.stringify({ ids: [] }),
                            headers: { 'Content-Type': 'application/json' }
                        });
                        const dataAuto = await resAuto.json();
                        if (dataAuto.success && dataAuto.updated > 0) {
                            alert(`¡Magia aplicada! ${dataAuto.updated} movimiento(s) ha(n) sido clasificado(s).`);
                        } else if (dataAuto.success) {
                            alert(`Reglas revisadas, pero no se encontraron coincidencias en los movimientos sin clasificar.`);
                        }
                    } catch (err) {
                        console.error("Error al auto-clasificar:", err);
                    }
                }
            }
            location.reload();
        } else {
            alert("Error: " + (result.error || "Desconocido"));
        }
    } catch (err) {
        alert("Error de comunicación.");
    }
});

async function eliminarCategoria(id) {
    if (!confirm("¿Seguro que quieres borrar esta categoría personalizada?\n\nLos movimientos que la estén usando pasarán a estar 'Por clasificar'.")) return;

    try {
        const res = await fetch('controllers/CategoriaRouter.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();

        if (data.success) {
            location.reload();
        } else {
            alert("Error del servidor: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        console.error(err);
        alert("Hubo un error de comunicación. Revisa tu conexión a internet.");
    }
}
