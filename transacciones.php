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

<style>
    .drag-over > div {
        background-color: #eef2ff !important; /* bg-indigo-50 */
        border: 2px dashed #6366f1; /* border-indigo-500 */
    }
    /* Diferenciación visual para el panel de edición/creación */
    #panelEditar.mode-new #panelHeader {
        background-color: #e0e7ff; /* bg-indigo-100 */
        border-color: #c7d2fe; /* border-indigo-200 */
    }
    #panelEditar.mode-edit #panelHeader {
        background-color: #fef3c7; /* bg-amber-100 */
        border-color: #fde68a; /* border-amber-200 */
    }
</style>
<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Movimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Historial completo de tus finanzas.</p>
        </div>
        <div class="flex items-center gap-4">
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
            <div class="flex items-center gap-2">
                <label for="bulk-category-select" class="text-sm font-medium">Cambiar categoría a:</label>
                <select id="bulk-category-select" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                    <?php echo $opcionesCategoriaParaEdicionHtml; ?>
                </select>
                <button id="btnAplicarCategoria" class="bg-indigo-500 hover:bg-indigo-600 px-4 py-2.5 rounded-lg font-bold text-sm transition">Aplicar</button>
            </div>
            <button id="btnEliminarSeleccionados" class="bg-red-600 hover:bg-red-700 px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Eliminar Seleccionados
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
// La variable `draggedTransactionId` ya no es necesaria si usamos `dataTransfer`
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
    const maxPagesToShow = 5; // Cuántos números de página mostrar (sin contar los extremos y los '...')
    let startPage = Math.max(1, paginaActual - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPaginas, startPage + maxPagesToShow - 1);

    // Ajustar startPage si endPage está al final pero no se muestran suficientes páginas
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, totalPaginas - maxPagesToShow + 1);
    }

    // Mostrar la primera página y '...' si es necesario
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

    // Mostrar los números de página intermedios
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button data-page="${i}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ${i === paginaActual ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                ${i}
            </button>
        `;
    }

    // Mostrar '...' y la última página si es necesario
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

    // Añadir listeners a los botones de número de página
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
        // Si hay otra fila en edición, la recargamos para cancelar.
        // El usuario tendrá que volver a hacer clic. Es la forma más simple y segura.
        cargarTransacciones();
        return;
    }

    filaEnEdicion = tr;
    tr.classList.add('edit-mode', 'bg-indigo-50');

    const data = JSON.parse(tr.dataset.transaction);
    const cells = tr.querySelectorAll('td');

    // Clases para los inputs
    const inputClasses = "w-full p-2 border border-indigo-300 rounded-md focus:ring-2 focus:ring-indigo-500 outline-none text-sm";

    // 0: Fecha
    cells[0].innerHTML = `<input type="date" class="edit-fecha ${inputClasses}" value="${data.fecha}">`;
    
    // 1: Descripción
    cells[1].innerHTML = `<input type="text" class="edit-descripcion ${inputClasses}" value="${escapeHtml(data.descripcion)}">`;

    // 2: Categoría
    cells[2].innerHTML = `<select class="edit-categoria ${inputClasses}">${opcionesCategoriaHTML}</select>`;
    cells[2].querySelector('select').value = data.categoria_id || "";

    // 3: Importe (con signo)
    cells[3].innerHTML = `<input type="number" step="0.01" class="edit-importe ${inputClasses} text-right" value="${data.importe}">`;
    cells[3].classList.remove('text-red-500', 'text-green-500');

    // 4: Acciones
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
        importe: tr.querySelector('.edit-importe').value, // El backend espera 'importe' o 'monto'
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
            // ¡Éxito! Recargamos los datos por AJAX para actualizar el orden y posibles totales
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

    // Función interna para resaltar el texto buscado
    const textoBuscado = document.getElementById('filtroTexto').value.trim();
    const highlight = (text) => {
        const safeText = escapeHtml(text || '');
        if (!textoBuscado) return safeText;
        
        const safeQuery = escapeHtml(textoBuscado).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // Escapar para RegExp
        const regex = new RegExp(`(${safeQuery})`, 'gi');
        return safeText.replace(regex, '<mark class="bg-yellow-200 text-yellow-900 font-extrabold rounded px-0.5">$1</mark>');
    };

    return `
        <!-- Transaction ID: ${m.id} -->
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
    console.log('Replacing content for transaction ID:', m.id);
    tr.classList.remove('edit-mode', 'bg-indigo-50');
    tr.dataset.transaction = JSON.stringify(m);
    tr.innerHTML = generarContenidoHtmlFila(m);
}

function renderTabla(movimientos, tbody) {
    movimientos.forEach(m => {
        console.log('Creating row for transaction ID:', m.id);
        const tr = document.createElement('tr');
        tr.className = 'fila-movimiento hover:bg-gray-50 transition';
        // Habilitar drag and drop
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
                'X-CSRF-TOKEN': window.csrf_token // Importante para la seguridad
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
            body: JSON.stringify({ transactionId, categoryId: categoryId || null }), // categoryId puede ser null para "Por clasificar"
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token }
        });
        const data = await res.json();
        if (data.success) {
            // Recargamos la tabla y actualizamos los totales para reflejar los cambios
            console.log(`Reassign successful for transaction ${transactionId}, refreshing table.`);
            // Limpiamos el filtro de categoría para asegurar que la transacción reasignada sea visible.
            document.getElementById('filtroCategoria').value = ''; 
            await cargarTransacciones();
            mostrarNotificacion("¡Hecho! Categoría reasignada.");
        } else {
            alert("Error al reasignar categoría: " + (data.error || "Desconocido"));
            cargarTransacciones(); // Recarga para revertir cualquier cambio visual optimista
        }
    } catch (err) {
        console.error("Error de comunicación al reasignar:", err);
        alert("Error de comunicación al reasignar.");
    } finally {
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
    }, 400); // Espera 400ms después de que el usuario deje de escribir
});

