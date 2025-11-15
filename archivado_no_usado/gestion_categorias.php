<?php
// gestion_categorias.php - gestor completo (añadir / eliminar / listar jerárquico)
session_start();
require_once __DIR__ . '/db.php'; // debe definir $conn (PDO) o $conexion (mysqli)

// Normalizar conexión PDO
if (isset($conn) && $conn instanceof PDO) {
    $db = $conn;
} elseif (isset($conexion) && $conexion instanceof mysqli) {
    // convertir mysqli a PDO-like wrapper sencillo usando fetch_assoc later
    $db = $conexion; // indicaremos usos distintos abajo
} else {
    die("No se encontró conexión a la base de datos. Revisa db.php");
}

// Manejo de POST: añadir / eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir categoría
    if (!empty($_POST['action']) && $_POST['action'] === 'add_categoria' && !empty($_POST['nombre_cat'])) {
        $nombre = trim($_POST['nombre_cat']);
        if ($nombre !== '') {
            if ($db instanceof PDO) {
                $st = $db->prepare("INSERT INTO categorias (nombre) VALUES (:n)");
                $st->execute(['n' => $nombre]);
            } else {
                $stmt = $db->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                $stmt->close();
            }
        }
        header('Location: gestion_categorias.php'); exit;
    }

    // Añadir subcategoría
    if (!empty($_POST['action']) && $_POST['action'] === 'add_sub' && !empty($_POST['nombre_sub']) && !empty($_POST['id_categoria'])) {
        $nombre = trim($_POST['nombre_sub']);
        $id_cat = intval($_POST['id_categoria']);
        if ($db instanceof PDO) {
            $st = $db->prepare("INSERT INTO subcategorias (id_categoria, nombre) VALUES (:id_cat, :n)");
            $st->execute(['id_cat' => $id_cat, 'n' => $nombre]);
        } else {
            $stmt = $db->prepare("INSERT INTO subcategorias (id_categoria, nombre) VALUES (?, ?)");
            $stmt->bind_param("is", $id_cat, $nombre);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: gestion_categorias.php'); exit;
    }

    // Añadir sub-subcategoría
    if (!empty($_POST['action']) && $_POST['action'] === 'add_subsub' && !empty($_POST['nombre_subsub']) && !empty($_POST['id_subcategoria'])) {
        $nombre = trim($_POST['nombre_subsub']);
        $id_sub = intval($_POST['id_subcategoria']);
        if ($db instanceof PDO) {
            $st = $db->prepare("INSERT INTO subsubcategorias (id_subcategoria, nombre) VALUES (:id_sub, :n)");
            $st->execute(['id_sub' => $id_sub, 'n' => $nombre]);
        } else {
            $stmt = $db->prepare("INSERT INTO subsubcategorias (id_subcategoria, nombre) VALUES (?, ?)");
            $stmt->bind_param("is", $id_sub, $nombre);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: gestion_categorias.php'); exit;
    }

    // Eliminar (categoría / sub / subsub)
    if (!empty($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['type']) && !empty($_POST['id'])) {
        $type = $_POST['type'];
        $id = intval($_POST['id']);
        if ($type === 'cat') {
            $sql = "DELETE FROM categorias WHERE id = :id";
            if ($db instanceof PDO) {
                $st = $db->prepare($sql); $st->execute(['id' => $id]);
            } else {
                $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
            }
        } elseif ($type === 'sub') {
            if ($db instanceof PDO) {
                $st = $db->prepare("DELETE FROM subcategorias WHERE id = :id"); $st->execute(['id' => $id]);
            } else {
                $stmt = $db->prepare("DELETE FROM subcategorias WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
            }
        } elseif ($type === 'subsub') {
            if ($db instanceof PDO) {
                $st = $db->prepare("DELETE FROM subsubcategorias WHERE id = :id"); $st->execute(['id' => $id]);
            } else {
                $stmt = $db->prepare("DELETE FROM subsubcategorias WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
            }
        }
        header('Location: gestion_categorias.php'); exit;
    }
}

// --- Recuperar datos una sola vez para renderizar (eficiente) ---
$categorias = [];
$subcategorias = [];
$subsubcategorias = [];

if ($db instanceof PDO) {
    $categorias = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $subcategorias = $db->query("SELECT id, id_categoria, nombre FROM subcategorias ORDER BY id_categoria, nombre")->fetchAll(PDO::FETCH_ASSOC);
    $subsubcategorias = $db->query("SELECT id, id_subcategoria, nombre FROM subsubcategorias ORDER BY id_subcategoria, nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // mysqli
    $res = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    while ($r = $res->fetch_assoc()) $categorias[] = $r;
    $res = $db->query("SELECT id, id_categoria, nombre FROM subcategorias ORDER BY id_categoria, nombre");
    while ($r = $res->fetch_assoc()) $subcategorias[] = $r;
    $res = $db->query("SELECT id, id_subcategoria, nombre FROM subsubcategorias ORDER BY id_subcategoria, nombre");
    while ($r = $res->fetch_assoc()) $subsubcategorias[] = $r;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gestión Categorías</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .cat-item{ background:#e9f3ff; margin-bottom:8px; border-left:4px solid #0d6efd; padding:10px; }
    .sub-item{ background:#fff8e1; margin:6px 0 6px 18px; border-left:4px solid #ffc107; padding:8px; }
    .subsub-item{ background:#e7f9f4; margin:4px 0 4px 36px; border-left:4px solid #20c997; padding:6px; }
    .small-actions button{ margin-left:6px; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <h2 class="mb-3">Gestión de Categorías</h2>

  <!-- FORM: AÑADIR CATEGORÍA RÁPIDA -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="POST" class="row g-2 align-items-center">
        <input type="hidden" name="action" value="add_categoria">
        <div class="col-auto" style="flex:1">
          <input type="text" name="nombre_cat" class="form-control" placeholder="Nueva categoría" required>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Añadir categoría</button>
        </div>
      </form>
    </div>
  </div>

  <!-- LISTADO JERÁRQUICO -->
  <div class="card">
    <div class="card-body">
      <?php if (empty($categorias)): ?>
        <p class="text-muted">No hay categorías.</p>
      <?php else: ?>
        <?php foreach ($categorias as $cat): ?>
          <div class="cat-item">
            <div class="d-flex justify-content-between align-items-start">
              <div><strong><?= htmlspecialchars($cat['nombre']) ?></strong></div>
              <div class="small-actions">
                <!-- Form para añadir subcategoria ligada a esta categoría (colapsable) -->
                <button class="btn btn-success btn-sm btn-add-sub" data-cat="<?= $cat['id'] ?>"><i class="bi bi-plus-circle"></i></button>
                <!-- Edit placeholder -->
                <!--<button class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></button>-->
                <!-- Delete -->
                <form method="POST" style="display:inline-block" class="d-inline delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="type" value="cat">
                  <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                  <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="bi bi-trash"></i></button>
                </form>
              </div>
            </div>

            <!-- inline add sub form (hidden by default) -->
            <div class="mt-2 mb-2 add-sub-form" data-cat="<?= $cat['id'] ?>" style="display:none">
              <form method="POST" class="row g-2 align-items-center">
                <input type="hidden" name="action" value="add_sub">
                <input type="hidden" name="id_categoria" value="<?= $cat['id'] ?>">
                <div class="col-auto" style="flex:1">
                  <input type="text" name="nombre_sub" class="form-control form-control-sm" placeholder="Nombre subcategoría" required>
                </div>
                <div class="col-auto">
                  <button class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Añadir</button>
                </div>
                <div class="col-12 mt-1">
                  <small class="text-muted">Añadir subcategoría para <strong><?= htmlspecialchars($cat['nombre']) ?></strong></small>
                </div>
              </form>
            </div>

            <!-- list subcategorias de esta categoria -->
            <?php
              $subs = array_values(array_filter($subcategorias, fn($s) => intval($s['id_categoria']) === intval($cat['id'])));
            ?>
            <?php if (!empty($subs)): ?>
              <?php foreach ($subs as $sub): ?>
                <div class="sub-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div><?= htmlspecialchars($sub['nombre']) ?></div>
                    <div class="small-actions">
                      <button class="btn btn-success btn-sm btn-add-subsub" data-sub="<?= $sub['id'] ?>"><i class="bi bi-plus-circle"></i></button>
                      <!--<button class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></button>-->
                      <form method="POST" style="display:inline-block" class="d-inline delete-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type" value="sub">
                        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                        <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </div>

                  <!-- inline add sub-sub form -->
                  <div class="mt-2 mb-2 add-subsub-form" data-sub="<?= $sub['id'] ?>" style="display:none">
                    <form method="POST" class="row g-2 align-items-center">
                      <input type="hidden" name="action" value="add_subsub">
                      <input type="hidden" name="id_subcategoria" value="<?= $sub['id'] ?>">
                      <div class="col-auto" style="flex:1">
                        <input type="text" name="nombre_subsub" class="form-control form-control-sm" placeholder="Nombre sub-subcategoría" required>
                      </div>
                      <div class="col-auto">
                        <button class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Añadir</button>
                      </div>
                      <div class="col-12 mt-1">
                        <small class="text-muted">Añadir sub-subcategoría para <strong><?= htmlspecialchars($sub['nombre']) ?></strong></small>
                      </div>
                    </form>
                  </div>

                  <!-- listar subsub -->
                  <?php
                    $sscs = array_values(array_filter($subsubcategorias, fn($s) => intval($s['id_subcategoria']) === intval($sub['id'])));
                  ?>
                  <?php if (!empty($sscs)): ?>
                    <?php foreach ($sscs as $ssc): ?>
                      <div class="subsub-item d-flex justify-content-between align-items-center">
                        <div><?= htmlspecialchars($ssc['nombre']) ?></div>
                        <div>
                          <!--<button class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></button>-->
                          <form method="POST" style="display:inline-block" class="d-inline delete-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="type" value="subsub">
                            <input type="hidden" name="id" value="<?= $ssc['id'] ?>">
                            <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="small text-muted ms-4">No hay sub-subcategorías.</div>
                  <?php endif; ?>

                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="small text-muted ms-2">No hay subcategorías.</div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-3">
    <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar/ocultar formularios inline y confirm delete
document.addEventListener('DOMContentLoaded', function(){
  // Toggle add-sub forms
  document.querySelectorAll('.btn-add-sub').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.cat;
      const node = document.querySelector('.add-sub-form[data-cat="'+id+'"]');
      if (node) node.style.display = node.style.display === 'none' ? 'block' : 'none';
    });
  });

  // Toggle add-subsub forms
  document.querySelectorAll('.btn-add-subsub').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.sub;
      const node = document.querySelector('.add-subsub-form[data-sub="'+id+'"]');
      if (node) node.style.display = node.style.display === 'none' ? 'block' : 'none';
    });
  });

  // Delete confirmations
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (!confirm('¿Eliminar definitivamente? Esta acción no se puede deshacer.')) return;
      // submit the enclosing form
      const form = btn.closest('form');
      if (form) form.submit();
    });
  });
});
</script>
</body>
</html>
