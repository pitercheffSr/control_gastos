<?php 
require_once 'config.php';
require_once 'models/CategoriaModel.php';

// La sesión ya se inicia y se comprueba en config.php
if (!isset($_SESSION['usuario_id'])) { redirect('index.php'); }

$model = new CategoriaModel($pdo);
$categorias = $model->getAllTree($_SESSION['usuario_id']);
$categoriasLista = $model->getAll($_SESSION['usuario_id']);

// FUNCIÓN MÁGICA: Mezcla cualquier color HEX con blanco para hacerlo Pastel
function mezclarColorPastel($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Lo mezclamos con un 60% de blanco puro para suavizarlo
    $r = (int)(($r + 255 + 255) / 3);
    $g = (int)(($g + 255 + 255) / 3);
    $b = (int)(($b + 255 + 255) / 3);
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

include 'includes/header.php'; 
?>

<div class="container mx-auto p-6 max-w-5xl">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Mis Categorías</h1>
            <p class="text-gray-500 mt-1">Organiza tu presupuesto en niveles. El color se hereda automáticamente.</p>
        </div>
        <button onclick="nuevaCategoria()" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 transition font-bold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Añadir Principal
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
        <div id="treeContainer" class="space-y-2">
            <?php
            // Modificamos el renderizador para que pase el color de generación en generación
            function renderTree($nodes, $colorHeredado = null) {
                foreach ($nodes as $node) {
                    $hasChildren = !empty($node['children']);
                    
                    // Si el padre le pasa un color, generamos su versión pastel. Si es raíz, usa su propio color.
                    $colorFinal = $colorHeredado ? mezclarColorPastel($colorHeredado) : ($node['color'] ?? '#cbd5e1');

                    echo '<div class="category-item bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden" id="node-'.$node['id'].'">';
                    echo '  <div class="flex items-center justify-between p-4 hover:bg-white transition group">';
                    
                    $clickAction = $hasChildren ? 'onclick="toggleNode('.$node['id'].')"' : '';
                    $cursorClass = $hasChildren ? 'cursor-pointer' : '';
                    
                    echo '    <div class="flex items-center gap-3 flex-grow '.$cursorClass.'" '.$clickAction.'>';
                    if ($hasChildren) {
                        echo '<span class="text-gray-400 transition p-1"><svg id="icon-'.$node['id'].'" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></span>';
                    } else {
                        echo '<div class="w-6"></div>';
                    }
                    // Pintamos la bolita con el color final heredado o propio
                    echo '      <span class="w-3 h-3 rounded-full" style="background-color:'.$colorFinal.'"></span>';
                    echo '      <span class="font-bold text-gray-700 select-none">'.htmlspecialchars($node['nombre']).'</span>';
                    
                    if (!empty($node['tipo_fijo']) && strtolower(trim($node['tipo_fijo'])) !== 'personalizado') {
                        echo '<span class="text-[10px] uppercase font-black bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-md tracking-widest ml-2">'.$node['tipo_fijo'].'</span>';
                    }
                    echo '    </div>';

                    echo '    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">';
                    // Preparamos los datos para pasarlos a JS de forma segura
                    $nodeNombreEscapado = htmlspecialchars($node['nombre'], ENT_QUOTES, 'UTF-8');
                    $nodeJsonEscapado = htmlspecialchars(json_encode($node), ENT_QUOTES, 'UTF-8');

                    echo "      <button onclick='nuevaSubcategoria({$node['id']}, \"{$nodeNombreEscapado}\")' class='p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition' title='Añadir subcategoría'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6v6m0 0v6m0-6h6m-6 0H6'></path></svg></button>";
                    echo "      <button onclick='editarCategoria({$nodeJsonEscapado})' class='p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition' title='Editar'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path></svg></button>";

                    if (empty($node['tipo_fijo']) || !empty($node['parent_id']) || strtolower(trim($node['tipo_fijo'])) === 'personalizado') {
                        echo '      <button onclick="eliminarCategoria('.$node['id'].')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>';
                    }
                    echo '    </div>';
                    echo '  </div>';
                    
                    if ($hasChildren) {
                        echo '  <div id="children-'.$node['id'].'" class="hidden border-t border-gray-100 bg-white ml-8 border-l">';
                        // Pasamos el color actual a los hijos para que sigan aclarándose
                        renderTree($node['children'], $colorFinal);
                        echo '  </div>';
                    }
                    echo '</div>';
                }
            }
            renderTree($categorias);
            ?>
        </div>
    </div>
</div>

<div id="modalCat" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
        <div class="bg-indigo-600 p-6 text-white">
            <h2 id="modalTitle" class="text-2xl font-bold">Categoría</h2>
            <p id="modalSub" class="text-indigo-100 text-sm mt-1">Gestiona la posición en el árbol genealógico.</p>
        </div>
        
        <form id="formCat" class="p-8 space-y-5" autocomplete="off">
            <input type="hidden" id="cat_id" name="id">
            <input type="hidden" id="cat_parent_id" name="parent_id">

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Nombre</label>
                <input type="text" id="cat_nombre" name="nombre" class="w-full border border-gray-300 rounded-xl p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required placeholder="Ej: Supermercado, Hipoteca..." autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Categoría Padre</label>
                <input list="listaPosiblesPadres" id="cat_parent_input" class="w-full border border-gray-300 rounded-xl p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Escribe para buscar o dejar vacío..." autocomplete="off">
                <datalist id="listaPosiblesPadres">
                    <option data-id="" value="Ninguna (Categoría Principal)"></option>
                    <?php foreach($categoriasLista as $c): ?>
                        <option data-id="<?= $c['id'] ?>" value="<?= htmlspecialchars($c['nombre']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div id="bloqueColor">
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Color (Solo principales)</label>
                <input type="color" id="cat_color" name="color" class="w-full h-12 p-1 rounded-xl bg-white border border-gray-300 cursor-pointer">
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                <button type="button" onclick="cerrarModal()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-50 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition transform hover:-translate-y-0.5">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
// LÓGICA DE OCULTACIÓN DEL COLOR: Si eliges un padre, adiós al selector de color.
document.getElementById('cat_parent_input').addEventListener('input', function(e) {
    const list = document.getElementById('listaPosiblesPadres');
    const hiddenId = document.getElementById('cat_parent_id');
    const options = list.querySelectorAll('option');
    
    hiddenId.value = ""; 
    options.forEach(opt => {
        if (opt.value === this.value) hiddenId.value = opt.getAttribute('data-id');
    });

    // Si hay un padre seleccionado, ocultamos el color. Si no, lo mostramos.
    document.getElementById('bloqueColor').style.display = hiddenId.value ? 'none' : 'block';
});

function toggleNode(id) {
    const childDiv = document.getElementById('children-' + id);
    const icon = document.getElementById('icon-' + id);
    if (childDiv.classList.contains('hidden')) {
        childDiv.classList.remove('hidden');
        if(icon) icon.classList.add('rotate-90');
    } else {
        childDiv.classList.add('hidden');
        if(icon) icon.classList.remove('rotate-90');
    }
}

function cerrarModal() { document.getElementById('modalCat').classList.add('hidden'); }
document.addEventListener('keydown', function(event) { if (event.key === "Escape") cerrarModal(); });

function nuevaCategoria() {
    document.getElementById('formCat').reset();
    document.getElementById('cat_id').value = "";
    document.getElementById('cat_parent_id').value = "";
    document.getElementById('cat_parent_input').value = "Ninguna (Categoría Principal)";
    document.getElementById('modalTitle').textContent = "Nueva Principal";
    document.getElementById('bloqueColor').style.display = "block";
    document.getElementById('modalCat').classList.remove('hidden');
}

function nuevaSubcategoria(parentId, parentNombre) {
    document.getElementById('formCat').reset();
    document.getElementById('cat_id').value = "";
    document.getElementById('cat_parent_id').value = parentId;
    document.getElementById('cat_parent_input').value = parentNombre;
    document.getElementById('modalTitle').textContent = "Nueva Subcategoría";
    // Ocultamos el color por defecto al crear subcategoría
    document.getElementById('bloqueColor').style.display = "none";
    document.getElementById('modalCat').classList.remove('hidden');
}

function editarCategoria(node) {
    document.getElementById('formCat').reset();
    document.getElementById('cat_id').value = node.id;
    document.getElementById('cat_nombre').value = node.nombre;
    document.getElementById('cat_color').value = node.color || "#4f46e5";
    document.getElementById('cat_parent_id').value = node.parent_id || "";
    
    const list = document.getElementById('listaPosiblesPadres');
    let parentName = "Ninguna (Categoría Principal)";
    Array.from(list.options).forEach(opt => {
        if (opt.getAttribute('data-id') == node.parent_id) parentName = opt.value;
    });
    document.getElementById('cat_parent_input').value = parentName;
    document.getElementById('modalTitle').textContent = "Editar Categoría";
    
    // Si tiene padre, ocultamos el color.
    if (node.parent_id) {
        document.getElementById('bloqueColor').style.display = "none";
    } else {
        const esFijaDelSistema = node.tipo_fijo && node.tipo_fijo.toLowerCase() !== 'personalizado';
        document.getElementById('bloqueColor').style.display = esFijaDelSistema ? "none" : "block";
    }
    
    document.getElementById('modalCat').classList.remove('hidden');
}

document.getElementById('formCat').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('controllers/CategoriaRouter.php?action=save', {
            method: 'POST', body: JSON.stringify(data), headers: { 'Content-Type': 'application/json' }
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert('Error: ' + (result.error || 'Desconocido'));
    } catch (error) { alert('Error crítico de conexión.'); }
});

function eliminarCategoria(id) {
    if (!confirm('¿Seguro? Se borrarán sus subcategorías y movimientos.')) return;
    fetch('controllers/CategoriaRouter.php?action=delete', {
        method: 'POST', body: JSON.stringify({ id }), headers: { 'Content-Type': 'application/json' }
    }).then(res => res.json()).then(res => {
        if (res.success) location.reload(); else alert('Error: ' + (res.error || ''));
    });
}
</script>

<?php include 'includes/footer.php'; ?>