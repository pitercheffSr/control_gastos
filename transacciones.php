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

// Cargamos movimientos y categorías
$transModel = new TransaccionModel($pdo);
$movimientos = $transModel->getAll($uid);

$catModel = new CategoriaModel($pdo);
$categoriasRaw = $catModel->getAll($uid);

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

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Movimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Historial completo de tus finanzas.</p>
        </div>
        <button onclick="abrirModalTransaccion()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nuevo Movimiento
        </button>
    </div>

    <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-wrap items-center gap-4 mb-8">
        <div class="flex items-center gap-2 border-r border-gray-100 pr-4">
            <span class="text-sm font-bold text-gray-600 pl-2">Mes:</span>
            <select id="filtroMes" onchange="aplicarFiltroMes()" class="border-none focus:ring-0 text-indigo-600 font-bold bg-transparent cursor-pointer outline-none">
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
            <input type="date" id="filtroInicio" onchange="filtrarTablaManual()" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-gray-600">Hasta:</span>
            <input type="date" id="filtroFin" onchange="filtrarTablaManual()" class="border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-200 text-indigo-600 font-bold bg-gray-50 p-1.5 outline-none text-sm cursor-pointer">
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
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Fecha</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Descripción</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Categoría</th>
                        <th class="p-4 text-right text-gray-500 font-bold tracking-wider uppercase text-xs">Importe</th>
                        <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaMovimientos" class="divide-y divide-gray-100">
                    <?php if(empty($movimientos)): ?>
                        <tr id="filaVacia"><td colspan="5" class="p-8 text-center text-gray-400">No hay movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach($movimientos as $m): 
                            $isGasto = $m['importe'] < 0;
                            $fechaF = date('d/m/Y', strtotime($m['fecha']));
                        ?>
                        <tr class="fila-movimiento hover:bg-gray-50 transition" data-fecha="<?= $m['fecha'] ?>">
                            <td class="p-4 text-sm text-gray-500 font-medium"><?= $fechaF ?></td>
                            <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($m['descripcion']) ?></td>
                            <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-xs font-bold"><?= htmlspecialchars($m['categoria_nombre'] ?: 'Por clasificar') ?></span></td>
                            <td class="p-4 text-right font-extrabold <?= $isGasto ? 'text-red-500' : 'text-green-500' ?>">
                                <?= number_format(abs($m['importe']), 2, ',', '.') ?>€
                            </td>
                            <td class="p-4 text-center">
                                <button onclick="abrirModalTransaccion(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['descripcion'])) ?>', <?= abs($m['importe']) ?>, '<?= $m['fecha'] ?>', '<?= $m['categoria_id'] ?>', <?= $isGasto ? 'true' : 'false' ?>)" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button onclick="eliminarTransaccion(<?= $m['id'] ?>)" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="filaSinResultados" class="hidden"><td colspan="5" class="p-8 text-center text-gray-500 font-medium italic">No hay movimientos en estas fechas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalTransaccion" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalTransContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 id="modalTransTitle" class="text-2xl font-extrabold mb-6 text-gray-800">Movimiento</h2>
        <form id="formTransaccion" class="space-y-5">
            <input type="hidden" id="trans_id">
            
            <div class="flex gap-4 mb-4 bg-gray-50 p-1.5 rounded-lg border border-gray-200">
                <button type="button" id="btnTipoGasto" onclick="setTipoTransaccion('gasto')" class="flex-1 py-2 rounded-md font-bold text-sm transition shadow-sm bg-white text-red-600 border border-gray-200">Gasto</button>
                <button type="button" id="btnTipoIngreso" onclick="setTipoTransaccion('ingreso')" class="flex-1 py-2 rounded-md font-bold text-sm transition text-gray-500 hover:text-green-600">Ingreso</button>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Descripción</label>
                <input type="text" id="trans_desc" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required autocomplete="off" placeholder="Ej: Compra supermercado">
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-bold mb-1.5 text-gray-700">Importe (€)</label>
                    <input type="number" id="trans_importe" step="0.01" min="0.01" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required placeholder="0.00">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-bold mb-1.5 text-gray-700">Fecha</label>
                    <input type="date" id="trans_fecha" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Categoría</label>
                <select id="trans_cat" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition bg-white cursor-pointer">
                    <option value="">-- Por clasificar --</option>
                    <optgroup label="Gastos" id="optgroup-gastos">
                        <?php foreach($catGastos as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Ingresos" id="optgroup-ingresos" class="hidden">
                        <?php foreach($catIngresos as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalTransaccion()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const DIA_INICIO = <?= $dia_inicio ?>;

// ============================================
// LÓGICA DE FILTRADO EN TIEMPO REAL
// ============================================
function limpiarFiltros() {
    document.getElementById('filtroMes').value = '';
    document.getElementById('filtroInicio').value = '';
    document.getElementById('filtroFin').value = '';
    ejecutarFiltro();
}

function filtrarTablaManual() {
    document.getElementById('filtroMes').value = '';
    ejecutarFiltro();
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
    ejecutarFiltro();
}

function ejecutarFiltro() {
    const fInicio = document.getElementById('filtroInicio').value;
    const fFin = document.getElementById('filtroFin').value;
    const filas = document.querySelectorAll('.fila-movimiento');
    let visibles = 0;

    filas.forEach(fila => {
        const fecha = fila.dataset.fecha;
        let mostrar = true;

        if (fInicio && fecha < fInicio) mostrar = false;
        if (fFin && fecha > fFin) mostrar = false;

        if (mostrar) {
            fila.classList.remove('hidden');
            visibles++;
        } else {
            fila.classList.add('hidden');
        }
    });

    const msjVacio = document.getElementById('filaSinResultados');
    if (msjVacio) {
        if (visibles === 0 && filas.length > 0) {
            msjVacio.classList.remove('hidden');
        } else {
            msjVacio.classList.add('hidden');
        }
    }
}

// ============================================
// LÓGICA DEL MODAL Y GUARDADO
// ============================================
let tipoActual = 'gasto';

function setTipoTransaccion(tipo) {
    tipoActual = tipo;
    const btnGasto = document.getElementById('btnTipoGasto');
    const btnIngreso = document.getElementById('btnTipoIngreso');
    const optGastos = document.getElementById('optgroup-gastos');
    const optIngresos = document.getElementById('optgroup-ingresos');

    if(tipo === 'gasto') {
        btnGasto.classList.add('bg-white', 'text-red-600', 'shadow-sm', 'border', 'border-gray-200');
        btnGasto.classList.remove('text-gray-500', 'hover:text-red-600');
        btnIngreso.classList.remove('bg-white', 'text-green-600', 'shadow-sm', 'border', 'border-gray-200');
        btnIngreso.classList.add('text-gray-500', 'hover:text-green-600');
        optGastos.classList.remove('hidden');
        optIngresos.classList.add('hidden');
    } else {
        btnIngreso.classList.add('bg-white', 'text-green-600', 'shadow-sm', 'border', 'border-gray-200');
        btnIngreso.classList.remove('text-gray-500', 'hover:text-green-600');
        btnGasto.classList.remove('bg-white', 'text-red-600', 'shadow-sm', 'border', 'border-gray-200');
        btnGasto.classList.add('text-gray-500', 'hover:text-red-600');
        optIngresos.classList.remove('hidden');
        optGastos.classList.add('hidden');
    }
    document.getElementById('trans_cat').value = "";
}

function abrirModalTransaccion(id = null, desc = '', importe = '', fecha = '', catId = '', isGasto = true) {
    document.getElementById('formTransaccion').reset();
    document.getElementById('trans_id').value = id || '';
    
    if(id) {
        document.getElementById('trans_desc').value = desc;
        document.getElementById('trans_importe').value = importe;
        document.getElementById('trans_fecha').value = fecha;
        setTipoTransaccion(isGasto ? 'gasto' : 'ingreso');
        document.getElementById('trans_cat').value = catId || '';
    } else {
        document.getElementById('trans_fecha').value = new Date().toISOString().split('T')[0];
        setTipoTransaccion('gasto');
    }
    
    document.getElementById('modalTransaccion').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalTransContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalTransaccion() {
    const content = document.getElementById('modalTransContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { const modal = document.getElementById('modalTransaccion'); if(modal) modal.classList.add('hidden'); }, 300);
}

// Cierre centralizado con Escape [cite: 2026-01-17]
document.addEventListener('keydown', (e) => { 
    if(e.key === "Escape") { 
        cerrarModalTransaccion(); 
        
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    } 
});

document.getElementById('formTransaccion').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const btnGuardar = e.target.querySelector('button[type="submit"]');
    const textoOriginal = btnGuardar.innerText;
    btnGuardar.innerText = 'Guardando...';
    btnGuardar.disabled = true;
    btnGuardar.classList.add('opacity-75', 'cursor-not-allowed');
    
    let importeVal = parseFloat(document.getElementById('trans_importe').value);
    if(tipoActual === 'gasto' && importeVal > 0) importeVal = -importeVal;

    const data = {
        id: document.getElementById('trans_id').value,
        descripcion: document.getElementById('trans_desc').value,
        importe: importeVal,
        fecha: document.getElementById('trans_fecha').value,
        categoria_id: document.getElementById('trans_cat').value || null
    };

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=save', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {'Content-Type': 'application/json'}
        });
        
        const textoRespuesta = await res.text(); 
        
        try {
            const result = JSON.parse(textoRespuesta);
            if(result.success) {
                cerrarModalTransaccion();
                location.reload();
            } else {
                alert("Error al guardar: " + (result.error || "Desconocido"));
                restaurarBoton(btnGuardar, textoOriginal);
            }
        } catch (jsonErr) {
            console.warn("Se guardó, pero la respuesta no era JSON puro:", textoRespuesta);
            cerrarModalTransaccion();
            location.reload();
        }
    } catch (err) {
        console.error(err);
        alert("Error de comunicación. Revisa tu conexión.");
        restaurarBoton(btnGuardar, textoOriginal);
    }
});

function restaurarBoton(btn, texto) {
    btn.innerText = texto;
    btn.disabled = false;
    btn.classList.remove('opacity-75', 'cursor-not-allowed');
}

async function eliminarTransaccion(id) {
    if (!confirm("¿Seguro que quieres borrar este movimiento?")) return;
    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert("Error al borrar: " + (data.error || "Desconocido"));
    } catch (err) {
        alert("Error de comunicación.");
    }
}
</script>

<?php include 'includes/footer.php'; ?>