<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$uid = $_SESSION['usuario_id'];
$error = '';

// --- FUNCIONES ---

function obtenerInfoRaiz($pdo, $catId) {
    $currentId = $catId;
    while(true) {
        $stmt = $pdo->prepare("SELECT id, parent_id, tipo_fijo FROM categorias WHERE id = ?");
        $stmt->execute([$currentId]);
        $cat = $stmt->fetch();
        if (!$cat) return null;
        if ($cat['parent_id'] === NULL) return $cat;
        $currentId = $cat['parent_id'];
    }
}

function obtenerArbolCompleto($pdo, $uid, $parentId) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? AND parent_id = ? ORDER BY nombre ASC");
    $stmt->execute([$uid, $parentId]);
    $hijos = $stmt->fetchAll();
    foreach ($hijos as &$hijo) {
        $hijo['subcategorias'] = obtenerArbolCompleto($pdo, $uid, $hijo['id']);
    }
    return $hijos;
}

function renderizarLista($categorias) {
    echo '<ul class="space-y-1 mt-1 ml-4 border-l-2 border-indigo-100 pl-4 subcategoria-lista hidden">';
    foreach ($categorias as $cat) {
        $tieneHijos = !empty($cat['subcategorias']);
        echo '<li class="group py-1">';
        $onclick = $tieneHijos ? 'onclick="toggleSubcategorias(this)"' : '';
        $cursor = $tieneHijos ? 'cursor-pointer hover:bg-indigo-50' : 'hover:bg-gray-50';
        
        echo '<div class="flex items-center justify-between py-1 border-b border-gray-50 border-dashed transition rounded px-2 ' . $cursor . '" ' . $onclick . '>';
            echo '<span class="text-gray-700 text-sm flex items-center gap-2">';
            if ($tieneHijos) {
                echo '<span class="text-indigo-400 transition-transform duration-200 transform -rotate-90 text-xs icono-flecha">▼</span>';
            } else {
                echo '<span class="text-indigo-200 text-xs">↳</span>';
            }
            echo htmlspecialchars($cat['nombre']);
            echo '</span>';
            
            echo '<div class="opacity-0 group-hover:opacity-100 transition-opacity space-x-3">';
                echo '<button onclick="event.stopPropagation(); abrirModal('.$cat['id'].', \''.htmlspecialchars(addslashes($cat['nombre'])).'\')" class="text-indigo-600 text-xs font-bold hover:underline">Sub</button>';
                echo '<a href="?borrar='.$cat['id'].'" onclick="event.stopPropagation(); return confirm(\'¿Borrar '.htmlspecialchars(addslashes($cat['nombre'])).'?\')" class="text-red-500 text-xs font-bold hover:underline">Eliminar</a>';
            echo '</div>';
        echo '</div>';
        
        if ($tieneHijos) renderizarLista($cat['subcategorias']);
        echo '</li>';
    }
    echo '</ul>';
}

// --- LÓGICA PRINCIPAL (CREAR, EDITAR, BORRAR) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Crear Subcategoría
    if ($action == 'crear') {
        $nombre = trim($_POST['nombre']);
        $parent_id = $_POST['parent_id'];
        if (!empty($nombre)) {
            $infoRaiz = obtenerInfoRaiz($pdo, $parent_id);
            $tipoHeredado = ($infoRaiz && isset($infoRaiz['tipo_fijo'])) ? $infoRaiz['tipo_fijo'] : 'personalizado';
            $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nombre, parent_id, tipo_fijo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uid, $nombre, $parent_id, $tipoHeredado]);
            header("Location: categorias.php?open=" . ($infoRaiz['id'] ?? ''));
            exit;
        }
    }
    
    // Crear Categoría Principal
    if ($action == 'crear_principal') {
        $nombre = trim($_POST['nombre']);
        $tipo_fijo = $_POST['tipo_fijo'];
        $color = $_POST['color'];
        if (!empty($nombre)) {
            $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nombre, parent_id, tipo_fijo, color) VALUES (?, ?, NULL, ?, ?)");
            $stmt->execute([$uid, $nombre, $tipo_fijo, $color]);
            header("Location: categorias.php");
            exit;
        }
    }

    // Editar Categoría Principal
    if ($action == 'editar_principal') {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $color = $_POST['color'];
        if (!empty($nombre)) {
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, color = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nombre, $color, $id, $uid]);
            header("Location: categorias.php");
            exit;
        }
    }
}

