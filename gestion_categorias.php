<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/controllers/CategoriasController.php';

$controller = new CategoriasController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $controller->eliminar($_POST['id']);
    } else {
        $controller->guardar($_POST);
    }
    header("Location: gestion_categorias.php");
    exit;
}

$categorias = $controller->index();
include __DIR__ . '/includes/header.php';
?>

<div class="columns mb-2">
    <div class="column col-12">
        <button class="btn btn-primary float-right" onclick="abrirModal()">
            <i class="icon icon-plus"></i> Nueva Categoría
        </button>
        <h3>Mis Categorías</h3>
    </div>
</div>

<div class="columns">
    <?php foreach ($categorias as $cat): ?>
    <div class="column col-12">
        <div class="card p-2 mb-1" style="border-left: 5px solid <?= $cat['color'] ?>; margin-left: <?= $cat['nivel'] * 30 ?>px;">
            <div class="tile tile-centered">
                <div class="tile-icon">
                    <i class="icon <?= $cat['icono'] ?>"></i>
                </div>
                <div class="tile-content">
                    <div class="tile-title text-bold">
                        <?= htmlspecialchars($cat['nombre']) ?>
                        <?php if($cat['grupo_503020']!='indefinido'): ?>
                            <span class="label label-secondary ml-2"><?= ucfirst($cat['grupo_503020']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tile-action">
                    <button class="btn btn-link" onclick='editar(<?= json_encode($cat) ?>)'>Editar</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Borrar?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button class="btn btn-link text-error"><i class="icon icon-delete"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal" id="modalCategoria">
    <a href="#close" class="modal-overlay" onclick="cerrarModal()"></a>
    <div class="modal-container">
        <div class="modal-header">
            <div class="h5 modal-title" id="modalTitulo">Categoría</div>
        </div>
        <div class="modal-body">
            <form method="POST" id="formCategoria">
                <input type="hidden" name="id" id="inputID">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input class="form-input" type="text" name="nombre" id="inputNombre" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Padre</label>
                    <select class="form-select" name="parent_id" id="inputParent">
                        <option value="">-- Principal --</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['nombre_display'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Grupo 50/30/20</label>
                    <select class="form-select" name="grupo_503020" id="inputGrupo">
                        <option value="indefinido">- Sin asignar -</option>
                        <option value="necesidad">Necesidad (50%)</option>
                        <option value="deseo">Deseo (30%)</option>
                        <option value="ahorro">Ahorro (20%)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Color y Tipo</label>
                    <div class="input-group">
                        <input class="form-input" type="color" name="color" id="inputColor" value="#5755d9" style="height:36px; width:50px;">
                        <select class="form-select" name="tipo" id="inputTipo">
                            <option value="gasto">Gasto</option>
                            <option value="ingreso">Ingreso</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Icono</label>
                    <select class="form-select" name="icono" id="inputIcono">
                        <option value="icon-bookmark">Etiqueta</option>
                        <option value="icon-home">Casa</option>
                        <option value="icon-cart">Carrito</option>
                        <option value="icon-people">Gente</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block mt-2">Guardar</button>
            </form>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('modalCategoria');
function abrirModal() {
    document.getElementById('formCategoria').reset();
    document.getElementById('inputID').value = '';
    document.getElementById('modalTitulo').innerText = 'Nueva Categoría';
    modal.classList.add('active');
}
function cerrarModal() { modal.classList.remove('active'); }
function editar(cat) {
    abrirModal();
    document.getElementById('modalTitulo').innerText = 'Editar Categoría';
    document.getElementById('inputID').value = cat.id;
    document.getElementById('inputNombre').value = cat.nombre;
    document.getElementById('inputParent').value = cat.parent_id || '';
    document.getElementById('inputGrupo').value = cat.grupo_503020;
    document.getElementById('inputColor').value = cat.color;
    document.getElementById('inputTipo').value = cat.tipo;
    document.getElementById('inputIcono').value = cat.icono;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>