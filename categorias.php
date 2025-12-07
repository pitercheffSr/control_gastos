<?php
include_once "config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Categorías — ControlGastos</title>
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head><body>
<div class="app-root">
  <aside class="sidebar">
    <div class="sidebar-header"><h3 class="brand">ControlGastos</h3></div>
    <nav class="menu">
      <a class="menu-item" href="dashboard.php"><i class="icon icon-home"></i> Dashboard</a>
      <a class="menu-item" href="transacciones.php"><i class="icon icon-list"></i> Transacciones</a>
      <a class="menu-item is-active" href="categorias.php"><i class="icon icon-folder"></i> Categorías</a>
      <a class="menu-item" href="informes.php"><i class="icon icon-chart"></i> Informes</a>
      <a class="menu-item" href="config.php"><i class="icon icon-cog"></i> Configuración</a>
      <div class="menu-spacer"></div>
      <a class="menu-item" href="logout.php"><i class="icon icon-exit"></i> Cerrar sesión</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="topbar"><h2>Categorías</h2></header>
    <section class="container grid-lg">
      <div class="columns"><div class="column col-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title h5">Gestión de categorías</div>
            <div class="card-subtitle text-gray">En construcción</div>
          </div>
          <div class="card-body">
            <p>Aquí podrás gestionar categorías, subcategorías y sub-subcategorías.</p>
          </div>
        </div>
      </div></div>
    </section>
  </main>
</div>
</body>
</html>
