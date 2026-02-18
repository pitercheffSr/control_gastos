<?php
require_once 'config.php';
require_once 'controllers/DashboardController.php';

// ESTO SOLUCIONA TU ERROR DE REDIRECCIÓN:
checkAuth(); // Usa la función correcta del nuevo sistema

$dash = new DashboardController($pdo);

// --- PROCESAR FILTROS ---
$rango = $_GET['rango'] ?? 'defecto';
$cat_padre = $_GET['cat_padre'] ?? '';
$f_ini = $_GET['fecha_ini'] ?? '';
$f_fin = $_GET['fecha_fin'] ?? '';

$datos = $dash->obtenerDatos([
    'rango' => $rango, 
    'cat_padre' => $cat_padre,
    'fecha_ini' => $f_ini,
    'fecha_fin' => $f_fin
]);

// Preparar datos para Chart.js
$dataJS = [
    $datos['grupos']['necesidad'],
    $datos['grupos']['deseo'],
    $datos['grupos']['ahorro']
];
$total = $datos['total_gastos'] > 0 ? $datos['total_gastos'] : 1; // Evitar división por cero

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Resumen Financiero</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="transacciones.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-plus"></i> Nuevo Movimiento
        </a>
    </div>
</div>

<div class="card mb-4 border-0 shadow-sm bg-white">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="small text-muted fw-bold">Periodo</label>
                <select name="rango" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="defecto" <?= $rango=='defecto'?'selected':'' ?>>Últimos 15 (Defecto)</option>
                    <option value="mes_actual" <?= $rango=='mes_actual'?'selected':'' ?>>Este Mes Actual</option>
                    <option value="3_meses" <?= $rango=='3_meses'?'selected':'' ?>>Últimos 3 Meses</option>
                    <option value="custom" <?= $rango=='custom'?'selected':'' ?>>Personalizado</option>
                </select>
            </div>
            
            <?php if($rango == 'custom'): ?>
            <div class="col-md-2">
                <label class="small text-muted">Desde</label>
                <input type="date" name="fecha_ini" value="<?= $f_ini ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $f_fin ?>" class="form-control form-control-sm">
            </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="small text-muted fw-bold">Grupo / Categoría</label>
                <select name="cat_padre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach($dash->obtenerCategoriasPadre() as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $cat_padre==$p['id']?'selected':'' ?>>
                            <?= $p['nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-4 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title text-center text-muted mb-3">REGLA 50 / 30 / 20</h6>
                <div style="height: 220px; position: relative;">
                    <canvas id="reglaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 h-100">
            <div class="col-md-6">
                <div class="card bg-primary text-white h-100 border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 fw-bold">INGRESOS</small>
                                <h3 class="fw-bold mb-0"><?= number_format($datos['ingresos'], 2) ?> €</h3>
                            </div>
                            <i class="fas fa-arrow-up fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-secondary text-white h-100 border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 fw-bold">GASTOS TOTALES</small>
                                <h3 class="fw-bold mb-0"><?= number_format($datos['total_gastos'], 2) ?> €</h3>
                            </div>
                            <i class="fas fa-arrow-down fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="row text-center align-items-center h-100">
                            <div class="col-4 border-end">
                                <small class="text-muted fw-bold d-block">NECESIDADES</small>
                                <h4 class="text-necesidad mb-0"><?= number_format($datos['grupos']['necesidad'], 2) ?> €</h4>
                                <small class="text-muted"><?= round(($datos['grupos']['necesidad']/$total)*100) ?>%</small>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted fw-bold d-block">DESEOS</small>
                                <h4 class="text-deseo mb-0"><?= number_format($datos['grupos']['deseo'], 2) ?> €</h4>
                                <small class="text-muted"><?= round(($datos['grupos']['deseo']/$total)*100) ?>%</small>
                            </div>
                            <div class="col-4">
                                <small class="text-muted fw-bold d-block">AHORRO</small>
                                <h4 class="text-ahorro mb-0"><?= number_format($datos['grupos']['ahorro'], 2) ?> €</h4>
                                <small class="text-muted"><?= round(($datos['grupos']['ahorro']/$total)*100) ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-primary">Últimos Movimientos</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-4">Fecha</th>
                    <th scope="col">Categoría</th>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="text-end pe-4">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($datos['transacciones'] as $t): ?>
                <tr>
                    <td class="ps-4 text-muted"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                    <td>
                        <span class="badge rounded-pill fw-normal" style="background-color: <?= $t['color'] ?>; color: #fff;">
                            <?= $t['cat_nombre'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($t['descripcion']) ?></td>
                    <td class="text-end pe-4 fw-bold <?= $t['importe'] < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($t['importe'], 2) ?> €
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($datos['transacciones'])): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">No hay movimientos en este periodo</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const ctx = document.getElementById('reglaChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Necesidades', 'Deseos', 'Ahorro'],
            datasets: [{
                data: <?= json_encode($dataJS) ?>,
                backgroundColor: ['#e74a3b', '#f6c23e', '#1cc88a'], // Rojo, Amarillo, Verde
                hoverOffset: 4,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            },
            cutout: '75%' // Grosor del donut
        }
    });
</script>

<?php include 'includes/footer.php'; ?>