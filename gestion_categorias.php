<?php
// gestion_categorias.php — Panel para gestionar categorías, subcategorías y sub-subcategorías
require_once __DIR__ . '/db.php';
session_start();

// Proteger acceso
if (!isset($_SESSION['usuario_id'])) {
    header('Location: views/login.php');
    exit;
}

// --- CREAR / ELIMINAR CATEGORÍA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nueva_categoria'])) {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
        $stmt->execute(['nombre' => $_POST['nueva_categoria']]);
    } elseif (isset($_POST['eliminar_categoria'])) {
        $stmt = $conn->prepare("DELETE FROM categorias WHERE id = :id");
        $stmt->execute(['id' => $_POST['eliminar_categoria']]);
    } elseif (isset($_POST['nueva_subcategoria'])) {
        $stmt = $conn->prepare("INSERT INTO subcategorias (nombre, id_categoria) VALUES (:nombre, :id_categoria)");
        $stmt->execute([
            'nombre' => $_POST['nueva_subcategoria'],
            'id_categoria' => $_POST['id_categoria']
        ]);
    } elseif (isset($_POST['eliminar_subcategoria'])) {
        $stmt = $conn->prepare("DELETE FROM subcategorias WHERE id = :id");
        $stmt->execute(['id' => $_POST['eliminar_subcategoria']]);
    } elseif (isset($_POST['nueva_subsubcategoria'])) {
        $stmt = $conn->prepare("INSERT INTO subsubcategorias (nombre, id_subcategoria) VALUES (:nombre, :id_subcategoria)");
        $stmt->execute([
            'nombre' => $_POST['nueva_subsubcategoria'],
            'id_subcategoria' => $_POST['id_subcategoria']
        ]);
    } elseif (isset($_POST['eliminar_subsubcategoria'])) {
        $stmt = $conn->prepare("DELETE FROM subsubcategorias WHERE id = :id");
        $stmt->execute(['id' => $_POST['eliminar_subsubcategoria']]);
    }

    header("Location: gestion_categorias.php");
    exit;
}

// --- OBTENER DATOS PARA MOSTRAR ---
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$subcategorias = $conn->query("SELECT * FROM subcategorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$subsubcategorias = $conn->query("SELECT * FROM subsubcategorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Categorías</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h2 class="mb-4 text-center">Gestión de Categorías y Subcategorías</h2>
  <div class="text-end mb-3">
    <a href="dashboard.php" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Volver al Dashboard
    </a>
  </div>

  <!-- CATEGORÍAS -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">Categorías</div>
    <div class="card-body">
      <form method="POST" class="d-flex mb-3">
        <input type="text" name="nueva_categoria" class="form-control me-2" placeholder="Nueva categoría" required>
        <button class="btn btn-success"><i class="bi bi-plus-lg"></i> Añadir</button>
      </form>
      <ul class="list-group">
        <?php foreach ($categorias as $cat): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($cat['nombre']) ?>
            <button name="eliminar_categoria" value="<?= $cat['id'] ?>" class="btn btn-sm btn-danger"
                    formmethod="POST" formaction="gestion_categorias.php">
              <i class="bi bi-trash"></i>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- SUBCATEGORÍAS -->
  <div class="card mb-4">
    <div class="card-header bg-warning text-dark">Subcategorías</div>
    <div class="card-body">
      <form method="POST" class="row g-2 mb-3">
        <div class="col-md-5">
          <input type="text" name="nueva_subcategoria" class="form-control" placeholder="Nueva subcategoría" required>
        </div>
        <div class="col-md-5">
          <select name="id_categoria" class="form-select" required>
            <option value="">Categoría...</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button>
        </div>
      </form>
      <ul class="list-group">
        <?php foreach ($subcategorias as $sub): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($sub['nombre']) ?> <span class="text-muted">(Cat ID <?= $sub['id_categoria'] ?>)</span>
            <button name="eliminar_subcategoria" value="<?= $sub['id'] ?>" class="btn btn-sm btn-danger"
                    formmethod="POST" formaction="gestion_categorias.php">
              <i class="bi bi-trash"></i>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- SUBSUBCATEGORÍAS -->
  <div class="card mb-4">
    <div class="card-header bg-info text-white">Sub-Subcategorías</div>
    <div class="card-body">
      <form method="POST" class="row g-2 mb-3">
        <div class="col-md-5">
          <input type="text" name="nueva_subsubcategoria" class="form-control" placeholder="Nueva sub-subcategoría" required>
        </div>
        <div class="col-md-5">
          <select name="id_subcategoria" class="form-select" required>
            <option value="">Subcategoría...</option>
            <?php foreach ($subcategorias as $sub): ?>
              <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button>
        </div>
      </form>
      <ul class="list-group">
        <?php foreach ($subsubcategorias as $ssc): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($ssc['nombre']) ?> <span class="text-muted">(SubCat ID <?= $ssc['id_subcategoria'] ?>)</span>
            <button name="eliminar_subsubcategoria" value="<?= $ssc['id'] ?>" class="btn btn-sm btn-danger"
                    formmethod="POST" formaction="gestion_categorias.php">
              <i class="bi bi-trash"></i>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
</body>
</html>
