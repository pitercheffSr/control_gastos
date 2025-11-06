<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
include '../includes/conexion.php';

$id_usuario = $_SESSION['usuario_id'];

// Obtener categorías generales (id_usuario = 0) y personalizadas
$sql = "SELECT * FROM categorias WHERE id_usuario = 0 OR id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
.list-group.scrollable-list {
    max-height: 328px; /* Aproximadamente 8 ítems de 40px + padding */
    overflow-y: auto;
}
</style>

<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4" style="margin-top: 0 !important;">Gestión de Categorías y Conceptos</h1>

        <!-- Listado de Categorías -->
        <div class="card p-4 mt-4">
            <h4>Tus Categorías</h4>
            <ul class="list-group scrollable-list">
                <?php foreach ($categorias as $categoria): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($categoria['tipo']); ?></span>
                            <span class="badge bg-info ms-2">Regla <?php echo htmlspecialchars($categoria['clasificacion']); ?>%</span>
                        </div>
                        <?php if ($categoria['id_usuario'] != 0): ?>
                            <!-- Botón para eliminar solo categorías personalizadas -->
                            <a href="#" class="btn btn-sm btn-danger" onclick="return confirmarEliminacion('<?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES); ?>', 'eliminar_categoria.php?id=<?php echo $categoria['id']; ?>');">Eliminar</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Panel informativo de categorías fijas -->
    <div class="col-md-4">
        <div class="card p-4 bg-light">
            <h4>Categorías Principales</h4>
            <ul class="list-group">
                <li class="list-group-item"><strong>Necesidades</strong> <span class="badge bg-info ms-2">50%</span></li>
                <li class="list-group-item"><strong>Deseos</strong> <span class="badge bg-info ms-2">30%</span></li>
                <li class="list-group-item"><strong>Ahorro</strong> <span class="badge bg-info ms-2">20%</span></li>
            </ul>
            <small class="text-muted d-block mt-2">Estas categorías son fijas y no pueden editarse.</small>
        </div>
    </div>
</div>

