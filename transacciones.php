<?php
include_once "config.php";
include_once "db.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Transacciones — ControlGastos</title>

<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>

<div class="app-root">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header"><h3 class="brand">ControlGastos</h3></div>
    <nav class="menu">
      <a class="menu-item" href="dashboard.php"><i class="icon icon-home"></i> Dashboard</a>
      <a class="menu-item is-active" href="transacciones.php"><i class="icon icon-list"></i> Transacciones</a>
      <a class="menu-item" href="categorias.php"><i class="icon icon-folder"></i> Categorías</a>
      <a class="menu-item" href="informes.php"><i class="icon icon-chart"></i> Informes</a>
      <a class="menu-item" href="config.php"><i class="icon icon-cog"></i> Configuración</a>
      <div class="menu-spacer"></div>
      <a class="menu-item" href="logout.php"><i class="icon icon-exit"></i> Cerrar sesión</a>
    </nav>
  </aside>

  <!-- MAIN AREA -->
  <main class="main-content">
    
    <header class="topbar">
      <h2>Transacciones</h2>
      <a href="transaccion_nueva.php" class="btn btn-primary" style="margin-left:auto;">+ Nueva</a>
    </header>

    <section class="container grid-lg">

      <div class="columns">
        <div class="column col-12">

          <div class="card">
            <div class="card-header">
              <div class="card-title h5">Lista de transacciones</div>
              <div class="card-subtitle text-gray">Transacciones del usuario</div>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-striped" id="tablaTransacciones">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Categoria</th>
                                <th>Subcat.</th>
                                <th>Importe</th>
                                <th>Tipo</th>
                                <th style="width: 80px; text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>

          </div> <!-- card -->

        </div>
      </div>

    </section>

  </main>
</div>
<!-- Panel de edición deslizante -->
<div id="panelEditar" class="slide-panel">
    <div class="panel-content">
        <!-- Botón cerrar panel -->          
      <button id="cerrarPanel" type="button" class="btn btn-link">
          <i class="icon icon-cross"></i> Cerrar
      </button>

        <!-- Formulario de edición -->
        <form id="formEditar">
            <!-- fecha -->
            <label>Fecha</label>
            <input type="date" class="form-input" id="e_fecha">
            <!-- descripción -->
            <label>Descripción</label>
            <input type="text" class="form-input" id="e_desc">
            <!-- monto -->
            <label>Monto (€)</label>
            <input type="number" step="0.01" class="form-input" id="e_monto">
            <!-- tipo -->
            <label>Tipo</label>
            <select id="e_tipo" class="form-select">
                <option value="ingreso">Ingreso</option>
                <option value="gasto">Gasto</option>
            </select>
            <!-- categoría -->
            <label>Categoría</label>
            <select id="e_cat" class="form-select"></select>
            <!-- subcategoría --> 
            <label>Subcategoría</label>
            <select id="e_subcat" class="form-select"></select>
            <!-- sub-subcategoría -->
            <label>Sub-subcategoría</label>
            <select id="e_subsub" class="form-select"></select>
            <!-- guardar cambios -->
            <button id="guardarCambios" type="button" class="btn btn-primary mt-2">
                <i class="icon icon-save"></i> Guardar cambios
            </button>
            <!-- cancelar edicion -->
            <button id="cancelarEdicion" type="button" class="btn btn-link mt-2">
                Cancelar
            </button>

        </form>

    </div>
</div>
<script src="assets/js/transacciones.js"></script>
<script src="assets/js/transacciones_editar.js"></script>

</body>
</html>
