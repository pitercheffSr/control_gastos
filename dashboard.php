<?php
// dashboard.php - Rediseño (Opción A)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php'; // debe definir $conn (PDO)
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: views/login.php');
    exit;
}
$uid = (int)$_SESSION['usuario_id'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Control de Gastos — Dashboard</title>

  <!-- local assets -->
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/icons/bootstrap-icons.css" rel="stylesheet">
  <link href="css/dashboard_custom.css" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">

<div class="container-fluid vh-100 py-3">
  <div class="row h-100 gx-3">

    <!-- IZQUIERDA 70% -->
    <main class="col-lg-8 col-md-12 mb-3">
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <div><i class="bi bi-plus-circle me-2"></i> Añadir transacción</div>
          <div class="small text-white-50">Usuario: <?= htmlentities($uid) ?></div>
        </div>

        <div class="card-body">
          <!-- FILTROS RÁPIDOS -->
          <form id="filtros" class="row g-2 mb-3">
            <div class="col-auto">
              <select id="filtroTipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="gasto">Gasto</option>
                <option value="ingreso">Ingreso</option>
              </select>
            </div>
            <div class="col-auto">
              <select id="filtroPeriodo" class="form-select form-select-sm">
                <option value="todo">Todo</option>
                <option value="hoy">Hoy</option>
                <option value="semana">Esta semana</option>
                <option value="mes">Este mes</option>
                <option value="anio">Este año</option>
                <option value="rango">Rango...</option>
              </select>
            </div>
            <div class="col-auto d-none" id="rangoFechas">
              <input type="date" id="f_inicio" class="form-control form-control-sm">
              <input type="date" id="f_fin" class="form-control form-control-sm mt-1">
            </div>
            <div class="col-auto ms-auto">
              <button id="aplicarFiltros" type="button" class="btn btn-sm btn-primary">Aplicar</button>
              <button id="resetFiltros" type="button" class="btn btn-sm btn-outline-secondary">Reset</button>
            </div>
          </form>

          <form id="form-transaccion" method="POST" action="insert.php" class="row g-3">
            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
            <!-- dentro del form -->
            <div class="row g-3">
              <div class="col-md-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" id="fecha" name="fecha" class="form-control" required>
              </div>

              <div class="col-md-3">
                <label for="monto" class="form-label">Monto (€)</label>
                <input type="number" id="monto" name="monto" step="0.01" class="form-control" required>
              </div>

              <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select id="tipo" name="tipo" class="form-select" required>
                  <option value="">Selecciona...</option>
                  <option value="gasto">Gasto</option>
                  <option value="ingreso">Ingreso</option>
                </select>
              </div>

              <div class="col-md-3">
                <label for="categoria" class="form-label">Categoría</label>
                <select id="categoria" name="id_categoria" class="form-select"></select>
              </div>

              <div class="col-md-3">
                <label for="subcategoria" class="form-label">Subcategoría</label>
                <select id="subcategoria" name="subcategoria" class="form-select"></select>
              </div>

              <div class="col-md-3">
                <label for="subsubcategoria" class="form-label">Sub-Subcategoría</label>
                <select id="subsubcategoria" name="subsubcategoria" class="form-select"></select>
              </div>

              <div class="col-md-6">
                <label for="concepto" class="form-label">Concepto (texto)</label>
                <input type="text" id="concepto" name="concepto" class="form-control" placeholder="Ej: Pago nómina, Compra pan...">
              </div>
            </div>


            <div class="col-12 text-end">
              <a href="gestion_categorias.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-tags"></i> Gestionar categorías
              </a>
              <button type="submit" class="btn btn-success">Guardar transacción</button>
            </div>
          </form>
        </div>
      </div>

      <!-- GRÁFICAS -->
      <div class="row gx-3">
        <div class="col-md-6 mb-3">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-danger text-white">Gastos — por categoría</div>
            <div class="card-body">
              <canvas id="chartGastos" style="max-height:320px;"></canvas>
              <div id="leyendaGastos" class="mt-2 small"></div>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">Ingresos — por origen</div>
            <div class="card-body">
              <canvas id="chartIngresos" style="max-height:320px;"></canvas>
              <div id="leyendaIngresos" class="mt-2 small"></div>
            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- DERECHA 30% -->
    <aside class="col-lg-4 col-md-12">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
          <div>Historial</div>
          <div class="small text-white-50">Últimas movimientos</div>
        </div>
        <div class="card-body p-0 overflow-auto" style="max-height: calc(100vh - 180px);">
          <table class="table table-sm mb-0">
            <thead class="table-dark">
              <tr><th>Fecha</th><th>Descripción</th><th class="text-end">€</th></tr>
            </thead>
            <tbody id="tabla-transacciones">
              <?php
                // ftch.php imprime filas cuando FROM_DASHBOARD está definido
                define('FROM_DASHBOARD', true);
                include __DIR__ . '/ftch.php';
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </aside>

  </div>
</div>

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>
