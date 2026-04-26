<?php
require_once 'config.php';
require_once 'models/TransaccionModel.php';
require_once 'models/CategoriaModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['usuario_id'];

// Obtener día de inicio para el mes contable
$stmtUser = $pdo->prepare("SELECT dia_inicio_mes FROM usuarios WHERE id = ?");
$stmtUser->execute([$uid]);
$uData = $stmtUser->fetch();
$dia_inicio = $uData ? (int)$uData['dia_inicio_mes'] : 1;

// Obtener meses disponibles para el selector
$stmtMeses = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as mes_val FROM transacciones WHERE usuario_id = ? ORDER BY mes_val DESC");
$stmtMeses->execute([$uid]);
$mesesDisponibles = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

$nombresMeses = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$catModel = new CategoriaModel($pdo);
$categoriasRaw = $catModel->getAll($uid);

// Medida de seguridad: Si la consulta de categorías falla, nos aseguramos de que
// $categoriasRaw sea un array vacío para evitar un error fatal en los bucles.
if (!is_array($categoriasRaw)) {
    $categoriasRaw = [];
}

// --- INICIO: Lógica para el filtro de categorías ---
// Organizamos la lista plana en un "Árbol Genealógico" para el <select>
$categoriasPorPadre = [];
foreach ($categoriasRaw as $c) {
    $pid = $c['parent_id'] ?: 0;
    $categoriasPorPadre[$pid][] = $c;
}

// Función que se llama a sí misma para dibujar las opciones del select con anidación
function renderizarOpcionesCategoria($categoriasPorPadre, $parentId = 0, $nivel = 0) {
    // Se añade una comprobación de profundidad para evitar bucles infinitos si hay datos corruptos.
    if (!isset($categoriasPorPadre[$parentId]) || $nivel > 10) {
        return '';
    }

    $html = '';
    foreach ($categoriasPorPadre[$parentId] as $categoria) {
        $prefijo = str_repeat('&nbsp;&nbsp;&nbsp;', $nivel);
        if ($nivel > 0) $prefijo .= '↳ ';
        $html .= "<option value=\"{$categoria['id']}\">{$prefijo}" . htmlspecialchars($categoria['nombre']) . "</option>";
        $html .= renderizarOpcionesCategoria($categoriasPorPadre, $categoria['id'], $nivel + 1);
    }
    return $html;
}

