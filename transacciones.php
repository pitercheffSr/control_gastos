<?php 
require_once 'config.php';
require_once 'models/TransaccionModel.php';
require_once 'models/CategoriaModel.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$uid = $_SESSION['usuario_id'];
$model = new TransaccionModel($pdo);
$catModel = new CategoriaModel($pdo);

$transacciones = $model->getAll($uid);
$categoriasLista = $catModel->getAll($uid);

$stmtJerarquia = $pdo->prepare("SELECT id, parent_id FROM categorias WHERE usuario_id = ?");
$stmtJerarquia->execute([$uid]);
$jerarquiaCats = $stmtJerarquia->fetchAll(PDO::FETCH_ASSOC);

$stmtUser = $pdo->prepare("SELECT dia_inicio_mes FROM usuarios WHERE id = ?");
$stmtUser->execute([$uid]);
$uData = $stmtUser->fetch();
$dia_inicio = $uData ? (int)$uData['dia_inicio_mes'] : 1;

function getFamiliaCategorias($id, $jerarquia) {
    if (!$id) return '';
    $ancestros = [$id];
    $actual = $id;
    while(true) {
        $parent = null;
        foreach($jerarquia as $c) {
            if ($c['id'] == $actual) { $parent = $c['parent_id']; break; }
        }
        if ($parent) { $ancestros[] = $parent; $actual = $parent; } else { break; }
    }
    return implode(',', $ancestros); 
}

include 'includes/header.php'; 
?>

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Mantenimiento de Movimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Navega y gestiona tus registros (6 por página).</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="controllers/ExportarRouter.php" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-emerald-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Exportar CSV
            </a>
            <button onclick="abrirModalBorradoMasivo()" class="bg-red-50 text-red-600 border border-red-200 px-5 py-2.5 rounded-xl shadow-sm hover:bg-red-100 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Borrar
            </button>
            <button onclick="abrirModalImportar()" class="bg-green-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-green-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg> Importar CSV
            </button>
            <button onclick="abrirModalTransaccion()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nuevo Registro
            </button>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-bold mb-1 text-gray-700">Mes Contable</label>
            <input type="month" id="filterMesContable" onchange="aplicarMesContable()" class="border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-bold text-indigo-600 cursor-pointer">
        </div>

        <div>
            <label class="block text-sm font-bold mb-1 text-gray-700">Desde</label>
            <input type="date" id="filterFechaInicio" onchange="alCambiarFechaManualTransacciones()" class="border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-medium text-gray-700 cursor-pointer">
        </div>
        <div>
            <label class="block text-sm font-bold mb-1 text-gray-700">Hasta</label>
            <input type="date" id="filterFechaFin" onchange="alCambiarFechaManualTransacciones()" class="border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-medium text-gray-700 cursor-pointer">
        </div>
        
        <div class="flex-grow max-w-xs relative">
            <label class="block text-sm font-bold mb-1 text-gray-700">Categoría</label>
            <input list="listaFiltroCategorias" id="inputFilterCategory" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-medium text-gray-700" placeholder="Escribe para buscar...">
            <input type="hidden" id="filterCategory">
            <datalist id="listaFiltroCategorias">
                <option data-id="" value="Todas las categorías"></option>
                <?php foreach($categoriasLista as $c): ?>
                    <option data-id="<?= $c['id'] ?>" value="<?= htmlspecialchars($c['nombre']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <button onclick="limpiarFiltrosTransacciones()" class="text-gray-500 hover:text-indigo-600 font-bold px-4 py-2.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition border border-gray-100">Limpiar</button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Fecha</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Descripción</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Categoría</th>
                        <th class="p-4 text-right text-gray-500 font-bold tracking-wider uppercase text-xs">Importe</th>
                        <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCuerpo" class="divide-y divide-gray-100">
                    <?php foreach($transacciones as $t): 
                        $familiaStr = getFamiliaCategorias($t['categoria_id'], $jerarquiaCats);
                        $importe = isset($t['importe']) ? (float)$t['importe'] : 0;
                    ?>
                    <tr class="transaccion-row" data-fecha="<?= $t['fecha'] ?>" data-familia="<?= $familiaStr ?>">
                        <td class="p-4 text-gray-600 text-sm font-medium"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                        <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($t['descripcion']) ?></td>
                        <td class="p-4"><span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-md text-xs font-bold border border-gray-200"><?= htmlspecialchars($t['categoria_nombre'] ?? 'Sin categoría') ?></span></td>
                        <td class="p-4 text-right font-extrabold <?= $importe < 0 ? 'text-red-500' : 'text-green-500' ?>"><?= number_format($importe, 2, ',', '.') ?>€</td>
                        <td class="p-4 text-center">
                            <button onclick="abrirModalTransaccion(<?= $t['id'] ?>, '<?= $t['fecha'] ?>', '<?= htmlspecialchars(addslashes($t['descripcion'])) ?>', <?= $importe ?>, <?= $t['categoria_id'] ?? 'null' ?>)" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                            <button onclick="eliminarTransaccion(<?= $t['id'] ?>)" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-100 p-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-gray-500" id="infoPaginacion">Cargando movimientos...</p>
            <div class="flex items-center gap-1.5 flex-wrap" id="botonesPaginacion"></div>
        </div>
    </div>
