<?php
// 🔍 Paso 1: Mostrar errores para depuración temporal
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'db.php';
define('FROM_DASHBOARD', true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Control de Gastos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="css/estilos.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <h1 class="mb-4 text-center">Control de Gastos 50/30/20</h1>

  <!-- FILTROS -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form id="filtroForm" class="row g-3 align-items-center">
        <div class="col-md-3">
          <label for="fechaInicio" class="form-label">Desde</label>
          <input type="date" class="form-control" id="fechaInicio" name="fechaInicio">
        </div>
        <div class="col-md-3">
          <label for="fechaFin" class="form-label">Hasta</label>
          <input type="date" class="form-control" id="fechaFin" name="fechaFin">
        </div>
        <div class="col-md-3">
          <label for="filtroPeriodo" class="form-label">Periodo</label>
          <select id="filtroPeriodo" class="form-select">
            <option value="dia">Diario</option>
            <option value="semana">Semanal</option>
            <option value="quincena">Quincenal</option>
            <option value="mes">Mensual</option>
            <option value="anio">Anual</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Aplicar Filtros</button>
        </div>
      </form>
    </div>
  </div>

<!-- 🔹 FORMULARIO PARA NUEVA TRANSACCIÓN -->
<div class="card shadow-sm mb-4">
  <div class="card-header bg-primary text-white">
    <i class="bi bi-plus-circle me-2"></i> Añadir Transacción
  </div>
  <div class="card-body">
    <form id="form-transaccion" method="POST" action="insert.php">
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
          <label for="categoria" class="form-label">Categoría</label>
          <select id="categoria" name="categoria" class="form-select" required></select>
        </div>
        <div class="col-md-3">
          <label for="subcategoria" class="form-label">Subcategoría</label>
          <select id="subcategoria" name="subcategoria" class="form-select"></select>
        </div>
        <div class="col-md-3">
          <label for="subsubcategoria" class="form-label">Sub-Subcategoría</label>
          <select id="subsubcategoria" name="subsubcategoria" class="form-select"></select>
        </div>
      </div>
        <div class="text-end mb-3">
            <a href="categorias.php" class="btn btn-outline-secondary">
              <i class="bi bi-tags"></i> Gestionar Categorías
            </a>
        </div>


      <div class="mt-3 text-end">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save me-1"></i> Guardar Transacción
        </button>
      </div>
    </form>
  </div>
</div>

  <!-- TABLA DE TRANSACCIONES -->
  <div class="card shadow-sm">
    <div class="card-header bg-secondary text-white">Historial de Transacciones</div>
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>Subcategoría</th>
            <th>Sub-Subcategoría</th>
            <th>Importe (€)</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-transacciones">
                <?php include 'ftch.php'; ?>
        </tbody>     
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>
