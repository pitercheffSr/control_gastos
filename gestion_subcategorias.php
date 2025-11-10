<?php
include 'db.php';
session_start();
$id_usuario = $_SESSION['usuario_id'] ?? 0;

if ($id_usuario <= 0) {
    echo "Debe iniciar sesión para acceder a esta página.";
    exit;
}

// Función para obtener categorías
function obtenerCategorias($conexion) {
    $res = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = [];
    while ($row = $res->fetch_assoc()) $categorias[] = $row;
    return $categorias;
}

// Función para obtener subcategorías
function obtenerSubcategorias($conexion) {
    $res = $conexion->query("SELECT sc.id, sc.nombre, c.nombre AS categoria_nombre, sc.id_categoria
                             FROM subcategorias sc
                             LEFT JOIN categorias c ON sc.id_categoria = c.id
                             ORDER BY c.nombre, sc.nombre");
    $subcategorias = [];
    while ($row = $res->fetch_assoc()) $subcategorias[] = $row;
    return $subcategorias;
}

// Función para obtener sub-subcategorías
function obtenerSubsubcategorias($conexion) {
    $res = $conexion->query("SELECT ssc.id, ssc.nombre, sc.nombre AS subcategoria_nombre, ssc.id_subcategoria
                             FROM subsubcategorias ssc
                             LEFT JOIN subcategorias sc ON ssc.id_subcategoria = sc.id
                             ORDER BY sc.nombre, ssc.nombre");
    $subsubcategorias = [];
    while ($row = $res->fetch_assoc()) $subsubcategorias[] = $row;
    return $subsubcategorias;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Categorías / Subcategorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4 text-center">Gestión de Categorías, Subcategorías y Sub-Subcategorías</h1>

    <!-- FORMULARIO CATEGORÍA -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Categoría</div>
        <div class="card-body">
            <form method="POST" action="procesar_categoria.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="nombre_categoria" placeholder="Nombre de la categoría" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="accion" value="categoria" class="btn btn-success w-100">Agregar Categoría</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- FORMULARIO SUBCATEGORÍA -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Agregar Subcategoría</div>
        <div class="card-body">
            <form method="POST" action="procesar_categoria.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <select name="id_categoria" class="form-select" required>
                            <option value="">Selecciona categoría</option>
                            <?php foreach(obtenerCategorias($conexion) as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="nombre_subcategoria" placeholder="Nombre de la subcategoría" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="accion" value="subcategoria" class="btn btn-success w-100">Agregar Subcategoría</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- FORMULARIO SUB-SUBCATEGORÍA -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Agregar Sub-Subcategoría</div>
        <div class="card-body">
            <form method="POST" action="procesar_categoria.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <select name="id_categoria" class="form-select" id="categoria_for_subsub" required>
                            <option value="">Selecciona categoría</option>
                            <?php foreach(obtenerCategorias($conexion) as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="id_subcategoria" class="form-select" id="subcategoria_for_subsub" required>
                            <option value="">Selecciona subcategoría</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="nombre_subsubcategoria" placeholder="Nombre sub-subcategoría" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="accion" value="subsubcategoria" class="btn btn-success w-100">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTADO EXISTENTE -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">Listado de Subcategorías y Sub-Subcategorías</div>
        <div class="card-body">
            <h5>Categorías:</h5>
            <ul>
                <?php foreach(obtenerCategorias($conexion) as $cat): ?>
                    <li><?= htmlspecialchars($cat['nombre']) ?></li>
                <?php endforeach; ?>
            </ul>
            <h5>Subcategorías:</h5>
            <ul>
                <?php foreach(obtenerSubcategorias($conexion) as $sub): ?>
                    <li><?= htmlspecialchars($sub['categoria_nombre']) ?> → <?= htmlspecialchars($sub['nombre']) ?></li>
                <?php endforeach; ?>
            </ul>
            <h5>Sub-Subcategorías:</h5>
            <ul>
                <?php foreach(obtenerSubsubcategorias($conexion) as $ssc): ?>
                    <li><?= htmlspecialchars($ssc['subcategoria_nombre']) ?> → <?= htmlspecialchars($ssc['nombre']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('categoria_for_subsub').addEventListener('change', function() {
    const catId = this.value;
    const subSelect = document.getElementById('subcategoria_for_subsub');
    subSelect.innerHTML = '<option value="">Cargando...</option>';

fetch('load_categorias.php?nivel=subcategorias&padre=' + catId)
        .then(resp => resp.json())
        .then(data => {
            subSelect.innerHTML = '<option value="">Selecciona subcategoría</option>';
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.nombre;
                subSelect.appendChild(opt);
            });
        });
});
</script>
</body>
</html>
