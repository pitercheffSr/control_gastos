document.addEventListener('DOMContentLoaded', function() {
    const categoriaSelect = document.getElementById('id_categoria');
    const subcategoriaSelect = document.getElementById('subcategoria');
    const subsubcategoriaSelect = document.getElementById('subsubcategoria');
    const subcatContainer = document.getElementById('subcategoria-container');
    const subsubcatContainer = document.getElementById('subsubcategoria-container');

    function cargarSubcategorias() {
        const categoriaId = categoriaSelect.value;
        subcategoriaSelect.innerHTML = '<option value="">Seleccione una subcategoría</option>';
        subsubcategoriaSelect.innerHTML = '<option value="">Seleccione una sub-subcategoría</option>';
        subsubcatContainer.style.display = 'none';
        if (categoriaId) {
            fetch(`obtener_subcategorias.php?id_categoria=${categoriaId}&nivel=1`)
                .then(response => response.json())
                .then(subcategorias => {
                    if (subcategorias.length > 0) {
                        subcatContainer.style.display = 'block';
                        subcategorias.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.nombre;
                            subcategoriaSelect.appendChild(option);
                        });
                    } else {
                        subcatContainer.style.display = 'none';
                    }
                });
        } else {
            subcatContainer.style.display = 'none';
        }
    }

    function cargarSubsubcategorias() {
        const subcatId = subcategoriaSelect.value;
        subsubcategoriaSelect.innerHTML = '<option value="">Seleccione una sub-subcategoría</option>';
        if (subcatId) {
            fetch(`obtener_subcategorias.php?parent_id=${subcatId}&nivel=2`)
                .then(response => response.json())
                .then(subsubs => {
                    if (subsubs.length > 0) {
                        subsubcatContainer.style.display = 'block';
                        subsubs.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id;
                            option.textContent = sub.nombre;
                            subsubcategoriaSelect.appendChild(option);
                        });
                    } else {
                        subsubcatContainer.style.display = 'none';
                    }
                });
        } else {
            subsubcatContainer.style.display = 'none';
        }
    }

    categoriaSelect.addEventListener('change', cargarSubcategorias);
    subcategoriaSelect.addEventListener('change', cargarSubsubcategorias);
    if (categoriaSelect.value) {
        cargarSubcategorias();
    }
});