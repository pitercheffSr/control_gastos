<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

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
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
	<link rel="stylesheet" href="assets/css/dashboard.css">
	<script>
		window.csrf_token = "<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>";
	</script>
</head>

<body>

	<div class="app-root">
		<aside class="sidebar" id="mainSidebar">
			<div class="sidebar-header">
				<h3 class="brand">ControlGastos</h3>
			</div>
			<nav class="menu">
				<a class="menu-item" href="dashboard.php"><i class="icon icon-home"></i> Dashboard</a>
				<a class="menu-item is-active" href="transacciones.php"><i class="icon icon-list"></i> Transacciones</a>
				<a class="menu-item" href="gestion_categorias.php"><i class="icon icon-folder"></i> Categorías</a>
				<a class="menu-item" href="logout.php"><i class="icon icon-exit"></i> Cerrar sesión</a>
			</nav>
		</aside>

		<main class="main-content">
			<header class="topbar">
				<div class="topbar-section">
					<button id="toggleMenu" class="btn btn-link btn-action btn-lg">
						<i class="icon icon-menu"></i>
					</button>
					<h2 style="display: inline-block; margin-left: 10px; vertical-align: middle;">Transacciones</h2>
				</div>
				<div class="topbar-section">
					<button id="btnNuevaTransaccion" class="btn btn-primary">+ Nueva</button>
				</div>
			</header>

			<section class="container grid-lg">
				<div class="card">
					<div class="card-body">
						<div class="table-container">
							<table class="table table-striped table-hover" id="tablaTransacciones">
								<thead>
									<tr>
										<th>Fecha</th>
										<th>Descripción</th>
										<th>Categoría</th>
										<th>Subcat.</th>
										<th>Sub-subcat.</th>
										<th>Importe</th>
										<th>Tipo</th>
										<th style="text-align:right;">Acciones</th>
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

	<div id="overlayPanel"></div>

	<div id="panelEditar" class="side-panel">
		<div class="panel-header">
			<h3 id="panelTitulo">Editar transacción</h3>
			<button id="cerrarPanel" class="btn btn-clear" type="button"></button>
		</div>
		<div class="panel-body">
			<form id="formEditar">
				<div class="form-group">
					<label class="form-label">Fecha</label>
					<input id="e_fecha" type="date" class="form-input">
				</div>
				<div class="form-group">
					<label class="form-label">Descripción</label>
					<input id="e_desc" type="text" class="form-input">
				</div>
				<div class="form-group">
					<label class="form-label">Importe</label>
					<input id="e_monto" type="number" step="0.01" class="form-input">
				</div>
				<div class="form-group">
					<label class="form-label">Tipo</label>
					<select id="e_tipo" class="form-select">
						<option value="gasto">Gasto</option>
						<option value="ingreso">Ingreso</option>
					</select>
				</div>
				<div class="form-group">
					<label class="form-label">Categoría</label>
					<select id="e_cat" class="form-select"></select>
				</div>
				<div class="form-group">
					<label class="form-label">Subcategoría</label>
					<select id="e_subcat" class="form-select"></select>
				</div>
				<div class="form-group">
					<label class="form-label">Sub-subcategoría</label>
					<select id="e_subsub" class="form-select"></select>
				</div>
				<div class="panel-footer mt-2">
					<button id="guardarCambios" type="button" class="btn btn-primary btn-block">Guardar</button>
					<button id="cancelarEdicion" type="button" class="btn btn-link btn-block">Cancelar</button>
				</div>
			</form>
		</div>
	</div>

	<?php require_once __DIR__ . '/includes/csrf_js.php'; ?>
	<script src="assets/js/transacciones.js"></script>
	<script src="assets/js/transacciones_editar.js"></script>
</body>

</html>
