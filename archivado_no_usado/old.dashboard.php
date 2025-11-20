<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
include 'includes/header.php';
include 'includes/conexion.php';

$id_usuario = $_SESSION['usuario_id'];

// Consultar ingresos totales
$sql_ingresos = "SELECT SUM(monto) as ingresos FROM transacciones WHERE id_usuario = ? AND tipo = 'ingreso'";
$stmt_ingresos = $conexion->prepare($sql_ingresos);
$stmt_ingresos->bind_param("i", $id_usuario);
$stmt_ingresos->execute();
$resultado_ingresos = $stmt_ingresos->get_result();
$ingresos = $resultado_ingresos->fetch_assoc()['ingresos'] ?? 0;
$stmt_ingresos->close();

// Consultar gastos totales
$sql_gastos = "SELECT SUM(monto) as gastos FROM transacciones WHERE id_usuario = ? AND tipo = 'gasto'";
$stmt_gastos = $conexion->prepare($sql_gastos);
$stmt_gastos->bind_param("i", $id_usuario);
$stmt_gastos->execute();
$resultado_gastos = $stmt_gastos->get_result();
$gastos = $resultado_gastos->fetch_assoc()['gastos'] ?? 0;
$stmt_gastos->close();

$balance = $ingresos - $gastos;

// Calcular gastos por categoría
$gastos_por_categoria = [];
$sql_categorias = "SELECT categoria, SUM(monto) as total FROM transacciones WHERE id_usuario = ? AND tipo = 'gasto' GROUP BY categoria";
$stmt_categorias = $conexion->prepare($sql_categorias);
$stmt_categorias->bind_param("i", $id_usuario);
$stmt_categorias->execute();
$resultado_categorias = $stmt_categorias->get_result();
while ($fila = $resultado_categorias->fetch_assoc()) {
    $gastos_por_categoria[$fila['categoria']] = $fila['total'];
}
$stmt_categorias->close();

// Calcular presupuestos según la regla 50/30/20
$presupuesto_50 = $ingresos * 0.50;
$presupuesto_30 = $ingresos * 0.30;
$presupuesto_20 = $ingresos * 0.20;

// Obtener transacciones recientes
$sql_recientes = "SELECT * FROM transacciones WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 5";
$stmt_recientes = $conexion->prepare($sql_recientes);
$stmt_recientes->bind_param("i", $id_usuario);
$stmt_recientes->execute();
$resultado_recientes = $stmt_recientes->get_result();
?>

<div class="row">
    <!-- Columna principal (izquierda) -->
    <div class="col-md-8">
        <h1 class="mb-4">Panel de Control</h1>
        
        <!-- Tarjeta de Resumen de Balance -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between mb-3">
                <h4>Balance Actual:</h4>
                <h4><?php echo number_format($balance, 2); ?> €</h4>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span>Ingresos:</span>
                <span class="text-success">+<?php echo number_format($ingresos, 2); ?> €</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Gastos:</span>
                <span class="text-danger">-<?php echo number_format($gastos, 2); ?> €</span>
            </div>
        </div>

        <!-- Tarjeta de Desglose 50/30/20 -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">Desglose 50/30/20</h4>
            
            <h5>50% - Necesidades</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, (($gastos_por_categoria['50'] ?? 0) / $presupuesto_50) * 100); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['50'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_50; ?>">
                    <?php echo number_format($gastos_por_categoria['50'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_50, 2); ?> €
                </div>
            </div>

            <h5>30% - Deseos</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, (($gastos_por_categoria['30'] ?? 0) / $presupuesto_30) * 100); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['30'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_30; ?>">
                    <?php echo number_format($gastos_por_categoria['30'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_30, 2); ?> €
                </div>
            </div>

            <h5>20% - Ahorro/Deudas</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, (($gastos_por_categoria['20'] ?? 0) / $presupuesto_20) * 100); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['20'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_20; ?>">
                    <?php echo number_format($gastos_por_categoria['20'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_20, 2); ?> €
                </div>
            </div>
        </div>

        <!-- Tarjeta de Transacciones Recientes -->
        <div class="card p-4 mt-4">
            <h4>Transacciones Recientes</h4>
            <ul class="list-group">
                <?php if ($resultado_recientes->num_rows > 0): ?>
                    <?php while ($transaccion = $resultado_recientes->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span><?php echo $transaccion['descripcion']; ?></span>
                                <small class="text-muted d-block"><?php echo $transaccion['fecha']; ?></small>
                            </div>
                            <span class="badge bg-<?php echo ($transaccion['tipo'] == 'ingreso') ? 'success' : 'danger'; ?> rounded-pill">
                                <?php echo ($transaccion['tipo'] == 'ingreso' ? '+' : '-') . number_format($transaccion['monto'], 2); ?> €
                            </span>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item text-center text-muted">Aún no hay transacciones registradas.</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <?php $stmt_recientes->close(); ?>
    </div>
    
    <!-- Columna del formulario (derecha) -->
    <div class="col-md-4">
        <h4 class="mt-4 mt-md-0">Registrar Transacción</h4>
        <div class="card p-4">
            <form action="procesar_transaccion.php" method="POST">
                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="ingreso">Ingreso</option>
                        <option value="gasto">Gasto</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría (50/30/20)</label>
                    <select class="form-select" id="categoria" name="categoria" required>
                        <option value="50">50% (Necesidades)</option>
                        <option value="30">30% (Deseos)</option>
                        <option value="20">20% (Ahorro/Deudas)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                </div>
                <div class="mb-3">
                    <label for="monto" class="form-label">Monto</label>
                    <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrar</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
