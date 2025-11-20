<?php
// 🔍 Mostrar errores temporalmente
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
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/icons/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

  <h1 class="mb-4 text-center">Control de Gastos 50/30/20</h1>

  <!-- FORMULARIO PARA NUEVA TRANSACCIÓN -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <i class="bi bi-plus-circle me-2"></i> Añadir Transacción
    </div>

    <div class="card-body">

      <form id="form-transaccion" method="POST" action="insert.php">

        <div class="row g-3">

          <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" id="fecha" name="fecha" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Monto (€)</label>
            <input type="number" id="monto" name="monto" step="0.01" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <select id="categoria" name="categoria" class="form-select" required></select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Subcategoría</label>
            <select id="subcategoria" name="subcategoria" class="form-select"></select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Sub-Subcategoría</label>
            <select id="subsubcategoria" name="subsubcategoria" class="form-select"></select>
          </div>

        </div>

        <div class="text-end mt-3 mb-2">
          <a href="gestion_categorias.php" class="btn btn-outline-secondary">
            <i class="bi bi-tags"></i> Gestionar Categorías
          </a>
        </div>

        <div class="text-end mt-2">
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

<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// ==============================
//   CARGA DE CATEGORÍAS
// ==============================

document.addEventListener('DOMContentLoaded', () => {
  cargarCategorias();

  document.getElementById('categoria').addEventListener('change', cargarSubcategorias);
  document.getElementById('subcategoria').addEventListener('change', cargarSubsubcategorias);
});

function cargarCategorias() {
  fetch('load_categorias.php?nivel=categorias')
    .then(r => r.json())
    .then(data => {
      let sel = document.getElementById('categoria');
      sel.innerHTML = '<option value="">Selecciona...</option>';

      data.forEach(c => {
        sel.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
      });
    });
}

function cargarSubcategorias() {
  let id = document.getElementById('categoria').value;
  let sub = document.getElementById('subcategoria');
  let subsub = document.getElementById('subsubcategoria');

  sub.innerHTML = '<option value="">Selecciona...</option>';
  subsub.innerHTML = '<option value="">Selecciona...</option>';

  if (!id) return;

  fetch(`load_categorias.php?nivel=subcategorias&padre=${id}`)
    .then(r => r.json())
    .then(data => {
      data.forEach(s => {
        sub.innerHTML += `<option value="${s.id}">${s.nombre}</option>`;
      });
    });
}

function cargarSubsubcategorias() {
  let id = document.getElementById('subcategoria').value;
  let subsub = document.getElementById('subsubcategoria');

  subsub.innerHTML = '<option value="">Selecciona...</option>';

  if (!id) return;

  fetch(`load_categorias.php?nivel=subsubcategorias&padre=${id}`)
    .then(r => r.json())
    .then(data => {
      data.forEach(ss => {
        subsub.innerHTML += `<option value="${ss.id}">${ss.nombre}</option>`;
      });
    });
}

</script>

</body>
</html>