</div>

<div id="modalTransaccion" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalTransaccionContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 id="modalTitle" class="text-2xl font-extrabold mb-6 text-gray-800">Movimiento</h2>
        <form id="formTransaccion" class="space-y-5">
            <input type="hidden" name="id" id="transaccion_id">
            <div><label class="block text-sm font-bold mb-1.5 text-gray-700">Fecha</label><input type="date" name="fecha" id="fecha" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required></div>
            <div><label class="block text-sm font-bold mb-1.5 text-gray-700">Descripción</label><input type="text" name="descripcion" id="descripcion" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required autocomplete="off"></div>
            <div><label class="block text-sm font-bold mb-1.5 text-gray-700">Categoría (Buscar)</label><input list="listaSugerencias" id="input_cat_form" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Escribe..." required autocomplete="off"><input type="hidden" name="categoria_id" id="hidden_cat_id">
                <datalist id="listaSugerencias"><?php foreach($categoriasLista as $c): ?><option data-id="<?= $c['id'] ?>" data-tipo="<?= htmlspecialchars($c['tipo_fijo'] ?? 'gasto') ?>" value="<?= htmlspecialchars($c['nombre']) ?>"></option><?php endforeach; ?></datalist>
            </div>
            <div><label class="block text-sm font-bold mb-1.5 text-gray-700">Importe (€)</label><input type="number" step="0.01" name="monto" id="monto" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required></div>
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100"><button type="button" onclick="cerrarModalTransaccion()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button><button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md">Guardar</button></div>
        </form>
    </div>
</div>

<div id="modalImportar" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalImportarContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 class="text-2xl font-extrabold mb-4 text-gray-800">Importar CSV</h2>
        <form action="controllers/ImportarRouter.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="file" name="archivo_csv" accept=".csv" required class="w-full border border-gray-300 rounded-lg p-2 font-medium">
            <label class="block text-sm font-bold mb-1.5 text-gray-700">Asignar a:</label>
            <input list="listaSugerencias" id="input_cat_excel" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required autocomplete="off">
            <input type="hidden" name="categoria_id" id="hidden_cat_excel">
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalImportar()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-green-600 text-white font-bold hover:bg-green-700 rounded-xl shadow-md transition">Importar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalBorradoMasivo" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalBorradoMasivoContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300 border-t-8 border-red-500">
        <h2 class="text-2xl font-extrabold mb-2 text-gray-800">Borrado Masivo</h2>
        <p class="text-sm text-gray-500 mb-6">Elimina de golpe todos los movimientos entre dos fechas. <strong class="text-red-500">Esta acción no se puede deshacer.</strong></p>
        
        <form id="formBorradoMasivo" class="space-y-5">
            <div class="flex gap-4">
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-1.5 text-gray-700">Desde</label>
                    <input type="date" id="borrado_inicio" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-red-500 transition cursor-pointer">
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-1.5 text-gray-700">Hasta</label>
                    <input type="date" id="borrado_fin" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-red-500 transition cursor-pointer">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalBorradoMasivo()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-red-600 text-white font-bold hover:bg-red-700 rounded-xl shadow-md transition">Eliminar Definitivamente</button>
            </div>
        </form>
    </div>
</div>

<script>
const DIA_INICIO = <?= $dia_inicio ?>;
let paginaActual = 1;
const filasPorPagina = 6;
let filasFiltradas = [];

