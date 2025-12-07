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

/** @var PDO $conn */

// Función para borrar recursivamente una rama de categorías
function borrarRama($db, $id) {
  $st = $db->prepare("SELECT id FROM categorias WHERE parent_id = ?");
  $st->execute([$id]);
  $hijos = $st->fetchAll(PDO::FETCH_COLUMN);

  foreach ($hijos as $h) borrarRama($db, $h);

  $st2 = $db->prepare("DELETE FROM categorias WHERE id = ?");
  $st2->execute([$id]);
}

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

// AÑADIR CATEGORÍA HIJA  (hereda tipo)
if (isset($_POST['action']) && $_POST['action'] === 'add_child') {

    $nombre = trim($_POST['nombre_child']);
    $parent = intval($_POST['id_parent']);

    if ($nombre === '' || $parent <= 0) {
        die("Error: datos inválidos");
    }

    // obtener tipo del padre
    $st = $db->prepare("SELECT tipo FROM categorias WHERE id = :p LIMIT 1");
    $st->execute(['p' => $parent]);
    $tipo_padre = $st->fetchColumn();

    if (!$tipo_padre) {
        die("Error: categoría padre no encontrada");
    }

    // insertar con el mismo tipo
    $st = $db->prepare("INSERT INTO categorias (nombre, parent_id, tipo)
                        VALUES (:n, :p, :t)");
    $st->execute(['n' => $nombre, 'p' => $parent, 't' => $tipo_padre]);

    header("Location: gestion_categorias.php");
    exit;
}


// ELIMINAR CATEGORÍA
if (isset($_POST['action']) && $_POST['action'] === 'delete') {

    $id = intval($_POST['id']);

    if ($id <= 0) die("Error: ID inválido");

    // Se borran primero los hijos (recursivo manual)
    // Usar la función global `borrarRama` definida arriba
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

<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">

<style>
.cat-root{background:#dceeff;border-left:4px solid #0d6efd;padding:10px;margin-bottom:10px;}
.cat-child{background:#fff4d8;border-left:4px solid #ffc107;padding:10px;margin:6px 0 6px 25px;}
.cat-sub{background:#e1fff2;border-left:4px solid #20c997;padding:10px;margin:6px 0 6px 50px;}
.container{max-width:1200px;margin:0 auto;padding:0 1rem;}
.flex-row{display:flex;gap:0.5rem;align-items:center;}
.flex-between{display:flex;justify-content:space-between;align-items:center;}
</style>
</head>

<body>
<div class="container" style="padding-top:2rem;">
<h2>Gestión de Categorías</h2>

<!-- ────────────────────────────────────────────── -->
<!-- NUEVA CATEGORÍA RAÍZ -->
<!-- ────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:2rem;">
  <div class="card-header" style="background:#5755d2;color:white;">Añadir categoría raíz</div>
  <div class="card-body">
    <form method="POST" class="flex-row">
      <input type="hidden" name="action" value="add_root">
      <div style="flex:1;max-width:300px;">
        <input type="text" name="nombre_root" class="form-input" placeholder="Nombre categoría" required>
      </div>
      <div style="flex:1;max-width:200px;">
        <select name="tipo_root" class="form-input" required>
          <option value="">Tipo...</option>
          <option value="gasto">Gasto</option>
          <option value="ingreso">Ingreso</option>
        </select>
      </div>
      <div>
        <button class="btn btn-primary"><i class="icon icon-plus"></i> Crear</button>
      </div>
    </form>
  </div>
</div>

<!-- ────────────────────────────────────────────── -->
<!-- LISTA JERÁRQUICA -->
<!-- ────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header" style="background:#32b643;color:white;">Estructura de categorías</div>
  <div class="card-body">

<?php if (empty($tree)): ?>
  <p style="color:#666;">No hay categorías.</p>
<?php endif; ?>

<?php
// Función recursiva para dibujar árbol
function renderNode($nodo, $nivel = 1){
    $class = $nivel === 1 ? "cat-root" : ($nivel === 2 ? "cat-child" : "cat-sub");

    echo "<div class='{$class}'>";
    echo "<div class='d-flex justify-content-between'>";
    echo "<div><strong>".htmlspecialchars($nodo['nombre'])."</strong>";

    if ($nivel === 1)
        echo " <span class='label' style='background:#5755d2;color:white;margin-left:0.5rem;'>".$nodo['tipo']."</span>";

    echo "</div>";

    echo "<div class='flex-row'>";
    echo "<button class='btn btn-sm btn-primary btn-add' data-id='{$nodo['id']}'><i class='icon icon-plus'></i></button>";

    echo "<form method='POST' style='display:inline;'>";
    echo "<input type='hidden' name='action' value='delete'>";
    echo "<input type='hidden' name='id' value='{$nodo['id']}'>";
    echo "<button type='submit' class='btn btn-sm btn-error' onclick='return confirm(\"Eliminar categoría y todas sus hijas?\")'><i class='icon icon-trash'></i></button>";
    echo "</form>";

    echo "</div></div>";

    // formulario oculto para añadir hijo
    echo "<form method='POST' class='flex-row' id='add-{$nodo['id']}' style='display:none;margin-top:0.5rem;'>";
    echo "<input type='hidden' name='action' value='add_child'>";
    echo "<input type='hidden' name='id_parent' value='{$nodo['id']}'>";
    echo "<div style='flex:1;max-width:250px;'><input name='nombre_child' type='text' class='form-input' placeholder='Nueva subcategoría' required></div>";
    echo "<div><button class='btn btn-sm btn-primary'><i class='icon icon-plus'></i> Añadir</button></div>";
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

<div style="margin-top:2rem;">
  <a href="dashboard.php" class="btn btn-default">
    <i class="icon icon-arrow-left"></i> Volver al dashboard
  </a>
</div>
</div>

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
