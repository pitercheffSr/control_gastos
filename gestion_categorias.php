<?php
// ─────────────────────────────────────────────────────────────
//   GESTION DE CATEGORÍAS (versión corregida y optimizada)
//   Estructura usada: tabla única `categorias` con:
//   id | nombre | parent_id | tipo ('gasto','ingreso' o NULL)
// ─────────────────────────────────────────────────────────────

session_start();
require_once __DIR__ . '/db.php'; 

// Normalizar PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Error: No hay conexión PDO");
}

$db = $conn;

// ─────────────────────────────────────────────────────────────
// MANEJO DE ACCIONES POST
// ─────────────────────────────────────────────────────────────

// AÑADIR CATEGORÍA RAÍZ  (tiene TIPO)
if (isset($_POST['action']) && $_POST['action'] === 'add_root') {

    $nombre = trim($_POST['nombre_root']);
    $tipo   = trim($_POST['tipo_root']);

    if ($nombre === '' || ($tipo !== 'gasto' && $tipo !== 'ingreso')) {
        die("Error: datos inválidos");
    }

    $st = $db->prepare("INSERT INTO categorias (nombre, parent_id, tipo) VALUES (:n, NULL, :t)");
    $st->execute(['n' => $nombre, 't' => $tipo]);

    header("Location: gestion_categorias.php");
    exit;
}

// AÑADIR CATEGORÍA HIJA  (sin tipo)
if (isset($_POST['action']) && $_POST['action'] === 'add_child') {

    $nombre = trim($_POST['nombre_child']);
    $parent = intval($_POST['id_parent']);

    if ($nombre === '' || $parent <= 0) {
        die("Error: datos inválidos");
    }

    $st = $db->prepare("INSERT INTO categorias (nombre, parent_id, tipo) VALUES (:n, :p, NULL)");
    $st->execute(['n' => $nombre, 'p' => $parent]);

    header("Location: gestion_categorias.php");
    exit;
}

// ELIMINAR CATEGORÍA
if (isset($_POST['action']) && $_POST['action'] === 'delete') {

    $id = intval($_POST['id']);

    if ($id <= 0) die("Error: ID inválido");

    // Se borran primero los hijos (recursivo manual)
    function borrarRama($db, $id) {
        // borrar hijos
        $st = $db->prepare("SELECT id FROM categorias WHERE parent_id = ?");
        $st->execute([$id]);
        $hijos = $st->fetchAll(PDO::FETCH_COLUMN);

        foreach ($hijos as $h) borrarRama($db, $h);

        // borrar este nodo
        $st2 = $db->prepare("DELETE FROM categorias WHERE id = ?");
        $st2->execute([$id]);
    }

    borrarRama($db, $id);

    header("Location: gestion_categorias.php");
    exit;
}

// ─────────────────────────────────────────────────────────────
// CARGA DE TODAS LAS CATEGORÍAS
// ─────────────────────────────────────────────────────────────

$st = $db->query("SELECT id, nombre, parent_id, tipo FROM categorias ORDER BY parent_id, nombre");
$cats = $st->fetchAll(PDO::FETCH_ASSOC);

// Convertir a árbol para dibujarlo
function buildTree($elements, $parent = NULL) {
    $branch = [];
    foreach ($elements as $el) {
        if ($el['parent_id'] == $parent) {
            $hijos = buildTree($elements, $el['id']);
            if ($hijos) $el['hijos'] = $hijos;
            $branch[] = $el;
        }
    }
    return $branch;
}

$tree = buildTree($cats);
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
.cat-root{background:#dceeff;border-left:4px solid #0d6efd;padding:10px;margin-bottom:10px;}
.cat-child{background:#fff4d8;border-left:4px solid #ffc107;padding:10px;margin:6px 0 6px 25px;}
.cat-sub{background:#e1fff2;border-left:4px solid #20c997;padding:10px;margin:6px 0 6px 50px;}
.btn-sm{padding:2px 6px;}
</style>
</head>

<body class="bg-light">
<div class="container py-4">
<h2 class="mb-3">Gestión de Categorías</h2>

<!-- ────────────────────────────────────────────── -->
<!-- NUEVA CATEGORÍA RAÍZ -->
<!-- ────────────────────────────────────────────── -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">Añadir categoría raíz</div>
  <div class="card-body">
    <form method="POST" class="row g-2">
      <input type="hidden" name="action" value="add_root">
      <div class="col-md-5">
        <input type="text" name="nombre_root" class="form-control" placeholder="Nombre categoría" required>
      </div>
      <div class="col-md-3">
        <select name="tipo_root" class="form-select" required>
          <option value="">Tipo...</option>
          <option value="gasto">Gasto</option>
          <option value="ingreso">Ingreso</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-success"><i class="bi bi-plus-lg"></i> Crear</button>
      </div>
    </form>
  </div>
</div>

<!-- ────────────────────────────────────────────── -->
<!-- LISTA JERÁRQUICA -->
<!-- ────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header bg-secondary text-white">Estructura de categorías</div>
  <div class="card-body">

<?php if (empty($tree)): ?>
  <p class="text-muted">No hay categorías.</p>
<?php endif; ?>

<?php
// Función recursiva para dibujar árbol
function renderNode($nodo, $nivel = 1){
    $class = $nivel === 1 ? "cat-root" : ($nivel === 2 ? "cat-child" : "cat-sub");

    echo "<div class='{$class}'>";
    echo "<div class='d-flex justify-content-between'>";
    echo "<div><strong>".htmlspecialchars($nodo['nombre'])."</strong>";

    if ($nivel === 1)
        echo " <span class='badge bg-dark ms-2'>".$nodo['tipo']."</span>";

    echo "</div>";

    echo "<div>";
    echo "<button class='btn btn-success btn-sm btn-add' data-id='{$nodo['id']}'><i class='bi bi-plus-circle'></i></button>";

    echo "<form method='POST' class='d-inline'>";
    echo "<input type='hidden' name='action' value='delete'>";
    echo "<input type='hidden' name='id' value='{$nodo['id']}'>";
    echo "<button type='submit' class='btn btn-danger btn-sm' onclick='return confirm(\"Eliminar categoría y todas sus hijas?\")'><i class='bi bi-trash'></i></button>";
    echo "</form>";

    echo "</div></div>";

    // formulario oculto para añadir hijo
    echo "<form method='POST' class='row g-2 mt-2 add-form' id='add-{$nodo['id']}' style='display:none'>";
    echo "<input type='hidden' name='action' value='add_child'>";
    echo "<input type='hidden' name='id_parent' value='{$nodo['id']}'>";
    echo "<div class='col-md-6'><input name='nombre_child' type='text' class='form-control form-control-sm' placeholder='Nueva subcategoría' required></div>";
    echo "<div class='col-md-3'><button class='btn btn-success btn-sm'><i class='bi bi-plus-lg'></i> Añadir</button></div>";
    echo "</form>";

    if (isset($nodo['hijos'])) {
        foreach ($nodo['hijos'] as $hijo)
            renderNode($hijo, $nivel + 1);
    }

    echo "</div>";
}

foreach ($tree as $cat) renderNode($cat);
?>

  </div>
</div>

<div class="mt-3">
  <a href="dashboard.php" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> Volver al dashboard
  </a>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar formulario hijo
document.querySelectorAll(".btn-add").forEach(btn=>{
  btn.onclick = ()=>{
    const id = btn.dataset.id;
    const form = document.getElementById("add-"+id);
    form.style.display = form.style.display === "none" ? "flex" : "none";
  };
});
</script>

</body>
</html>