function alCambiarFechaManualTransacciones() {
    document.getElementById('filterMesContable').value = '';
    resetPaginaYFiltrar();
}

function limpiarFiltrosTransacciones() {
    document.getElementById('filterMesContable').value = '';
    document.getElementById('inputFilterCategory').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterFechaInicio').value = '';
    document.getElementById('filterFechaFin').value = '';
    resetPaginaYFiltrar();
}

function aplicarMesContable() {
    const mesVal = document.getElementById('filterMesContable').value;
    if (!mesVal) return;
    
    const [yearStr, monthStr] = mesVal.split('-');
    let year = parseInt(yearStr);
    let month = parseInt(monthStr);

    let fInicio, fFin;
    if (DIA_INICIO === 1) {
        fInicio = `${year}-${monthStr}-01`;
        let lastDay = new Date(year, month, 0).getDate();
        fFin = `${year}-${monthStr}-${lastDay.toString().padStart(2, '0')}`;
    } else {
        let prevMonth = month - 1;
        let prevYear = year;
        if (prevMonth === 0) { prevMonth = 12; prevYear--; }
        fInicio = `${prevYear}-${prevMonth.toString().padStart(2, '0')}-${DIA_INICIO.toString().padStart(2, '0')}`;
        let dFin = new Date(year, month - 1, DIA_INICIO - 1);
        let finM = (dFin.getMonth() + 1).toString().padStart(2, '0');
        let finD = dFin.getDate().toString().padStart(2, '0');
        fFin = `${dFin.getFullYear()}-${finM}-${finD}`;
    }

    document.getElementById('filterFechaInicio').value = fInicio;
    document.getElementById('filterFechaFin').value = fFin;
    resetPaginaYFiltrar();
}

function resetPaginaYFiltrar() { paginaActual = 1; actualizarVista(); }

function actualizarVista() {
    try {
        if (isNaN(paginaActual) || paginaActual < 1) paginaActual = 1;

        const fInicio = document.getElementById('filterFechaInicio').value;
        const fFin = document.getElementById('filterFechaFin').value;
        const catFiltro = document.getElementById('filterCategory').value;
        const rows = Array.from(document.querySelectorAll('.transaccion-row'));

        filasFiltradas = rows.filter(row => {
            const rowFecha = row.getAttribute('data-fecha') || '';
            const rowFamilia = (row.getAttribute('data-familia') || '').split(',');
            
            let matchFecha = true;
            if (fInicio !== '' && rowFecha < fInicio) matchFecha = false;
            if (fFin !== '' && rowFecha > fFin) matchFecha = false;

            const matchCat = (catFiltro === '' || rowFamilia.includes(catFiltro));
            return matchFecha && matchCat;
        });

        const total = filasFiltradas.length;
        const totalPaginas = Math.ceil(total / filasPorPagina);
        if (paginaActual > totalPaginas && totalPaginas > 0) paginaActual = totalPaginas;

        rows.forEach(r => r.style.display = 'none');
        const inicio = (paginaActual - 1) * filasPorPagina;
        filasFiltradas.slice(inicio, inicio + filasPorPagina).forEach(row => row.style.display = '');

        const pInfo = document.getElementById('infoPaginacion');
        if (pInfo) pInfo.textContent = total > 0 ? `Mostrando ${inicio + 1} a ${Math.min(inicio + filasPorPagina, total)} de ${total} registros` : 'Sin resultados';
        renderizarBotones(totalPaginas);
    } catch (error) { console.error("Error en paginación:", error); }
}

