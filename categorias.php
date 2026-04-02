<?php 
require_once 'config.php';
require_once 'models/CategoriaModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['usuario_id'];
$model = new CategoriaModel($pdo);
$categorias = $model->getAll($uid);

// Magia: Organizamos la lista plana en un "Árbol Genealógico"
$categoriasPorPadre = [];
$todasLasCategorias = []; 
foreach ($categorias as $c) {
    $pid = $c['parent_id'] ?: 0;
    $categoriasPorPadre[$pid][] = $c;
    $todasLasCategorias[] = $c; 
}

// Función que se llama a sí misma para dibujar infinitas subcategorías
function renderizarCategoria($c, $nivel, $categoriasPorPadre) {
    $esSistema = is_null($c['usuario_id']);
    $id = $c['id'];
    $pid = $c['parent_id'] ?: 0;
    $tieneHijos = isset($categoriasPorPadre[$id]);
    
    // Configuración visual matemática según la profundidad
    $paddingLeft = $nivel > 0 ? ($nivel * 2.5 + 1) . 'rem' : '1rem';
    $rowClass = $nivel > 0 ? "hijo-de-{$pid} hidden bg-gray-50/50 border-l-4 border-l-indigo-200" : "hover:bg-gray-50 border-b border-gray-100";
    ?>
    <tr class="transition-all duration-200 <?= $rowClass ?>" id="row-<?= $id ?>">
        <td class="p-4 flex items-center" style="padding-left: <?= $paddingLeft ?>;">
            <?php if($tieneHijos): ?>
                <button onclick="toggleHijos(<?= $id ?>)" id="btn-toggle-<?= $id ?>" class="text-gray-500 hover:text-indigo-600 transition p-1 bg-white rounded shadow-sm border border-gray-200 mr-3">
                    <svg class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            <?php else: ?>
                <span class="w-6 h-6 inline-block mr-3"></span>
            <?php endif; ?>
            
            <?php if($nivel > 0): ?>
                <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5v7a2 2 0 002 2h7m-3-3l3 3-3 3"></path></svg>
            <?php endif; ?>
            
            <span class="<?= $nivel === 0 ? 'font-extrabold text-gray-800' : 'font-medium text-gray-600' ?>"><?= htmlspecialchars($c['nombre']) ?></span>
        </td>
        <td class="p-4">
            <span class="px-2.5 py-1 <?= $c['tipo_fijo'] === 'ingreso' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-md text-xs font-bold uppercase"><?= htmlspecialchars($c['tipo_fijo']) ?></span>
        </td>
        <td class="p-4">
            <?php if($esSistema): ?>
                <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded">Sistema</span>
            <?php else: ?>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Personalizada</span>
            <?php endif; ?>
        </td>
        <td class="p-4 text-center">
            <button onclick="abrirModalCategoria(null, '', '<?= $c['tipo_fijo'] ?>', <?= $id ?>)" class="text-gray-400 hover:text-green-600 mx-1 p-1.5 rounded hover:bg-green-50 transition" title="Añadir Subcategoría">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>

            <?php if(!$esSistema): ?>
                <button onclick="abrirModalCategoria(<?= $id ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>', '<?= $c['tipo_fijo'] ?>', '<?= $pid ?>')" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition" title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </button>
                <button onclick="eliminarCategoria(<?= $id ?>)" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition" title="Eliminar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            <?php else: ?>
                <span class="text-xs text-gray-300 italic px-2">No editable</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    // Si la categoría tiene hijos, la función se vuelve a llamar a sí misma para dibujarlos debajo
    if ($tieneHijos) {
        foreach ($categoriasPorPadre[$id] as $hijo) {
            renderizarCategoria($hijo, $nivel + 1, $categoriasPorPadre);
        }
    }
}

include 'includes/header.php'; 
?>

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Mis Categorías</h1>
            <p class="text-sm text-gray-500 mt-1">Navega y organiza tus clasificaciones de movimientos.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="abrirModalCategoria()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nueva Categoría Principal
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Nombre</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Tipo</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Origen</th>
                        <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    // Disparamos la función maestra pasándole solo las categorías "Raíz" (Las que no tienen padre = 0)
                    if (isset($categoriasPorPadre[0])) {
                        foreach($categoriasPorPadre[0] as $raiz) {
                            renderizarCategoria($raiz, 0, $categoriasPorPadre);
                        }
                    } else {
                        echo '<tr><td colspan="4" class="p-8 text-center text-gray-400">No hay categorías.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalCategoria" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalCategoriaContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 id="modalCatTitle" class="text-2xl font-extrabold mb-6 text-gray-800">Categoría</h2>
        <form id="formCategoria" class="space-y-5">
            <input type="hidden" id="categoria_id">
            
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Nombre</label>
                <input type="text" id="cat_nombre" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Pertenece a (Opcional)</label>
                <select id="cat_parent" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition bg-white cursor-pointer">
                    <option value="">-- Ninguna (Es categoría principal) --</option>
                    <?php foreach($todasLasCategorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Tipo Contable</label>
                <select id="cat_tipo" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition bg-white cursor-pointer">
                    <option value="gasto">Gasto</option>
                    <option value="ingreso">Ingreso</option>
                </select>
            </div>
            
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalCategoria()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Despliega o contrae los hijos y gira la flecha
function toggleHijos(parentId) {
    const rows = document.querySelectorAll('.hijo-de-' + parentId);
    const btn = document.getElementById('btn-toggle-' + parentId).querySelector('svg');
    
    let isHidden = false;
    rows.forEach(row => {
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
            isHidden = true;
        } else {
            row.classList.add('hidden');
            
            // Si ocultamos un padre, tenemos que cerrar sus sub-hijos por seguridad
            const rowId = row.id.split('-')[1]; 
            const childBtn = document.getElementById('btn-toggle-' + rowId);
            if (childBtn && childBtn.querySelector('svg').classList.contains('rotate-90')) {
                toggleHijos(rowId); 
            }
        }
    });
    
    if (isHidden) {
        btn.classList.add('rotate-90');
    } else {
        btn.classList.remove('rotate-90');
    }
}

function abrirModalCategoria(id = null, nombre = '', tipo = 'gasto', parent_id = '') {
    document.getElementById('formCategoria').reset();
    document.getElementById('categoria_id').value = id || '';
    
    const parentSelect = document.getElementById('cat_parent');
    Array.from(parentSelect.options).forEach(opt => opt.disabled = false);

    if(id) {
        document.getElementById('cat_nombre').value = nombre;
        document.getElementById('cat_tipo').value = tipo;
        
        // Evitamos que una categoría sea padre de sí misma
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

document.addEventListener('keydown', (e) => { 
    if(e.key === "Escape") { 
        cerrarModalCategoria(); 
        
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    } 
});

document.getElementById('formCategoria').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        id: document.getElementById('categoria_id').value,
        nombre: document.getElementById('cat_nombre').value,
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
        if(result.success) location.reload();
        else alert("Error: " + (result.error || "Desconocido"));
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
</script>

<?php include 'includes/footer.php'; ?>