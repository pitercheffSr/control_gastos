<?php

/**
 * ============================================================
 * dashboard.php
 * ============================================================
 * Vista principal del Dashboard de ControlGastos
 *
 * RESPONSABILIDADES DE ESTE ARCHIVO:
 * - Verificar que el usuario está autenticado
 * - Definir la estructura HTML del dashboard
 * - Proveer los contenedores (IDs) que el JS rellenará
 *
 * NO DEBE HACER:
 * - Consultas SQL
 * - Lógica de negocio
 * - Cálculos
 * - Acceso directo a datos
 *
 * Toda la lógica vive en:
 * - controllers/DashboardRouter.php
 * - assets/js/dashboard.js
 * ============================================================
 */

include_once "config.php";

/* ------------------------------------------------------------
   Seguridad: usuario autenticado
------------------------------------------------------------ */
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

	<!-- =========================================================
	     Framework CSS (Spectre.css)
	     ========================================================= -->
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">

	<!-- =========================================================
	     CSS propio del proyecto
	     ========================================================= -->
	<link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>

	<div class="app-root">

		<!-- =========================================================
	     SIDEBAR / MENÚ LATERAL
	     ========================================================= -->
		<aside class="sidebar">
			<div class="sidebar-header">
				<h3 class="brand">ControlGastos</h3>
			</div>

			<nav class="menu">
				<a class="menu-item is-active" href="dashboard.php">
					<i class="icon icon-home"></i> Dashboard
				</a>

				<a class="menu-item" href="transacciones.php">
					<i class="icon icon-list"></i> Transacciones
				</a>

				<a class="menu-item" href="gestion_categorias.php">
					<i class="icon icon-folder"></i> Categorías
				</a>

				<a class="menu-item" href="informes.php">
					<i class="icon icon-chart"></i> Informes
				</a>

				<a class="menu-item" href="config.php">
					<i class="icon icon-cog"></i> Configuración
				</a>

				<div class="menu-spacer"></div>

				<a class="menu-item" href="logout.php">
					<i class="icon icon-exit"></i> Cerrar sesión
				</a>
			</nav>
		</aside>

		<!-- =========================================================
	     CONTENIDO PRINCIPAL
	     ========================================================= -->
		<main class="main-content">

			<!-- ===================== TOPBAR ===================== -->
			<header class="topbar">
				<div class="topbar-left">
					<button id="btnToggleSidebar"
						class="btn btn-link btn-action"
						title="Mostrar / ocultar menú">
						☰
					</button>
					<h2>Dashboard</h2>
				</div>
			</header>

			<!-- =====================================================
		     KPIs SUPERIORES
		     Estos valores se rellenan desde dashboard.js
		     ===================================================== -->
			<section class="container grid-lg">
				<div class="columns">

					<div class="column col-3 col-sm-6">
						<div class="card kpi">
							<div class="card-header">
								<div class="card-title h6 text-gray">Ingresos (mes)</div>
							</div>
							<div class="card-body">
								<div id="kpi-ingresos" class="kpi-value">—</div>
							</div>
						</div>
					</div>

					<div class="column col-3 col-sm-6">
						<div class="card kpi">
							<div class="card-header">
								<div class="card-title h6 text-gray">Gastos (mes)</div>
							</div>
							<div class="card-body">
								<div id="kpi-gastos" class="kpi-value">—</div>
							</div>
						</div>
					</div>

					<div class="column col-3 col-sm-6">
						<div class="card kpi">
							<div class="card-header">
								<div class="card-title h6 text-gray">Balance</div>
							</div>
							<div class="card-body">
								<div id="kpi-balance" class="kpi-value">—</div>
							</div>
						</div>
					</div>

					<div class="column col-3 col-sm-6">
						<div class="card kpi">
							<div class="card-header">
								<div class="card-title h6 text-gray">% gasto</div>
							</div>
							<div class="card-body">
								<div id="kpi-porcentaje" class="kpi-value">—</div>
							</div>
						</div>
					</div>

				</div>
			</section>

			<!-- =====================================================
		     GRÁFICO DONUT 50 / 30 / 20
		     El canvas es obligatorio para Chart.js
		     ===================================================== -->
			<section class="container grid-lg">
				<div class="columns">
					<div class="column col-12">
						<div class="card">
							<div class="card-header">
								<div class="card-title h5">
									Distribución 50 / 30 / 20
								</div>
							</div>
							<div class="card-body" style="max-width:420px; margin:auto;">
								<canvas id="chart503020"></canvas>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- =====================================================
		     TABLA DE MOVIMIENTOS
		     ===================================================== -->
			<section class="container grid-lg">
				<div class="columns">
					<div class="column col-12">
						<div class="card">
							<div class="card-header">
								<div class="card-title h5">Movimientos</div>
								<div class="card-subtitle text-gray">
									Historial de transacciones
								</div>
							</div>

							<div class="card-body">
								<div class="table-responsive">
									<table class="table table-striped" id="transactionsTable">
										<thead>
											<tr>
												<th>Fecha</th>
												<th>Descripción</th>
												<th>Categoría</th>
												<th>Subcat.</th>
												<th>Importe</th>
												<th>Tipo</th>
											</tr>
										</thead>
										<tbody></tbody>
									</table>
								</div>
							</div>

						</div>
					</div>
				</div>
			</section>

		</main>
	</div>

	<!-- =========================================================
     SCRIPTS
     ========================================================= -->

	<!-- Script principal del dashboard (KPIs + donut) -->
	<script type="module" src="assets/js/dashboard.js"></script>

	<!-- Librería Chart.js (necesaria para el donut) -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>

</html>
