<?php 
// 1. Cargamos la configuración centralizada (ya no usamos db.php)
require_once 'config.php';
require_once 'models/TransaccionModel.php';

// 2. Cargamos el header (que además valida la sesión)
include 'includes/header.php'; 

// 3. Instanciamos el modelo usando tu variable $pdo real
$model = new TransaccionModel($pdo);
$transacciones = $model->getAll($_SESSION['usuario_id']);
?>
<link rel="stylesheet" href="assets/css/transacciones.css">

<div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800">Mantenimiento de Transacciones</h1>
        <button onclick="nuevaTransaccion()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-indigo-700 transition">
            + Nuevo Registro
        </button>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-semibold mb-1 text-gray-700">Filtrar por Mes</label>
            <input type="month" id="filterMonth" onchange="filtrarTabla()" class="border rounded-lg p-2 focus:ring-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1 text-gray-700">Categoría</label>
            <select id="filterCategory" onchange="filtrarTabla()" class="border rounded-lg p-2 min-w-[150px] focus:ring-indigo-500 outline-none">
                <option value="">Todas</option>
            </select>
        </div>
        <button onclick="location.reload()" class="text-gray-500 hover:text-indigo-600 text-sm font-semibold">Limpiar Filtros</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-4 text-gray-600">Fecha</th>
                    <th class="p-4 text-gray-600">Descripción</th>
                    <th class="p-4 text-gray-600">Categoría</th>
                    <th class="p-4 text-right text-gray-600">Importe</th>
                    <th class="p-4 text-center text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($transacciones)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500 italic">No hay transacciones registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($transacciones as $t): ?>
                    <tr class="transaccion-row border-b hover:bg-gray-50 transition" 
                        data-mes="<?= substr($t['fecha'],0,7) ?>" 
                        data-categoria="<?= $t['categoria_id'] ?>">
                        <td class="p-4"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                        <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($t['descripcion']) ?></td>
                        <td class="p-4"><span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-semibold"><?= htmlspecialchars($t['categoria_nombre'] ?? 'Sin categoría') ?></span></td>
                        <td class="p-4 text-right font-bold <?= $t['monto'] < 0 ? 'text-red-500' : 'text-green-500' ?>">
                            <?= number_format($t['monto'], 2, ',', '.') ?>€
                        </td>
                        <td class="p-4 text-center">
                            <button onclick="editarTransaccion(<?= $t['id'] ?>)" class="text-indigo-600 hover:underline mx-2 font-semibold">Editar</button>
                            <button onclick="eliminarTransaccion(<?= $t['id'] ?>)" class="text-red-500 hover:underline mx-2 font-semibold">Borrar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalTransaccion" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-50 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-90 transition-transform duration-300">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6 text-gray-800">Nueva Transacción</h2>
        <form id="formTransaccion" class="space-y-4">
            <input type="hidden" name="id" id="transaccion_id">
            
            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Fecha</label>
                <input type="date" name="fecha" id="fecha" class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Descripción</label>
                <input type="text" name="descripcion" id="descripcion" class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: Supermercado" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Categoría</label>
                <select name="categoria_id" id="categoria_id" class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500" required></select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Importe (Negativo para gastos)</label>
                <input type="number" step="0.01" name="monto" id="monto" class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500" placeholder="-50.00" required>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeModal()" class="px-6 py-2 text-gray-500 font-semibold hover:text-gray-700">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-md">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/transacciones.js"></script>
<?php include 'includes/footer.php'; ?>