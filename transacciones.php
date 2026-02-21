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

function getFamiliaCategorias($id, $jerarquia) {
    if (!$id) return '';
    $ancestros = [$id];
    $actual = $id;
    while(true) {
        $parent = null;
        foreach($jerarquia as $c) {
            if ($c['id'] == $actual) {
                $parent = $c['parent_id'];
                break;
            }
        }
        if ($parent) {
            $ancestros[] = $parent;
            $actual = $parent;
        } else {
            break;
        }
    }
    return implode(',', $ancestros); 
}

include 'includes/header.php'; 
?>

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800">Mantenimiento de Movimientos</h1>
            <p class="text-sm text-gray-500 mt-1">Filtra por categorías principales para ver todos sus subgastos.</p>
        </div>
        <button onclick="abrirModalTransaccion()" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 hover:shadow-lg font-bold transition duration-300 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Registro
        </button>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-bold mb-1 text-gray-700">Mes</label>
            <input type="month" id="filterMonth" onchange="filtrarTabla()" class="border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-medium text-gray-700">
        </div>
        <div class="flex-grow max-w-xs">
            <label class="block text-sm font-bold mb-1 text-gray-700">Categoría</label>
            <select id="filterCategory" onchange="filtrarTabla()" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-200 outline-none transition font-medium text-gray-700">
                <option value="">Todas las categorías</option>
                <?php foreach($categoriasLista as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button onclick="limpiarFiltros()" class="text-gray-500 hover:text-indigo-600 font-bold px-4 py-2.5 bg-gray-50 hover:bg-indigo-50 rounded-lg transition border border-gray-100">
            Limpiar
        </button>
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
                    <?php if(empty($transacciones)): ?>
                        <tr id="filaVacia"><td colspan="5" class="p-8 text-center text-gray-400 italic font-medium">No hay transacciones registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach($transacciones as $t): 
                            $familiaStr = getFamiliaCategorias($t['categoria_id'], $jerarquiaCats);
                            // CORRECCIÓN: Leemos $t['importe'] para evitar el error de "Undefined array key monto"
                            $importe = isset($t['importe']) ? (float)$t['importe'] : 0;
                        ?>
                        <tr class="transaccion-row hover:bg-indigo-50/30 transition duration-150" 
                            data-mes="<?= substr($t['fecha'], 0, 7) ?>" 
                            data-familia="<?= $familiaStr ?>">
                            <td class="p-4 text-gray-600 text-sm font-medium"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                            <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($t['descripcion']) ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-md text-xs font-bold border border-gray-200">
                                    <?= htmlspecialchars($t['categoria_nombre'] ?? 'Sin categoría') ?>
                                </span>
                            </td>
                            <td class="p-4 text-right font-extrabold <?= $importe < 0 ? 'text-red-500' : 'text-green-500' ?>">
                                <?= number_format($importe, 2, ',', '.') ?>€
                            </td>
                            <td class="p-4 text-center">
                                <button onclick="abrirModalTransaccion(<?= $t['id'] ?>, '<?= $t['fecha'] ?>', '<?= htmlspecialchars(addslashes($t['descripcion'])) ?>', <?= $importe ?>, <?= $t['categoria_id'] ?? 'null' ?>)" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button onclick="eliminarTransaccion(<?= $t['id'] ?>)" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalTransaccion" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div id="modalTransaccionContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 id="modalTitle" class="text-2xl font-extrabold mb-6 text-gray-800">Formulario</h2>
        <form id="formTransaccion" class="space-y-5">
            <input type="hidden" name="id" id="transaccion_id">
            
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Fecha</label>
                <input type="date" name="fecha" id="fecha" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Descripción</label>
                <input type="text" name="descripcion" id="descripcion" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Ej: Compra mensual, Nómina..." required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Categoría</label>
                <select name="categoria_id" id="categoria_id" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required>
                    <option value="" disabled selected>Selecciona una categoría</option>
                    <?php foreach($categoriasLista as $c): ?>
                        <option value="<?= $c['id'] ?>" data-tipo="<?= htmlspecialchars($c['tipo']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Importe <span class="text-xs text-indigo-500 font-normal ml-1">(Calcularemos el signo automáticamente)</span></label>
                <input type="number" step="0.01" name="monto" id="monto" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" placeholder="Ej: 50.00 o 1500.00" required>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalTransaccion()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md transition transform hover:-translate-y-0.5">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function filtrarTabla() {
    const mes = document.getElementById('filterMonth').value;
    const catFiltro = document.getElementById('filterCategory').value;
    const rows = document.querySelectorAll('.transaccion-row');
    let visibles = 0;

    rows.forEach(row => {
        const rowMes = row.getAttribute('data-mes');
        const rowFamilia = row.getAttribute('data-familia').split(',');
        const matchMes = (mes === '' || rowMes === mes);
        const matchCat = (catFiltro === '' || rowFamilia.includes(catFiltro));

        if (matchMes && matchCat) {
            row.style.display = '';
            visibles++;
        } else {
            row.style.display = 'none';
        }
    });

    let filaVacia = document.getElementById('filaSinResultados');
    if (visibles === 0 && rows.length > 0) {
        if (!filaVacia) document.getElementById('tablaCuerpo').insertAdjacentHTML('beforeend', '<tr id="filaSinResultados"><td colspan="5" class="p-8 text-center text-gray-400 italic font-medium border-t border-gray-100">No hay movimientos que coincidan.</td></tr>');
    } else if (filaVacia) {
        filaVacia.remove();
    }
}

function limpiarFiltros() {
    document.getElementById('filterMonth').value = '';
    document.getElementById('filterCategory').value = '';
    filtrarTabla();
}

function abrirModalTransaccion(id = null, fecha = '', descripcion = '', monto = '', categoria_id = null) {
    const form = document.getElementById('formTransaccion');
    form.reset();
    document.getElementById('transaccion_id').value = id || '';
    
    if (id) {
        document.getElementById('modalTitle').textContent = 'Editar Movimiento';
        document.getElementById('fecha').value = fecha;
        document.getElementById('descripcion').value = descripcion;
        document.getElementById('monto').value = Math.abs(parseFloat(monto)); 
        if(categoria_id) document.getElementById('categoria_id').value = categoria_id;
    } else {
        document.getElementById('modalTitle').textContent = 'Nuevo Movimiento';
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        document.getElementById('fecha').value = `${yyyy}-${mm}-${dd}`;
    }

    const modal = document.getElementById('modalTransaccion');
    const content = document.getElementById('modalTransaccionContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function cerrarModalTransaccion() {
    const modal = document.getElementById('modalTransaccion');
    const content = document.getElementById('modalTransaccionContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const modal = document.getElementById('modalTransaccion');
        if (!modal.classList.contains('hidden')) cerrarModalTransaccion();
    }
});

document.getElementById('formTransaccion').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const selectCat = document.getElementById('categoria_id');
    const tipoCategoria = selectCat.options[selectCat.selectedIndex].getAttribute('data-tipo');
    
    let montoIntroducido = parseFloat(document.getElementById('monto').value);

    if (tipoCategoria !== 'ingreso') {
        montoIntroducido = -Math.abs(montoIntroducido);
    } else {
        montoIntroducido = Math.abs(montoIntroducido); 
    }

    const data = {
        id: document.getElementById('transaccion_id').value,
        fecha: document.getElementById('fecha').value,
        descripcion: document.getElementById('descripcion').value,
        monto: montoIntroducido,
        categoria_id: selectCat.value
    };

    try {
        const res = await fetch('controllers/TransaccionRouter.php?action=save', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {'Content-Type': 'application/json'}
        });
        const json = await res.json();
        if (json.success) location.reload();
        else alert('Hubo un error al guardar el movimiento.');
    } catch (err) {
        console.error("Error al guardar:", err);
        alert('Error de conexión al servidor.');
    }
});

function eliminarTransaccion(id) {
    if (!confirm('¿Estás seguro de que deseas borrar este movimiento?')) return;
    
    fetch('controllers/TransaccionRouter.php?action=delete', {
        method: 'POST',
        body: JSON.stringify({id: id}),
        headers: {'Content-Type': 'application/json'}
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('No se pudo borrar el movimiento.');
    })
    .catch(err => console.error("Error al borrar:", err));
}
</script>

<?php include 'includes/footer.php'; ?>