function renderCategoriaArbolDragDrop($categoriasPorPadre, $parentId = 0, $nivel = 0) {
    // Se añade una comprobación de profundidad para evitar bucles infinitos.
    if (!isset($categoriasPorPadre[$parentId]) || $nivel > 10) {
        return '';
    }

    $html = '<ul class="space-y-1 ' . ($nivel > 0 ? 'pl-4' : '') . '">';
    foreach ($categoriasPorPadre[$parentId] as $categoria) {
        $html .= '<li class="droppable-category rounded-lg transition-colors" data-category-id="' . $categoria['id'] . '">';
        $html .= '<div class="p-2.5 text-sm font-medium text-gray-700 hover:bg-indigo-50 rounded-lg cursor-pointer">' . htmlspecialchars($categoria['nombre']) . '</div>';
        // Recursive call
        $html .= renderCategoriaArbolDragDrop($categoriasPorPadre, $categoria['id'], $nivel + 1);
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}
$opcionesCategoriaParaFiltroHtml = '<option value="">Todas</option><option value="unclassified" class="font-bold text-orange-600">⚠️ Sin clasificar</option>' . renderizarOpcionesCategoria($categoriasPorPadre);
$opcionesCategoriaParaEdicionHtml = '<option value="">-- Por clasificar --</option>' . renderizarOpcionesCategoria($categoriasPorPadre);
// --- FIN: Lógica para el filtro de categorías ---

$catIngresos = [];
$catGastos = [];
foreach($categoriasRaw as $c) {
    if ($c['tipo_fijo'] === 'ingreso') {
        $catIngresos[] = $c;
    } else {
        $catGastos[] = $c;
    }
}

include 'includes/header.php';
?>

<!-- CSS Personalizado -->
<link rel="stylesheet" href="assets/css/transacciones.css">

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Movimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Historial completo de tus finanzas.</p>
        </div>
        <div class="flex items-center gap-4">
            <button id="btnAutoClasificar" class="bg-purple-600 text-white px-4 py-2.5 rounded-xl shadow-md hover:bg-purple-700 font-bold transition flex items-center gap-2 text-sm" title="Aplica tus reglas para clasificar automáticamente los movimientos sin categoría">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" /></svg>
                Auto-Clasificar
            </button>
            <a href="views/importar.php" class="bg-green-600 text-white px-4 py-2.5 rounded-xl shadow-md hover:bg-green-700 font-bold transition flex items-center gap-2 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                Importar
            </a>
            <button id="btnExportarCSV" class="bg-gray-600 text-white px-4 py-2.5 rounded-xl shadow-md hover:bg-gray-700 font-bold transition flex items-center gap-2 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                Exportar
            </a>
            <!-- Este botón es detectado por transacciones_editar.js para abrir el panel lateral -->
            <button id="btnNuevaTransaccion" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nuevo Movimiento
            </button>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-12 lg:gap-8">
        <!-- Panel de Categorías -->
        <div class="lg:col-span-3 mb-8 lg:mb-0">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 sticky top-24">
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-3">Arrastra un movimiento aquí</h2>
                <div id="category-drop-zone" class="max-h-[65vh] overflow-y-auto pr-2">
                    <div class="droppable-category rounded-lg transition-colors" data-category-id="">
                        <div class="p-2.5 text-sm font-medium text-gray-500 italic hover:bg-gray-100 rounded-lg cursor-pointer">↳ Por clasificar</div>
                    </div>
                    <?php echo renderCategoriaArbolDragDrop($categoriasPorPadre); ?>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-9">
            <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-wrap items-center gap-4 mb-8">
                <div class="flex items-center gap-2 border-r border-gray-100 pr-4">
                    <span class="text-sm font-bold text-gray-600 pl-2">Mes:</span>
                    <select id="filtroMes" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
                        <option value="">Todos los meses</option>
                        <?php foreach($mesesDisponibles as $m):
                            $partes = explode('-', $m['mes_val']);
                            $nombreMostrar = $nombresMeses[$partes[1]] . ' ' . $partes[0];
                        ?>
                            <option value="<?= $m['mes_val'] ?>"><?= $nombreMostrar ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-600">Desde:</span>
                    <input type="date" id="filtroInicio" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-gray-600">Hasta:</span>
                    <input type="date" id="filtroFin" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
                </div>

                <div class="flex items-center gap-2 border-l border-gray-200 pl-4 ml-2">
                    <span class="text-sm font-bold text-gray-600">Categoría:</span>
                    <select id="filtroCategoria" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none w-48">
                        <?php echo $opcionesCategoriaParaFiltroHtml; ?>
                    </select>
                </div>

                <div class="flex items-center gap-2 border-l border-gray-200 pl-4 ml-2">
                    <span class="text-sm font-bold text-gray-600">Ordenar:</span>
                    <select id="filtroOrden" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
                        <option value="fecha-DESC">Más recientes</option>
                        <option value="fecha-ASC">Más antiguos</option>
                        <option value="importe-DESC">Mayor importe</option>
                        <option value="importe-ASC">Menor importe</option>
                        <option value="descripcion-ASC">Descripción (A-Z)</option>
                        <option value="descripcion-DESC">Descripción (Z-A)</option>
                        <option value="categoria_nombre-ASC">Categoría (A-Z)</option>
                        <option value="categoria_nombre-DESC">Categoría (Z-A)</option>
                    </select>
                </div>

                <div class="flex-grow flex items-center gap-2 border-l border-gray-200 pl-4 ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    <input type="text" id="filtroTexto" placeholder="Buscar por descripción..." class="w-full border-none focus:ring-0 bg-transparent outline-none text-sm p-0">
                </div>

                <button onclick="limpiarFiltros()" class="text-gray-500 hover:text-indigo-600 font-bold px-4 py-1.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition border border-gray-100 ml-auto md:ml-0">
                    Limpiar Filtros
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="p-4 w-4">
                                    <input type="checkbox" id="selectAllCheckbox" title="Seleccionar todo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                                </th>
                                <th data-sort="fecha" class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs cursor-pointer hover:bg-gray-100 transition-colors">Fecha</th>
                                <th data-sort="descripcion" class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs cursor-pointer hover:bg-gray-100 transition-colors">Descripción</th>
                                <th data-sort="categoria_nombre" class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs cursor-pointer hover:bg-gray-100 transition-colors">Categoría</th>
                                <th data-sort="importe" class="p-4 text-right text-gray-500 font-bold tracking-wider uppercase text-xs cursor-pointer hover:bg-gray-100 transition-colors">Importe</th>
                                <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMovimientos" class="divide-y divide-gray-100">
                            <!-- REFACTOR: El contenido de la tabla ahora se carga dinámicamente con JavaScript -->
                            <tr id="filaCargando">
                                <td colspan="6" class="p-8 text-center text-gray-400">Cargando movimientos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contenedor para la paginación -->
            <div id="paginacionContenedor" class="flex justify-between items-center mt-4 px-4 py-2"></div>
        </div>
    </div>
</div>

<!-- Barra de acciones masivas -->
<div id="bulk-actions-bar" class="hidden fixed bottom-0 left-0 right-0 bg-gray-800/95 backdrop-blur-sm text-white p-4 shadow-lg transform translate-y-full transition-transform duration-300 ease-in-out z-30">
    <div class="container mx-auto max-w-6xl flex justify-between items-center gap-6">
        <div class="font-bold">
            <span id="selection-count">0</span> seleccionados
        </div>
        <div class="flex items-center gap-6">
            <button id="btnAutoClasificarSeleccionados" class="bg-purple-600 hover:bg-purple-700 px-4 py-2.5 rounded-lg font-bold text-sm transition flex items-center gap-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" /></svg>
                Auto-Clasificar
            </button>
            <div class="flex items-center gap-2 border-l border-gray-600 pl-6">
                <label for="bulk-category-select" class="text-sm font-medium">Cambiar categoría a:</label>
                <select id="bulk-category-select" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                    <?php echo $opcionesCategoriaParaEdicionHtml; ?>
                </select>
                <button id="btnAplicarCategoria" class="bg-indigo-500 hover:bg-indigo-600 px-4 py-2.5 rounded-lg font-bold text-sm transition">Aplicar</button>
            </div>
            <button id="btnEliminarSeleccionados" class="bg-red-600 hover:bg-red-700 px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition shadow-md ml-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Eliminar
            </button>
        </div>
    </div>
</div>

<!-- Panel Lateral para Editar/Crear Transacción -->
<div id="overlayPanel" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 hidden transition-opacity"></div>
<div id="panelEditar" class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-40 flex flex-col">
    <div id="panelHeader" class="flex justify-between items-center p-6 border-b border-gray-200 bg-gray-50 transition-colors">
        <h2 id="panelTitulo" class="text-xl font-bold text-gray-800">Editar Transacción</h2>
        <button id="cerrarPanel" class="text-gray-500 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="flex-grow p-6 overflow-y-auto">
        <form id="formEditar" class="space-y-6">
            <div>
                <label for="e_fecha" class="block text-sm font-bold text-gray-700 mb-2">Fecha</label>
                <input type="date" id="e_fecha" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label for="e_desc" class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                <input type="text" id="e_desc" placeholder="Ej: Compra en supermercado" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="e_monto" class="block text-sm font-bold text-gray-700 mb-2">Importe</label>
                    <input type="number" step="0.01" id="e_monto" placeholder="25.50" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label for="e_tipo" class="block text-sm font-bold text-gray-700 mb-2">Tipo</label>
                    <select id="e_tipo" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="gasto">Gasto</option>
                        <option value="ingreso">Ingreso</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Categoría Raíz</label>
                <select id="e_cat" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white"></select>
            </div>
            <div>
                <label for="e_subcat" class="block text-sm font-bold text-gray-700 mb-2">Subcategoría</label>
                <select id="e_subcat" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white"><option value="">—</option></select>
            </div>
            <div>
                <label for="e_subsub" class="block text-sm font-bold text-gray-700 mb-2">Detalle</label>
                <select id="e_subsub" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white"><option value="">—</option></select>
            </div>
        </form>
    </div>
    <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-4">
        <button id="cancelarEdicion" class="px-5 py-2.5 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition border border-gray-200">Cancelar</button>
        <button id="guardarCambios" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md transition">Guardar Cambios</button>
    </div>
</div>

<script>
const opcionesCategoriaHTML = <?= json_encode($opcionesCategoriaParaEdicionHtml) ?>;
const DIA_INICIO = <?= $dia_inicio ?>;

// Declaración explícita del token CSRF en la ventana global para que JS lo capture
window.csrf_token = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
</script>

<!-- Carga los scripts de la página principal -->
<script src="assets/js/transacciones.js"></script>
<!-- Carga el script que controla el panel lateral de edición/creación -->
<script src="assets/js/transacciones_editar.js"></script>
<?php include 'includes/footer.php'; ?>