// Borrar Categoría (Subcategoría o Principal)
if (isset($_GET['borrar'])) {
    $catId = $_GET['borrar'];
    
    // Borrado en Cascada: Primero borramos sus hijos
    $stmtDelHijos = $pdo->prepare("DELETE FROM categorias WHERE parent_id = ? AND usuario_id = ?");
    $stmtDelHijos->execute([$catId, $uid]);
    
    // Luego borramos la categoría seleccionada
    $stmtDel = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmtDel->execute([$catId, $uid]);
    
    header("Location: categorias.php");
    exit;
}

$stmtPadres = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? AND parent_id IS NULL ORDER BY id ASC");
$stmtPadres->execute([$uid]);
$padres = $stmtPadres->fetchAll();

foreach ($padres as &$p) {
    $p['subcategorias'] = obtenerArbolCompleto($pdo, $uid, $p['id']);
}

include 'includes/header.php';
$openId = $_GET['open'] ?? null;
?>

<div class="container mx-auto p-6 max-w-4xl min-h-screen pb-24 overflow-y-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">Árbol de Categorías</h1>
            <p class="text-sm text-gray-500 mt-1">Gestiona tus fuentes de ingresos y gastos.</p>
        </div>
        <button onclick="abrirModalPrincipal()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
            + Nueva Categoría Principal
        </button>
    </div>

    <div class="grid gap-4">
        <?php foreach($padres as $index => $padre): 
            $estaAbierto = ($openId) ? ($padre['id'] == $openId) : ($index == 0);
            
            // Detectamos si es una de las 4 categorías intocables del sistema
            $esFija = in_array($padre['tipo_fijo'], ['necesidad', 'deseo', 'ahorro', 'ingreso']);
        ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition duration-300">
                <div class="p-4 flex flex-col md:flex-row justify-between items-start md:items-center border-l-4 cursor-pointer hover:bg-gray-50 transition gap-4" 
                     style="border-color: <?= $padre['color'] ?? '#6366f1' ?>; background-color: <?= $estaAbierto ? '#f8fafc' : 'white' ?>;"
                     onclick="togglePadre('padre-<?= $padre['id'] ?>', this)">
                    
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 transition-transform duration-200 transform <?= $estaAbierto ? '' : '-rotate-90' ?> icono-flecha-padre">▼</span>
                        <span class="text-lg font-bold" style="color: <?= $padre['color'] ?? '#4f46e5' ?>">
                            <?= htmlspecialchars($padre['nombre']) ?>
                        </span>
                        <span class="hidden md:inline-block px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full border uppercase font-bold tracking-wider">
                            <?= htmlspecialchars($padre['tipo_fijo'] ?? 'General') ?>
                        </span>
                        <?php if($esFija): ?>
                            <span class="text-xs text-indigo-400 font-semibold italic bg-indigo-50 px-2 rounded">Sistema</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                        <?php if(!$esFija): ?>
                            <button onclick="event.stopPropagation(); abrirModalEditar(<?= $padre['id'] ?>, '<?= htmlspecialchars(addslashes($padre['nombre'])) ?>', '<?= $padre['color'] ?>')" class="text-sm text-gray-500 hover:text-indigo-600 font-semibold px-2">Editar</button>
                            <a href="?borrar=<?= $padre['id'] ?>" onclick="event.stopPropagation(); return confirm('ATENCIÓN: ¿Borrar esta categoría principal y TODAS las subcategorías que contiene?')" class="text-sm text-red-400 hover:text-red-600 font-semibold px-2 border-r pr-4">Borrar</a>
                        <?php endif; ?>
                        
                        <button onclick="event.stopPropagation(); abrirModal(<?= $padre['id'] ?>, '<?= htmlspecialchars(addslashes($padre['nombre'])) ?>')" 
                                class="bg-white text-indigo-600 hover:bg-indigo-50 px-3 py-1.5 rounded-lg text-sm font-semibold transition shadow-sm border border-gray-200 ml-2">
                            + Subcategoría
                        </button>
                    </div>
                </div>

                <div id="padre-<?= $padre['id'] ?>" class="p-4 bg-white <?= $estaAbierto ? 'block' : 'hidden' ?>">
                    <?php if(empty($padre['subcategorias'])): ?>
                        <p class="text-gray-400 text-sm italic text-center py-2">No hay subcategorías registradas.</p>
                    <?php else: ?>
                        <div class="mostrar-primer-nivel">
                            <?php renderizarLista($padre['subcategorias']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="modalCrearCategoria" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Nueva Subcategoría</h3>
        <form method="POST">
            <input type="hidden" name="action" value="crear">
            <input type="hidden" name="parent_id" id="modalParentId">
            <p class="text-sm text-gray-500 mb-6 pb-4 border-b">Añadiendo en: <strong id="modalParentName" class="text-indigo-600"></strong></p>
            <input type="text" name="nombre" class="w-full border rounded-lg p-3 mb-4 outline-none focus:ring-2 focus:ring-indigo-200" required>
            <div class="flex justify-end gap-3"><button type="button" onclick="window.closeModal()" class="px-5 py-2 text-gray-600">Cancelar</button><button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Guardar</button></div>
        </form>
    </div>
</div>

<div id="modalCrearPrincipal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <h3 class="text-2xl font-bold text-indigo-700 mb-4">Nueva Sección Principal</h3>
        <form method="POST">
            <input type="hidden" name="action" value="crear_principal">
            <label class="block text-sm font-bold mb-2">Nombre</label>
            <input type="text" name="nombre" class="w-full border rounded-lg p-3 mb-4" required>
            <label class="block text-sm font-bold mb-2">Tipo</label>
            <select name="tipo_fijo" class="w-full border rounded-lg p-3 mb-4">
                <option value="ingreso">Ingreso (Suma dinero)</option>
                <option value="necesidad">Necesidad (Gasto 50%)</option>
                <option value="deseo">Deseo (Gasto 30%)</option>
                <option value="ahorro">Ahorro (Gasto 20%)</option>
                <option value="personalizado">Otro tipo</option>
            </select>
            <label class="block text-sm font-bold mb-2">Color</label>
            <input type="color" name="color" value="#6366f1" class="w-full h-12 rounded border-0 mb-6">
            <div class="flex justify-end gap-3"><button type="button" onclick="window.closeModal()" class="px-5 py-2 text-gray-600">Cancelar</button><button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Crear</button></div>
        </form>
    </div>
</div>

<div id="modalEditarPrincipal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Editar Sección</h3>
        <form method="POST">
            <input type="hidden" name="action" value="editar_principal">
            <input type="hidden" name="id" id="editPrincipalId">
            <label class="block text-sm font-bold mb-2">Nombre</label>
            <input type="text" name="nombre" id="editPrincipalNombre" class="w-full border rounded-lg p-3 mb-4" required>
            <label class="block text-sm font-bold mb-2">Color</label>
            <input type="color" name="color" id="editPrincipalColor" class="w-full h-12 rounded border-0 mb-6">
            <div class="flex justify-end gap-3"><button type="button" onclick="window.closeModal()" class="px-5 py-2 text-gray-600">Cancelar</button><button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg">Actualizar</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { document.querySelectorAll('.mostrar-primer-nivel > ul').forEach(ul => ul.classList.remove('hidden')); });

function togglePadre(id, header) {
    const cont = document.getElementById(id), flecha = header.querySelector('.icono-flecha-padre');
    if (cont.classList.contains('hidden')) { cont.classList.remove('hidden'); flecha.classList.remove('-rotate-90'); header.style.backgroundColor = '#f8fafc'; } 
    else { cont.classList.add('hidden'); flecha.classList.add('-rotate-90'); header.style.backgroundColor = 'white'; }
}

function toggleSubcategorias(el) {
    const ul = el.nextElementSibling, flecha = el.querySelector('.icono-flecha');
    if (ul && ul.tagName === 'UL') {
        if (ul.classList.contains('hidden')) { ul.classList.remove('hidden'); if(flecha) flecha.classList.remove('-rotate-90'); } 
        else { ul.classList.add('hidden'); if(flecha) flecha.classList.add('-rotate-90'); }
    }
}

function abrirModal(id, nombre) { document.getElementById('modalParentId').value = id; document.getElementById('modalParentName').textContent = nombre; document.getElementById('modalCrearCategoria').classList.remove('hidden'); }
function abrirModalPrincipal() { document.getElementById('modalCrearPrincipal').classList.remove('hidden'); }
function abrirModalEditar(id, nombre, color) {
    document.getElementById('editPrincipalId').value = id;
    document.getElementById('editPrincipalNombre').value = nombre;
    document.getElementById('editPrincipalColor').value = color;
    document.getElementById('modalEditarPrincipal').classList.remove('hidden');
}

window.closeModal = function() {
    ['modalCrearCategoria', 'modalCrearPrincipal', 'modalEditarPrincipal'].forEach(id => document.getElementById(id).classList.add('hidden'));
}
</script>

<?php include 'includes/footer.php'; ?>