// Escuchar el filtro superior global de tipos
document.getElementById('filtroTipoGlobal').addEventListener('change', () => {
    estadoPaginacion.paginaActual = 1;
    cargarTransacciones();
});

// Carga inicial de datos al entrar en la página
document.addEventListener('DOMContentLoaded', () => {
    // --- INICIO: Leer parámetros de la URL para pre-filtrar ---
    // Esto permite que otras páginas (como el dashboard) nos enlacen con filtros aplicados.
    const urlParams = new URLSearchParams(window.location.search);
    const categoryIdFromUrl = urlParams.get('categoryId');
    const startDateFromUrl = urlParams.get('startDate');
    const endDateFromUrl = urlParams.get('endDate');

    if (categoryIdFromUrl) {
        document.getElementById('filtroCategoria').value = categoryIdFromUrl;
    }
    if (startDateFromUrl) {
        document.getElementById('filtroInicio').value = startDateFromUrl;
    }
    if (endDateFromUrl) {
        document.getElementById('filtroFin').value = endDateFromUrl;
    }
    // --- FIN: Leer parámetros ---

    // Carga inicial de la tabla (ahora usará los filtros pre-cargados si existen)
    cargarTransacciones();

    // Añadimos los listeners para la ordenación
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const newSortBy = th.dataset.sort;
            if (estadoOrdenacion.sortBy === newSortBy) {
                estadoOrdenacion.sortOrder = estadoOrdenacion.sortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                estadoOrdenacion.sortBy = newSortBy;
                estadoOrdenacion.sortOrder = (newSortBy === 'descripcion' || newSortBy === 'categoria_nombre') ? 'ASC' : 'DESC';
            }
                // Sincronizar el desplegable visual con el clic en la tabla
                const selectOrden = document.getElementById('filtroOrden');
                if (selectOrden) selectOrden.value = `${estadoOrdenacion.sortBy}-${estadoOrdenacion.sortOrder}`;
                
            estadoPaginacion.paginaActual = 1;
            cargarTransacciones();
        });
    });

    // Listener para el botón de eliminar seleccionados
    document.getElementById('btnEliminarSeleccionados').addEventListener('click', eliminarSeleccionados);

    // Listener para el botón de aplicar categoría masiva
    document.getElementById('btnAplicarCategoria').addEventListener('click', cambiarCategoriaSeleccionados);

    // Listener para el checkbox "seleccionar todo"
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

    // Delegación de eventos para los checkboxes de cada fila
    document.getElementById('tablaMovimientos').addEventListener('change', (e) => {
        if (!e.target.classList.contains('row-checkbox')) return;
        e.target.checked ? seleccionados.add(e.target.dataset.id) : seleccionados.delete(e.target.dataset.id);
        updateBulkActionsBar();
    });

    // --- Lógica de Arrastrar y Soltar (Drag and Drop) ---
    const dropZone = document.getElementById('category-drop-zone');
    const tablaMovimientos = document.getElementById('tablaMovimientos');

    tablaMovimientos.addEventListener('dragstart', (e) => {
        const tr = e.target.closest('.fila-movimiento');
        if (tr) {
            const transactionData = JSON.parse(tr.dataset.transaction);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', transactionData.id.toString()); // Debe ser String y 'text/plain' por compatibilidad
            // Añadimos un pequeño delay para que el navegador "capture" la imagen del elemento antes de hacerlo semitransparente
            setTimeout(() => { tr.classList.add('opacity-40'); }, 0);
        }
    });

    tablaMovimientos.addEventListener('dragend', (e) => {
        // Limpiamos la opacidad de todas las filas para asegurar que vuelvan a su estado normal
        document.querySelectorAll('.fila-movimiento.opacity-40').forEach(el => el.classList.remove('opacity-40'));
        // Aprovechamos para limpiar cualquier resaltado residual en las categorías
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
        const transactionIdToReassign = e.dataTransfer.getData('text/plain'); // Obtenemos el ID
        if (transactionIdToReassign && categoryId !== undefined) {
            reassignTransactionCategory(transactionIdToReassign, categoryId);
        }
    });
});

// Listener para cuando se guarda desde el panel lateral
window.addEventListener('tx:saved', (e) => {
    // Cuando se crea o edita una transacción desde el panel, la forma más
    // robusta de asegurar que se vea es recargar la tabla.
    // Opcionalmente, podríamos ir a la página 1 para ver los nuevos registros.
    cargarTransacciones();
});

// Listener para el botón de exportar
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

    window.location.href = `controllers/ExportRouter.php?${params.toString()}`;
});
</script>
+
<!-- Carga el script que controla el panel lateral de edición/creación -->
<script src="assets/js/transacciones_editar.js"></script>
<?php include 'includes/footer.php'; ?>