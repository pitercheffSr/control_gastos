<?php
require_once 'config.php';
checkAuth();

$uid = $_SESSION['user_id'];
$mensaje = '';
$error = '';

// --- 1. PROCESAR FORMULARIO (GUARDAR / EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if ($accion === 'eliminar' && !empty($id)) {
        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM transacciones WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $uid]);
        $mensaje = "Transacción eliminada.";
    } elseif ($accion === 'guardar') {
        // Guardar o Editar
        $fecha = $_POST['fecha'];
        $desc = trim($_POST['descripcion']);
        $cat_id = $_POST['categoria_id'];
        $importe = (float) $_POST['importe'];
        
        // Si es gasto (importe negativo), aseguramos el signo menos
        if ($_POST['tipo'] === 'gasto') {
            $importe = -abs($importe);
        } else {
            $importe = abs($importe);
        }

        if (empty($id)) {
            // INSERTAR NUEVA
            $sql = "INSERT INTO transacciones (usuario_id, fecha, descripcion, importe, categoria_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid, $fecha, $desc, $importe, $cat_id]);
            $mensaje = "Transacción creada correctamente.";
        } else {
            // ACTUALIZAR EXISTENTE
            $sql = "UPDATE transacciones SET fecha=?, descripcion=?, importe=?, categoria_id=? WHERE id=? AND usuario_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fecha, $desc, $importe, $cat_id, $id, $uid]);
            $mensaje = "Transacción actualizada.";
        }
    }
}

// --- 2. OBTENER DATOS ---

// A) Lista de Transacciones (Últimas 15)
$sqlList = "SELECT t.*, c.nombre as cat_nombre, c.color 
            FROM transacciones t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.usuario_id = ? 
            ORDER BY t.fecha DESC, t.id DESC LIMIT 15";
$stmtList = $pdo->prepare($sqlList);
$stmtList->execute([$uid]);
$transacciones = $stmtList->fetchAll();

// B) Árbol de Categorías para el Selector (Select)
// Obtenemos todo y lo organizamos en PHP para el <select> optgroup
$sqlCats = "SELECT * FROM categorias WHERE usuario_id = ? ORDER BY parent_id ASC, id ASC";
$stmtCats = $pdo->prepare($sqlCats);
$stmtCats->execute([$uid]);
$todasCats = $stmtCats->fetchAll();

// Organizamos por padres
$arbol = [];
foreach ($todasCats as $c) {
    if ($c['parent_id'] === NULL) {
        $arbol[$c['id']] = ['datos' => $c, 'hijos' => []];
    }
}
foreach ($todasCats as $c) {
    if ($c['parent_id'] !== NULL && isset($arbol[$c['parent_id']])) {
        $arbol[$c['parent_id']]['hijos'][] = $c;
    }
    // Nota: Para simplificar, este selector maneja 2 niveles visuales principales, 
    // pero guardará el ID correcto sea cual sea el nivel.
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h2 class="fw-bold text-dark">Mis Transacciones</h2>
    <button class="btn btn-primary" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Nueva Transacción
    </button>
</div>

<?php if($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Fecha</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="text-end">Importe</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transacciones as $t): ?>
                <tr>
                    <td class="ps-4"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                    <td>
                        <span class="badge rounded-pill fw-normal" 
                              style="background-color: <?= $t['color'] ?? '#ccc' ?>; color: #fff;">
                            <?= htmlspecialchars($t['cat_nombre'] ?? 'Sin categoría') ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($t['descripcion']) ?></td>
                    <td class="text-end fw-bold <?= $t['importe'] < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($t['importe'], 2) ?> €
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary border-0" 
                                onclick='editar(<?= json_encode($t) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Seguro que deseas eliminar este movimiento?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger border-0">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($transacciones)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No hay movimientos recientes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTransaccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="formTransaccion">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">Nueva Transacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" id="inputId">

                <div class="mb-3 text-center">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tipo" id="tipoGasto" value="gasto" checked>
                        <label class="btn btn-outline-danger" for="tipoGasto">Gasto (-)</label>

                        <input type="radio" class="btn-check" name="tipo" id="tipoIngreso" value="ingreso">
                        <label class="btn btn-outline-success" for="tipoIngreso">Ingreso (+)</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Importe (€)</label>
                    <input type="number" step="0.01" name="importe" id="inputImporte" class="form-control form-control-lg text-center" required placeholder="0.00">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" id="inputFecha" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" id="inputCategoria" class="form-select" required>
                            <option value="">Selecciona...</option>
                            <?php foreach($arbol as $padre): ?>
                                <optgroup label="<?= $padre['datos']['nombre'] ?>">
                                    <?php foreach($padre['hijos'] as $hijo): ?>
                                        <option value="<?= $hijo['id'] ?>"><?= $hijo['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" id="inputDescripcion" class="form-control" placeholder="Ej: Compra semanal Mercadona">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
var modalElement = document.getElementById('modalTransaccion');
var modalObj = new bootstrap.Modal(modalElement);

function abrirModal() {
    // Resetear formulario para nueva entrada
    document.getElementById('formTransaccion').reset();
    document.getElementById('inputId').value = '';
    document.getElementById('modalTitulo').textContent = 'Nueva Transacción';
    document.getElementById('inputFecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('tipoGasto').checked = true;
    modalObj.show();
}

function editar(data) {
    // Cargar datos en el formulario
    document.getElementById('inputId').value = data.id;
    document.getElementById('modalTitulo').textContent = 'Editar Transacción';
    
    document.getElementById('inputFecha').value = data.fecha;
    document.getElementById('inputDescripcion').value = data.descripcion;
    document.getElementById('inputCategoria').value = data.categoria_id;
    
    // Manejar importe positivo/negativo
    let valor = parseFloat(data.importe);
    if (valor < 0) {
        document.getElementById('tipoGasto').checked = true;
        document.getElementById('inputImporte').value = Math.abs(valor);
    } else {
        document.getElementById('tipoIngreso').checked = true;
        document.getElementById('inputImporte').value = valor;
    }
    
    modalObj.show();
}
</script>

<?php include 'includes/footer.php'; ?>