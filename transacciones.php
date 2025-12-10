<?php
require_once "config.php";
require_once "db.php";

// Seguridad: si no hay usuario → login
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

<!-- CSS de Spectre -->
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">

<!-- Nuestro CSS -->
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

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <h2>Transacciones</h2>
            <a href="transaccion_nueva.php" class="btn btn-primary">+ Nueva</a>
        </header>

        <section class="container grid-lg">

            <div class="card">
                <div class="card-header">
                    <div class="card-title h5">Lista de transacciones</div>
                    <div class="card-subtitle text-gray">Historial completo</div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaTransacciones">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Subcat.</th>
                                    <th>Importe</th>
                                    <th>Tipo</th>
                                    <th style="width: 80px; text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
            </div>

        </section>

    </main>
</div>

<!-- Overlay -->
<div id="overlayPanel"></div>

<!-- PANEL LATERAL EDITAR -->
<div id="panelEditar">
    <div class="panel-header">
        <h3>Editar transacción</h3>
        <button id="cerrarPanel" type="button"><i class="icon icon-cross"></i></button>
    </div>

    <form id="formEditar">

        <label>Fecha</label>
        <input id="e_fecha" type="date" class="form-input">

        <label>Descripción</label>
        <input id="e_desc" type="text" class="form-input">

        <label>Importe</label>
        <input id="e_monto" type="number" step="0.01" class="form-input">

        <label>Tipo</label>
        <select id="e_tipo" class="form-select">
            <option value="ingreso">Ingreso</option>
            <option value="gasto">Gasto</option>
        </select>

        <label>Categoría</label>
        <select id="e_cat" class="form-select"></select>

        <label>Subcategoría</label>
        <select id="e_subcat" class="form-select"></select>

        <label>Sub-subcategoría</label>
        <select id="e_subsub" class="form-select"></select>

        <div class="panel-actions">
            <button id="guardarCambios" type="button" class="btn btn-primary">Guardar</button>
            <button id="cancelarEdicion" type="button" class="btn btn-link">Cancelar</button>
        </div>

    </form>
</div>

<!-- JS -->
<script src="assets/js/transacciones.js"></script>
<script src="assets/js/transacciones_editar.js"></script>

</body>
</html>