function renderizarBotones(total) {
    const container = document.getElementById('botonesPaginacion');
    if (!container) return;
    container.innerHTML = '';
    if (total <= 1) return;

    const btnPrev = document.createElement('button');
    btnPrev.innerHTML = '←';
    btnPrev.className = `px-3 py-1 rounded-lg border font-bold ${paginaActual === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-indigo-600 hover:bg-indigo-50 border-indigo-200'}`;
    btnPrev.onclick = () => { if(paginaActual > 1) { paginaActual--; actualizarVista(); } };
    container.appendChild(btnPrev);

    let maxPagesToShow = 10;
    let startPage = Math.max(1, paginaActual - Math.floor(maxPagesToShow / 2));
    let endPage = startPage + maxPagesToShow - 1;
    if (endPage > total) { endPage = total; startPage = Math.max(1, endPage - maxPagesToShow + 1); }

    if (startPage > 1) {
        const btnFirst = document.createElement('button');
        btnFirst.innerHTML = '1..';
        btnFirst.className = 'px-3 py-1 rounded-lg border font-bold text-gray-500 hover:bg-gray-50';
        btnFirst.onclick = () => { paginaActual = 1; actualizarVista(); };
        container.appendChild(btnFirst);
    }

    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `px-3 py-1 rounded-lg border font-bold ${i === paginaActual ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'}`;
        btn.onclick = () => { paginaActual = i; actualizarVista(); };
        container.appendChild(btn);
    }

    if (endPage < total) {
        const btnLast = document.createElement('button');
        btnLast.innerHTML = '..' + total;
        btnLast.className = 'px-3 py-1 rounded-lg border font-bold text-gray-500 hover:bg-gray-50';
        btnLast.onclick = () => { paginaActual = total; actualizarVista(); };
        container.appendChild(btnLast);
    }

    const btnNext = document.createElement('button');
    btnNext.innerHTML = '→';
    btnNext.className = `px-3 py-1 rounded-lg border font-bold ${paginaActual === total ? 'text-gray-300 cursor-not-allowed' : 'text-indigo-600 hover:bg-indigo-50 border-indigo-200'}`;
    btnNext.onclick = () => { if(paginaActual < total) { paginaActual++; actualizarVista(); } };
    container.appendChild(btnNext);
}

function vincularDatalist(inputId, hiddenId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function() {
        const list = document.getElementById('listaSugerencias');
        const hiddenInput = document.getElementById(hiddenId);
        hiddenInput.value = "";
        Array.from(list.options).forEach(opt => {
            if (opt.value === this.value) { hiddenInput.value = opt.getAttribute('data-id'); hiddenInput.setAttribute('data-tipo', opt.getAttribute('data-tipo')); }
        });
        if(inputId === 'inputFilterCategory') resetPaginaYFiltrar();
    });
}
setTimeout(() => { vincularDatalist('input_cat_form', 'hidden_cat_id'); vincularDatalist('input_cat_excel', 'hidden_cat_excel'); vincularDatalist('inputFilterCategory', 'filterCategory'); }, 50);


