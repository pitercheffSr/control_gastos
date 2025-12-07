<?php
include_once "config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Control Gastos — Dashboard</title>
  <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
  <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
  <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
  <div class="app-root">
    <aside class="sidebar">
      <div class="sidebar-header"><h3 class="brand">ControlGastos</h3></div>
      <nav class="menu">
          <a class="menu-item is-active" href="dashboard.php"><i class="icon icon-home"></i> Dashboard</a>
          <a class="menu-item" href="transacciones.php"><i class="icon icon-list"></i> Transacciones</a>
          <a class="menu-item" href="categorias.php"><i class="icon icon-folder"></i> Categorías</a>
          <a class="menu-item" href="informes.php"><i class="icon icon-chart"></i> Informes</a>
          <a class="menu-item" href="config.php"><i class="icon icon-cog"></i> Configuración</a>
          <div class="menu-spacer"></div>
          <a class="menu-item" href="logout.php"><i class="icon icon-exit"></i> Cerrar sesión</a>
        </nav>

    </aside>

    <main class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <button id="btnToggleSidebar" class="btn btn-link btn-action" title="Toggle sidebar">☰</button>
          <h2>Dashboard</h2>
        </div>
        <div class="topbar-right">
          <div class="input-group input-inline">
            <select id="filter_type" class="form-select">
              <option value="month">Mes</option>
              <option value="week">Semana</option>
              <option value="day">Día</option>
              <option value="range">Rango</option>
              <option value="all">Todos</option>
            </select>
            <input id="filter_date" type="date" class="form-input" style="display:none">
            <input id="filter_date_from" type="date" class="form-input" style="display:none">
            <input id="filter_date_to" type="date" class="form-input" style="display:none">
            <select id="filter_month" class="form-select" style="width:120px"></select>
            <select id="filter_year" class="form-select" style="width:100px"></select>
            <button id="btnApplyFilter" class="btn btn-primary">Aplicar</button>
          </div>
        </div>
      </header>

      <section class="container grid-lg">
        <div class="columns">
          <div class="column col-3">
            <div class="card card-stats"><div class="card-body"><p class="card-title">Ingresos</p><p class="card-value" id="t_ingresos">0 €</p></div></div>
            <div class="card card-stats"><div class="card-body"><p class="card-title">Gastos</p><p class="card-value" id="t_gastos">0 €</p></div></div>
            <div class="card card-stats"><div class="card-body"><p class="card-title">Saldo</p><p class="card-value" id="t_saldo">0 €</p></div></div>
          </div>

          <div class="column col-9">
            <div class="card">
              <div class="card-header">
                <div class="card-title h5">Movimientos</div>
                <div class="card-subtitle text-gray">Historial de transacciones</div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped" id="transactionsTable">
                    <thead><tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Subcat.</th><th>Importe</th><th>Tipo</th></tr></thead>
                    <tbody></tbody>
                  </table>
                </div>
                <div class="paginator mt-2">
                  <button id="prevPage" class="btn btn-sm">Anterior</button>
                  <span id="pageInfo">Página 1</span>
                  <button id="nextPage" class="btn btn-sm">Siguiente</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section>
    </main>
  </div>
  <script>
console.log("TEST INLINE: HTML SI EJECUTA JS");
</script>

  <script src="assets/js/dashboard.js"></script>
</body>
</html>
