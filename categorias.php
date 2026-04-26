<?php
require_once 'config.php';
require_once 'models/CategoriaModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['usuario_id'];
$model = new CategoriaModel($pdo);
$categorias = $model->getAll($uid);

// Función que se llama a sí misma para dibujar infinitas subcategorías
function renderCategoriaSortable($c, $categoriasPorPadre) {
    $esSistema = is_null($c['usuario_id']);
    $id = $c['id'];
    $tieneHijos = isset($categoriasPorPadre[$id]);
    // Ocultar texto entre paréntesis para la interfaz visual (si queda vacío, usa el original)
    $nombreVisual = trim(preg_replace('/\s*\(.*?\)/', '', $c['nombre'])) ?: $c['nombre'];
    ?>
    <li data-id="<?= $id ?>" class="list-group-item bg-white rounded-lg shadow-sm border">
        <div class="p-3 flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <span class="drag-handle cursor-move text-gray-400 hover:text-gray-800" title="Arrastrar para reordenar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </span>
                <!-- Wrapper for alignment and toggle button -->
                <div class="w-7 h-7 flex items-center justify-center">
                    <?php if ($tieneHijos): ?>
                    <button type="button" class="category-toggle-btn text-gray-400 hover:text-indigo-600 p-1 rounded-full">
                        <svg class="w-5 h-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <span class="font-bold text-gray-800" title="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($nombreVisual) ?></span>
                <span class="px-2 py-0.5 <?= $c['tipo_fijo'] === 'ingreso' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-md text-xs font-bold uppercase"><?= htmlspecialchars($c['tipo_fijo']) ?></span>
                <?php if($esSistema): ?>
                    <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded">Sistema</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-4">
                <span class="category-total text-base font-bold text-gray-700 w-28 text-right"></span>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="abrirModalCategoria(null, '', '<?= $c['tipo_fijo'] ?>', <?= $id ?>)" class="text-gray-400 hover:text-green-600 p-1.5 rounded-full hover:bg-green-50 transition" title="Añadir Subcategoría">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    <?php if(!$esSistema): ?>
                        <button onclick="abrirModalCategoria(<?= $id ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>', '<?= $c['tipo_fijo'] ?>', '<?= $c['parent_id'] ?: '' ?>')" class="text-gray-400 hover:text-indigo-600 p-1.5 rounded-full hover:bg-indigo-50 transition" title="Editar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button onclick="eliminarCategoria(<?= $id ?>)" class="text-gray-400 hover:text-red-500 p-1.5 rounded-full hover:bg-red-50 transition" title="Eliminar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($tieneHijos): ?>
            <ul class="list-group pl-10 pt-2 space-y-2 hidden" data-parent-id="<?= $id ?>">
                <?php foreach ($categoriasPorPadre[$id] as $hijo) {
                    renderCategoriaSortable($hijo, $categoriasPorPadre);
                } ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}

// Magia: Organizamos la lista plana en un "Árbol Genealógico"
$categoriasPorPadre = [];
$todasLasCategorias = [];
if (is_array($categorias)) {
    foreach ($categorias as $c) {
        $pid = $c['parent_id'] ?: 0;
        $categoriasPorPadre[$pid][] = $c;
        $todasLasCategorias[] = $c;
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

    <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-wrap items-center gap-4 mb-8">
        <span class="text-sm font-bold text-gray-600 pl-2">Mostrar totales de gastos para el periodo:</span>
        <div class="flex items-center gap-2">
            <label for="totalsStartDate" class="text-sm font-medium text-gray-600">Desde:</label>
            <input type="date" id="totalsStartDate" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
        </div>
        <div class="flex items-center gap-2">
            <label for="totalsEndDate" class="text-sm font-medium text-gray-600">Hasta:</label>
            <input type="date" id="totalsEndDate" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <ul id="category-list-root" class="list-group space-y-2" data-parent-id="">
            <?php
            if (isset($categoriasPorPadre[0])) {
                foreach($categoriasPorPadre[0] as $raiz) {
                    renderCategoriaSortable($raiz, $categoriasPorPadre);
                }
            } else {
                echo '<li class="p-8 text-center text-gray-400">No hay categorías. Crea una para empezar.</li>';
            }
            ?>
        </ul>
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
                    <?php foreach($todasLasCategorias as $c):
                        $nombreVisualOpt = trim(preg_replace('/\s*\(.*?\)/', '', $c['nombre'])) ?: $c['nombre'];
                    ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($nombreVisualOpt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Tipo Contable</label>
                <select id="cat_tipo" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition bg-white cursor-pointer">
                    <option value="gasto">Gasto (Resta en cuentas y suma en informes)</option>
                    <option value="ingreso">Ingreso (Suma en cuentas)</option>
                    <option value="ahorro">Ahorro (Resta en cuenta, suma en Meta 20%)</option>
                    <option value="puente">Puente / Traspaso (Movimiento invisible)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalCategoria()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<!-- JS Externo -->
<script src="assets/js/categorias.js"></script>

<?php include 'includes/footer.php'; ?>
