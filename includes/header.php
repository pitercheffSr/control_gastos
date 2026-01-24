<!DOCTYPE html>
<html lang="es">

<head>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Control de Gastos 50/30/20</title>
	<!-- Enlace a Spectre.css -->
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
	<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
	<style>
		.g-3 {
			gap: 1rem;
		}
	</style>
	<script>
		window.CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
	</script>

</head>

<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container">
			<a class="navbar-brand" href="/control_gastos/dashboard.php">Control 50/30/20</a>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link" href="/control_gastos/gestion_categorias.php">Gestionar Categorias</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/control_gastos/logout.php">Cerrar Sesión</a>
					</li>
				</ul>
			</div>
		</div>
	</nav>
	<div class="container mt-5">
</body>