<!-- Gestión de Subcategorías -->
<div class="row mt-4">
    <!-- Listado de subcategorías a la izquierda -->
    <div class="col-md-8">
        <div class="card p-4">
            <h5>Listado de Subcategorías</h5>
            <ul class="list-group scrollable-list">
                <?php
                // Obtener todas las subcategorías del usuario agrupadas por categoría principal
                $sql_sub = "SELECT s.*, c.nombre as categoria_nombre FROM subcategorias s INNER JOIN categorias c ON s.id_categoria = c.id WHERE s.id_usuario = ? ORDER BY s.id_categoria, s.parent_id, s.nombre";
                $stmt_sub = $conexion->prepare($sql_sub);
                $stmt_sub->bind_param("i", $id_usuario);
                $stmt_sub->execute();
                $subcategorias = $stmt_sub->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_sub->close();

                // Agrupar por categoría y por parent_id
                $cat_map = [1 => 'Necesidades', 2 => 'Deseos', 3 => 'Ahorro'];
                $tree = [];
                foreach ($subcategorias as $sub) {
                    if ($sub['parent_id'] === null) {
                        $tree[$sub['id_categoria']][$sub['id']] = [
                            'info' => $sub,
                            'children' => []
                        ];
                    }
                }
                // Añadir sub-subcategorías
                foreach ($subcategorias as $sub) {
                    if ($sub['parent_id'] !== null) {
                        $tree[$sub['id_categoria']][$sub['parent_id']]['children'][] = $sub;
                    }
                }
                foreach ($cat_map as $cat_id => $cat_nombre):
                    if (!isset($tree[$cat_id])) continue;
                ?>
                <li class="list-group-item bg-light"><strong><?php echo $cat_nombre; ?></strong></li>
                <?php foreach ($tree[$cat_id] as $subcat): ?>
                    <li class="list-group-item">
                        <span class="fw-bold"><?php echo htmlspecialchars($subcat['info']['nombre']); ?></span>
                        <a href="#" class="btn btn-sm btn-danger float-end ms-2" onclick="return confirmarEliminacion('<?php echo htmlspecialchars($subcat['info']['nombre'], ENT_QUOTES); ?>', 'eliminar_subcategoria.php?id=<?php echo $subcat['info']['id']; ?>');">Eliminar</a>
                        <?php if (!empty($subcat['children'])): ?>
                            <ul class="list-group mt-2 ms-3">
                                <?php foreach ($subcat['children'] as $child): ?>
                                    <li class="list-group-item">
                                        <span><?php echo htmlspecialchars($child['nombre']); ?></span>
                                        <a href="#" class="btn btn-sm btn-danger float-end ms-2" onclick="return confirmarEliminacion('<?php echo htmlspecialchars($child['nombre'], ENT_QUOTES); ?>', 'eliminar_subcategoria.php?id=<?php echo $child['id']; ?>');">Eliminar</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <!-- Formulario de subcategorías a la derecha debajo del de categorías -->
    <div class="col-md-4">
        <h4>Subcategorías</h4>
        <div class="card p-4 mb-3">
            <form action="procesar_subcategoria.php" method="POST">
                <div class="mb-3">
                    <label for="nombre_sub" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre_sub" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría Principal</label>
                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                        <option value="1">Necesidades (50%)</option>
                        <option value="2">Deseos (30%)</option>
                        <option value="3">Ahorro (20%)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Subcategoría Padre (opcional)</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">Ninguna (subcategoría de primer nivel)</option>
                        <?php
                        // Mostrar solo subcategorías de primer nivel para la categoría seleccionada
                        $sql_padres = "SELECT id, nombre FROM subcategorias WHERE parent_id IS NULL AND id_usuario = ?";
                        $stmt_padres = $conexion->prepare($sql_padres);
                        $stmt_padres->bind_param("i", $id_usuario);
                        $stmt_padres->execute();
                        $padres = $stmt_padres->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt_padres->close();
                        foreach ($padres as $padre): ?>
                            <option value="<?php echo $padre['id']; ?>"><?php echo htmlspecialchars($padre['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Guardar Subcategoría</button>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 y animaciones -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" />
<script>
function confirmarEliminacion(nombre, url) {
    Swal.fire({
        title: '<span style="color:#d33;font-weight:bold;">¿Eliminar? 🗑️</span>',
        html: '¿Estás seguro de que deseas eliminar <b>"' + nombre + '"</b>?<br><small>Esta acción no se puede deshacer.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        focusCancel: true,
        background: '#fffbe6',
        customClass: { popup: 'shadow rounded' },
        showClass: { popup: 'animate__animated animate__fadeInDown' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}
// Mensajes de éxito/error con iconos y animación
window.addEventListener('DOMContentLoaded', function() {
    var msgSuccess = <?php echo isset($_SESSION['success']) ? json_encode($_SESSION['success']) : 'null'; unset($_SESSION['success']); ?>;
    var msgError = <?php echo isset($_SESSION['error']) ? json_encode($_SESSION['error']) : 'null'; unset($_SESSION['error']); ?>;
    if (msgSuccess) {
        Swal.fire({
            icon: 'success',
            title: '<span style="color:#27ae60;font-weight:bold;">¡Éxito! ✔️</span>',
            html: '<div style="font-size:1.1rem;">' + msgSuccess + '</div>',
            timer: 3000,
            showConfirmButton: false,
            background: '#eafaf1',
            customClass: { popup: 'shadow rounded' },
            showClass: { popup: 'animate__animated animate__fadeInDown' },
            hideClass: { popup: 'animate__animated animate__fadeOutUp' }
        });
    }
    if (msgError) {
        Swal.fire({
            icon: 'error',
            title: '<span style="color:#d33;font-weight:bold;">Error ❌</span>',
            html: '<div style="font-size:1.1rem;">' + msgError + '</div>',
            timer: 4000,
            showConfirmButton: false,
            background: '#fdecea',
            customClass: { popup: 'shadow rounded' },
            showClass: { popup: 'animate__animated animate__shakeX' },
            hideClass: { popup: 'animate__animated animate__fadeOutUp' }
        });
    }
    // Modal de ayuda contextual (puedes poner un botón con id 'ayuda-btn')
    var ayudaBtn = document.getElementById('ayuda-btn');
    if (ayudaBtn) {
        ayudaBtn.addEventListener('click', function() {
            Swal.fire({
                title: '<span style="color:#3085d6;">Ayuda rápida 💡</span>',
                html: '<ul style="text-align:left;font-size:1.1rem;"><li>Gestiona tus <b>categorías</b> y <b>subcategorías</b> para organizar tus gastos.</li><li>Elimina con seguridad usando los botones rojos.</li><li>Recuerda: las categorías principales son fijas.</li></ul>',
                icon: 'info',
                confirmButtonText: '¡Entendido! 👍',
                background: '#f0f8ff',
                customClass: { popup: 'shadow rounded' }
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
