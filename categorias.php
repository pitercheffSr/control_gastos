<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Categorías</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <h3 class="mb-3"><i class="bi bi-tags"></i> Categorías</h3>

    <form method="POST" action="insert_categoria.php" class="row g-3 mb-4">
      <div class="col-md-4">
        <input type="text" name="nombre" class="form-control" placeholder="Nueva categoría" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Añadir</button>
      </div>
    </form>

    <h5>Categorías existentes:</h5>
    <ul class="list-group">
      <?php
      $stmt = $conn->query("SELECT * FROM categorias ORDER BY nombre ASC");
      foreach ($stmt as $cat) {
          echo "<li class='list-group-item d-flex justify-content-between align-items-center'>"
              . htmlspecialchars($cat['nombre'])
              . "<a href='delete_categoria.php?id={$cat['id']}' class='btn btn-sm btn-danger'>Eliminar</a></li>";
      }
      ?>
    </ul>

    <div class="mt-4">
      <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
  </div>
</body>
</html>