// --- FUNCIONES DEL MODAL "NUEVO/EDITAR" ---
function abrirModalTransaccion(id = null, fecha = '', descripcion = '', monto = '', categoria_id = null) {
    document.getElementById('formTransaccion').reset();
    document.getElementById('transaccion_id').value = id || '';
    if (id) {
        document.getElementById('fecha').value = fecha;
        document.getElementById('descripcion').value = descripcion;
        document.getElementById('monto').value = Math.abs(parseFloat(monto));
        document.getElementById('hidden_cat_id').value = categoria_id;
        Array.from(document.getElementById('listaSugerencias').options).forEach(opt => {
            if(opt.getAttribute('data-id') == categoria_id) document.getElementById('input_cat_form').value = opt.value;
        });
    } else { document.getElementById('fecha').value = new Date().toISOString().split('T')[0]; }
    document.getElementById('modalTransaccion').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalTransaccionContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalTransaccion() {
    const content = document.getElementById('modalTransaccionContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { const modal = document.getElementById('modalTransaccion'); if(modal) modal.classList.add('hidden'); }, 300);
}

// --- FUNCIONES DEL MODAL "IMPORTAR" ---
function abrirModalImportar() {
    document.getElementById('modalImportar').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalImportarContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalImportar() {
    const content = document.getElementById('modalImportarContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { const modal = document.getElementById('modalImportar'); if(modal) modal.classList.add('hidden'); }, 300);
}

// --- FUNCIONES DEL MODAL "BORRADO MASIVO" ---
function abrirModalBorradoMasivo() {
    document.getElementById('formBorradoMasivo').reset();
    document.getElementById('modalBorradoMasivo').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalBorradoMasivoContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalBorradoMasivo() {
    const content = document.getElementById('modalBorradoMasivoContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { const modal = document.getElementById('modalBorradoMasivo'); if(modal) modal.classList.add('hidden'); }, 300);
}

// --- CIERRE DE TODOS LOS MODALES CON ESCAPE ---
document.addEventListener('keydown', (e) => { 
    if(e.key === "Escape") { 
        cerrarModalTransaccion(); 
        cerrarModalImportar();
        cerrarModalBorradoMasivo();
    } 
});

function guardarMemoriaFiltros() {
    sessionStorage.setItem('memoriaPaginaTransacciones', paginaActual.toString());
    sessionStorage.setItem('memoriaFiltroInicio', document.getElementById('filterFechaInicio').value);
    sessionStorage.setItem('memoriaFiltroFin', document.getElementById('filterFechaFin').value);
    sessionStorage.setItem('memoriaFiltroMes', document.getElementById('filterMesContable').value);
}

// --- ENVÍO DE FORMULARIOS AL BACKEND ---
document.getElementById('formTransaccion').addEventListener('submit', async (e) => {
    e.preventDefault();
    const catId = document.getElementById('hidden_cat_id').value;
    if(!catId) return alert("Selecciona una categoría válida.");
    const tipo = document.getElementById('hidden_cat_id').getAttribute('data-tipo') || 'gasto';
    let val = Math.abs(parseFloat(document.getElementById('monto').value));
    const data = { id: document.getElementById('transaccion_id').value, fecha: document.getElementById('fecha').value, descripcion: document.getElementById('descripcion').value, monto: tipo === 'ingreso' ? val : -val, categoria_id: catId };
    
    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=save', { method: 'POST', body: JSON.stringify(data), headers: {'Content-Type': 'application/json'} });
        if((await res.json()).success) { 
            guardarMemoriaFiltros(); 
            location.reload(); 
        }
    } catch (err) { console.error(err); }
});

function eliminarTransaccion(id) {
    if(confirm('¿Borrar?')) fetch('controllers/TransaccionRouter.php?action=delete', { method: 'POST', body: JSON.stringify({id}), headers: {'Content-Type': 'application/json'} }).then(() => { 
        guardarMemoriaFiltros(); 
        location.reload(); 
    });
}

document.getElementById('formBorradoMasivo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fInicio = document.getElementById('borrado_inicio').value;
    const fFin = document.getElementById('borrado_fin').value;

    if (fInicio > fFin) return alert("La fecha 'Desde' no puede ser posterior a 'Hasta'.");
    
    if (!confirm(`¿Estás SEGURO de que quieres borrar TODOS los movimientos entre el ${fInicio} y el ${fFin}?`)) return;

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=deleteMasivo', {
            method: 'POST',
            body: JSON.stringify({ fecha_inicio: fInicio, fecha_fin: fFin }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        
        if (data.success) {
            alert(`Limpieza completada: Se han eliminado ${data.eliminados} movimientos.`);
            guardarMemoriaFiltros(); 
            location.reload();
        } else {
            alert("Hubo un error al intentar borrar los movimientos.");
        }
    } catch (err) { console.error(err); }
});

document.addEventListener('DOMContentLoaded', () => {
    let defInicio = '';
    let defFin = '';
    let defMes = '';

    if (sessionStorage.getItem('memoriaFiltroInicio') !== null) {
        defInicio = sessionStorage.getItem('memoriaFiltroInicio');
        sessionStorage.removeItem('memoriaFiltroInicio');
    }
    if (sessionStorage.getItem('memoriaFiltroFin') !== null) {
        defFin = sessionStorage.getItem('memoriaFiltroFin');
        sessionStorage.removeItem('memoriaFiltroFin');
    }
    if (sessionStorage.getItem('memoriaFiltroMes') !== null) {
        defMes = sessionStorage.getItem('memoriaFiltroMes');
        sessionStorage.removeItem('memoriaFiltroMes');
    }

    document.getElementById('filterMesContable').value = defMes;
    document.getElementById('filterFechaInicio').value = defInicio;
    document.getElementById('filterFechaFin').value = defFin;
    document.getElementById('inputFilterCategory').value = '';
    document.getElementById('filterCategory').value = '';

    try {
        const paginaGuardada = sessionStorage.getItem('memoriaPaginaTransacciones');
        if (paginaGuardada !== null) {
            const num = parseInt(paginaGuardada);
            if (!isNaN(num) && num > 0) paginaActual = num;
            sessionStorage.removeItem('memoriaPaginaTransacciones'); 
        } else {
            paginaActual = 1;
        }
    } catch (e) { paginaActual = 1; }
    
    actualizarVista();
});
</script>

<?php include 'includes/footer.php'; ?>