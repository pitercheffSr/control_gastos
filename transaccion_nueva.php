<?php

include_once "config.php";
include_once "db.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nueva transacción — ControlGastos</title>

<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">

<style>
.form-wrapper { max-width: 700px; margin: auto; margin-top: 40px; }
</style>

</head>
<body>

<div class="app-root">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-header"><h3 class="brand">ControlGastos</h3></div>
      <nav class="menu">
        <a class="menu-item" href="dashboard.php"><i class="icon icon-home"></i> Dashboard</a>
        <a class="menu-item" href="transacciones.php"><i class="icon icon-list"></i> Transacciones</a>
        <a class="menu-item" href="categorias.php"><i class="icon icon-folder"></i> Categorías</a>
        <a class="menu-item" href="informes.php"><i class="icon icon-chart"></i> Informes</a>
        <a class="menu-item" href="config.php"><i class="icon icon-cog"></i> Configuración</a>
        <div class="menu-spacer"></div>
        <a class="menu-item" href="logout.php"><i class="icon icon-exit"></i> Cerrar sesión</a>
      </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

      <header class="topbar">
        <button id="btnToggleSidebar" class="btn btn-link btn-action">☰</button>
        <h2>Nueva transacción</h2>
      </header>

      <div class="form-wrapper">

        <div class="card">
          <div class="card-header">
            <div class="card-title h5">Registrar transacción</div>
          </div>

          <div class="card-body">

            <!-- FORMULARIO -->
            <form id="formNuevaFull">

              <div class="form-group">
                <label>Fecha</label>
                <input type="date" class="form-input" id="f_fecha" required>
              </div>

              <div class="form-group">
                <label>Descripción</label>
                <input class="form-input" id="f_descripcion" placeholder="Opcional">
              </div>

              <div class="form-group">
                <label>Importe</label>
                <input type="number" step="0.01" class="form-input" id="f_monto" required>
              </div>

              <div class="form-group">
                <label>Tipo</label>
                <select id="f_tipo" class="form-select" required>
                  <option value="gasto">Gasto</option>
                  <option value="ingreso">Ingreso</option>
                </select>
              </div>

              <div class="form-group">
                <label>Categoría</label>
                <select id="f_categoria" class="form-select"></select>
              </div>

              <div class="form-group">
                <label>Subcategoría</label>
                <select id="f_subcategoria" class="form-select"></select>
              </div>

              <div class="form-group">
                <label>Sub-subcategoría</label>
                <select id="f_subsub" class="form-select"></select>
              </div>

            </form>

          </div>

          <div class="card-footer">
            <button class="btn btn-primary" id="btnGuardarFull">Guardar</button>
            <a href="transacciones.php" class="btn btn-link">Cancelar</a>
          </div>

        </div>

      </div>

    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split("T")[0];
    const inp = document.getElementById("f_fecha");
    if (inp) inp.value = today;
});
</script>

<script src="assets/js/transacciones_form.js"></script>


</body>
</html>
