<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
require_once '../config.php';

$id_usuario = $_SESSION['usuario_id'];

// Obtener categorías generales (id_usuario = 0) y personalizadas
$sql = "SELECT * FROM categorias WHERE id_usuario = 0 OR id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <?php foreach ($categorias as $categoria) : ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($categoria['tipo']); ?></span>
                            <span class="badge bg-info ms-2">Regla <?php echo htmlspecialchars($categoria['clasificacion']); ?>%</span>
                        </div>
                        <?php if ($categoria['id_usuario'] != 0) : ?>
                            <!-- Botón para eliminar solo categorías personalizadas -->
                            <a href="#" class="btn btn-sm btn-danger" onclick="confirmarEliminacion('<?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES); ?>', <?php echo $categoria['id']; ?>, 'delete_categoria', 'categoría'); return false;">Eliminar</a>
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
                $stmt_sub = $pdo->prepare($sql_sub);
                $stmt_sub->execute([$id_usuario]);
                $subcategorias = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

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
                foreach ($cat_map as $cat_id => $cat_nombre) :
                    if (!isset($tree[$cat_id])) {
                        continue;
                    }
                    ?>
                <li class="list-group-item bg-light"><strong><?php echo $cat_nombre; ?></strong></li>
                    <?php foreach ($tree[$cat_id] as $subcat) : ?>
                    <li class="list-group-item">
                        <span class="fw-bold"><?php echo htmlspecialchars($subcat['info']['nombre']); ?></span>
                        <a href="#" class="btn btn-sm btn-danger float-end ms-2" onclick="confirmarEliminacion('<?php echo htmlspecialchars($subcat['info']['nombre'], ENT_QUOTES); ?>', <?php echo $subcat['info']['id']; ?>, 'delete_subcategoria', 'subcategoría'); return false;">Eliminar</a>
                        <?php if (!empty($subcat['children'])) : ?>
                            <ul class="list-group mt-2 ms-3">
                                <?php foreach ($subcat['children'] as $child) : ?>
                                    <li class="list-group-item">
                                        <span><?php echo htmlspecialchars($child['nombre']); ?></span>
                                        <a href="#" class="btn btn-sm btn-danger float-end ms-2" onclick="confirmarEliminacion('<?php echo htmlspecialchars($child['nombre'], ENT_QUOTES); ?>', <?php echo $child['id']; ?>, 'delete_subcategoria', 'subcategoría'); return false;">Eliminar</a>
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
            <form id="form-add-subcategoria">
                <div class="mb-3">
                    <label for="nombre_sub" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre_sub" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label for="id_categoria_form" class="form-label">Categoría Principal</label>
                    <select class="form-select" id="id_categoria_form" name="id_categoria" required>
                        <option value="1">Necesidades (50%)</option>
                        <option value="2">Deseos (30%)</option>
                        <option value="3">Ahorro (20%)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="parent_id_form" class="form-label">Subcategoría Padre (opcional)</label>
                    <select class="form-select" id="parent_id_form" name="parent_id">
                        <option value="">Ninguna (subcategoría de primer nivel)</option>
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
<?php
// Obtenemos todas las subcategorías de primer nivel para el filtrado dinámico en JS
$sql_padres = "SELECT id, nombre, id_categoria FROM subcategorias WHERE parent_id IS NULL AND id_usuario = ?";
$stmt_padres = $pdo->prepare($sql_padres);
$stmt_padres->execute([$id_usuario]);
$padres_data = $stmt_padres->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
// Exponer el token CSRF global para llamadas AJAX
window.csrf_token = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

async function confirmarEliminacion(nombre, itemId, action, itemType = 'elemento') {
    const result = await Swal.fire({
            title: '<span style="color:#d33;font-weight:bold;">¿Eliminar? 🗑️</span>',
            html: `¿Estás seguro de que deseas eliminar la ${itemType} <b>"${nombre}"</b>?<br><small>Esta acción no se puede deshacer.</small>`,
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
        });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', itemId);

            const response = await fetch('procesar_categoria.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrf_token
                },
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: res.message || `El ${itemType} ha sido eliminado.`,
                    timer: 2000,
                    showConfirmButton: false,
                    willClose: () => window.location.reload()
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || `No se pudo eliminar el ${itemType}.` });
            }
        } catch (error) {
            console.error('Error en la eliminación:', error);
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo comunicar con el servidor.' });
        }
    }
}
// Mensajes de éxito/error con iconos y animación
window.addEventListener('DOMContentLoaded', function() {
    var msgSuccess = <?php echo isset($_SESSION['success']) ? json_encode($_SESSION['success']) : 'null';
    unset($_SESSION['success']); ?>;
    var msgError = <?php echo isset($_SESSION['error']) ? json_encode($_SESSION['error']) : 'null';
    unset($_SESSION['error']); ?>;
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

    // --- Lógica para el formulario de subcategorías con AJAX ---
    const todosLosPadres = <?php echo json_encode($padres_data); ?>;
    const categoriaFormSelect = document.getElementById('id_categoria_form');
    const parentFormSelect = document.getElementById('parent_id_form');

    function actualizarPadresDisponibles() {
        const categoriaSeleccionada = categoriaFormSelect.value;
        const padresFiltrados = todosLosPadres.filter(p => p.id_categoria == categoriaSeleccionada);

        // Limpiar opciones existentes (excepto la primera)
        parentFormSelect.innerHTML = '<option value="">Ninguna (subcategoría de primer nivel)</option>';

        // Añadir nuevas opciones filtradas
        padresFiltrados.forEach(padre => {
            const option = document.createElement('option');
            option.value = padre.id;
            option.textContent = padre.nombre;
            parentFormSelect.appendChild(option);
        });
    }

    // Actualizar al cambiar la categoría principal y al cargar la página
    if (categoriaFormSelect) {
        categoriaFormSelect.addEventListener('change', actualizarPadresDisponibles);
        actualizarPadresDisponibles(); // Para el estado inicial
    }

    const formSubcategoria = document.getElementById('form-add-subcategoria');
    if (formSubcategoria) {
        formSubcategoria.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(formSubcategoria);
            formData.append('action', 'add_subcategoria');

            const submitButton = formSubcategoria.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            try {
                const response = await fetch('procesar_categoria.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrf_token
                    },
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message || 'Subcategoría creada correctamente.',
                        timer: 2000,
                        showConfirmButton: false,
                        willClose: () => window.location.reload() // Recarga la página al cerrar
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'No se pudo crear la subcategoría.' });
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Guardar Subcategoría';
                }
            } catch (error) {
                console.error('Error al enviar el formulario:', error);
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo comunicar con el servidor.' });
                submitButton.disabled = false;
                submitButton.innerHTML = 'Guardar Subcategoría';
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
