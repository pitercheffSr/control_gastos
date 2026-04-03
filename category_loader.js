/**
 * category_loader.js
 * 
 * Proporciona una función reutilizable para inicializar un conjunto de tres
 * <select> en cascada para categorías, subcategorías y sub-subcategorías.
 */

/**
 * Inicializa un conjunto de tres <select> en cascada.
 * Carga automáticamente las categorías de primer nivel y configura los
 * eventos 'onchange' para cargar los niveles inferiores de forma dinámica.
 * 
 * @param {object} selectors - Un objeto que contiene los tres elementos <select>.
 * @param {HTMLSelectElement} selectors.cat - El <select> para la categoría principal.
 * @param {HTMLSelectElement} selectors.subcat - El <select> para la subcategoría.
 * @param {HTMLSelectElement} selectors.subsubcat - El <select> para la sub-subcategoría.
 */
async function initializeCascadingCategories({ cat, subcat, subsubcat }) {
    if (!cat || !subcat || !subsubcat) {
        console.error("Se requieren los tres elementos <select> para las categorías en cascada.");
        return;
    }

    const populateSelect = async (select, url, placeholder) => {
        try {
            const resp = await fetch(url);
            const items = await resp.json();
            select.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                select.innerHTML += `<option value="${item.id}">${item.nombre}</option>`;
            });
        } catch (err) {
            console.error(`Error cargando datos para el select #${select.id}:`, err);
            select.innerHTML = "<option value=''>Error al cargar</option>";
        }
    };

    cat.onchange = async () => {
        const parentId = cat.value;
        subcat.innerHTML = "<option value=''>—</option>";
        subsubcat.innerHTML = "<option value=''>—</option>";
        if (parentId) await populateSelect(subcat, `load_categorias.php?nivel=nivel2&padre=${parentId}`, '— Seleccione —');
    };

    subcat.onchange = async () => {
        const parentId = subcat.value;
        subsubcat.innerHTML = "<option value=''>—</option>";
        if (parentId) await populateSelect(subsubcat, `load_categorias.php?nivel=nivel3&padre=${parentId}`, '— Seleccione —');
    };

    await populateSelect(cat, "load_categorias.php?nivel=nivel1", '— Seleccione —');
}