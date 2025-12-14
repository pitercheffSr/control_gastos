<?php
session_start();
require_once __DIR__ . '/db.php';

// Seguridad
if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$db = $conn;

// =============================================================
// FUNCIONES
// =============================================================

// Cargar toda la tabla
$st = $db->query("SELECT id, nombre, parent_id, tipo
                  FROM categorias
                  ORDER BY parent_id, nombre");
$categorias = $st->fetchAll(PDO::FETCH_ASSOC);

// Construir árbol jerárquico
function buildTree($elements, $parent = null)
{
    $branch = [];
    foreach ($elements as $el) {
        if ($el["parent_id"] == $parent) {
            $children = buildTree($elements, $el["id"]);
            if ($children) {
                $el["hijos"] = $children;
            }
            $branch[] = $el;
        }
    }
    return $branch;
}

$tree = buildTree($categorias);

// =============================================================
// PROCESAR CREACIÓN DE CATEGORÍA RAÍZ
// =============================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_root") {
    $nombre = trim($_POST["nombre_root"]);
    $tipo = trim($_POST["tipo_root"]);

    if ($nombre !== "" && ($tipo === "gasto" || $tipo === "ingreso")) {
        $ins = $db->prepare("INSERT INTO categorias (nombre, parent_id, tipo) VALUES (:n, NULL, :t)");
        $ins->execute(["n" => $nombre, "t" => $tipo]);
    }

    header("Location: gestion_categorias.php");
    exit;
}

// =============================================================
// FUNCION PARA DIBUJAR NODOS (SOLO LECTURA)
// =============================================================

function renderNode($nodo, $nivel = 1)
{
    // Elegir estilo visual según nivel
    $class = $nivel === 1 ? "cat-root" : ($nivel === 2 ? "cat-child" : "cat-sub");

    // Iconos según profundidad
    $icon = ($nivel === 1)
        ? "<svg width='20' height='20' stroke='#333' fill='none' stroke-width='1.8' viewBox='0 0 24 24'><path d='M3 7h6l2 2h10v10H3z'/></svg>"
        : (($nivel === 2)
            ? "<svg width='18' height='18' stroke='#555' fill='none' stroke-width='1.6' viewBox='0 0 24 24'><path d='M3 7h6l2 2h10v10H3z'/></svg>"
            : "<svg width='16' height='16' stroke='#777' fill='none' stroke-width='1.4' viewBox='0 0 24 24'><path d='M3 7h6l2 2h10v10H3z'/></svg>");

    echo "<div class='{$class}'>";

    echo "<div class='flex-between'>";
    echo "<div class='flex-row'>";

    // Icono + Nombre
    echo $icon . " <strong>" . htmlspecialchars($nodo["nombre"]) . "</strong>";

    // Tipo solo en raíz
    if ($nivel === 1) {
        echo "<span class='label tipo-label'>" . htmlspecialchars($nodo["tipo"]) . "</span>";
    }

    echo "</div></div>";

    // Dibujar hijos
    if (isset($nodo["hijos"])) {
        foreach ($nodo["hijos"] as $h) {
            renderNode($h, $nivel + 1);
        }
    }

    echo "</div>";
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Gestión de Categorías</title>
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
<link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">

<style>
body { background:#f8f9fb; }

/* Contenedor */
.container { max-width: 900px; margin: 2rem auto; }

/* Estilo del árbol */
.cat-root, .cat-child, .cat-sub {
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 8px;
}

.cat-root  { background:#e8f1ff; border-left:4px solid #1a73e8; }
.cat-child { background:#fff4d8; border-left:4px solid #ffa000; margin-left:25px; }
.cat-sub   { background:#e7fff2; border-left:4px solid #20c997; margin-left:50px; }

.flex-row { display:flex; align-items:center; gap:8px; }
.flex-between { display:flex; justify-content:space-between; align-items:center; }

.tipo-label {
    background:#5755d2;
    color:white;
    padding:2px 6px;
    border-radius:4px;
    margin-left:8px;
}

/* Card título */
.card-header {
    font-size:1.1rem;
    font-weight:600;
}
</style>
</head>

<body>

<!-- Contenedor principal -->
<div class="container">

<h2>Gestión de Categorías</h2>


<!-- ==========================
     SOLO VISUALIZACIÓN ÁRBOL
============================== -->
<div class="card">
	<div class="card-header" style="background:#32b643;color:white;">
		Estructura de categorías
	</div>

	<div class="card-body">
		<div id="estructuraCategorias"></div>
	</div>
</div>

<div style="margin-top:2rem;">
  <a href="dashboard.php" class="btn btn-default">
    <i class="icon icon-arrow-left"></i> Volver al Dashboard
  </a>
</div>
<!-- =========================================================
     PANEL DE MANTENIMIENTO DE CATEGORÍAS
========================================================= -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header">
        <strong>Mantenimiento de categorías</strong>
    </div>

    <div class="card-body">
        <form id="formCategoria" class="form-horizontal">

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" id="cat_nombre" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select id="cat_tipo" class="form-select">
                    <option value="gasto">Gasto</option>
                    <option value="ingreso">Ingreso</option>
                </select>
            </div>

            <div class="form-group">
                <label>Depende de</label>
                <select id="cat_parent" class="form-select">
                    <option value="">— Categoría raíz —</option>
                </select>
            </div>

            <button class="btn btn-primary">
                <i class="icon icon-save"></i> Guardar
            </button>

            <button type="button" class="btn btn-link" id="btnCancelar" style="display:none;">
                Cancelar edición
            </button>

        </form>
    </div>
</div>

</div>
	<script src="assets/js/categorias.js"></script>

</body>
</html>
