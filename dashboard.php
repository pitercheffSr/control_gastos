<?php
define('ENVIRONMENT', 'production'); // Cambiar a 'development' para pruebas

// Configuración de manejo de errores
if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0); // Desactiva la visualización de errores
    ini_set('log_errors', 1);       // Activa el registro de errores
    ini_set('error_log', '/home/pedro/pitercheffSr/control_gastos/errores.log'); // Ruta al archivo de log
} else {
    ini_set('display_errors', 1);   // Activa la visualización de errores en desarrollo
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);           // Reporta todos los errores
}

session_start(); // Iniciar sesión para gestionar mensajes

if (isset($_SESSION['error'])) {
    // Verifica si hay un mensaje de error en la sesión
    echo '<div class="alert alert-danger mt-4">' . $_SESSION['error'] . '</div>'; // Muestra el mensaje de error
    unset($_SESSION['error']); // Elimina el mensaje después de mostrarlo
}

if (isset($_SESSION['success_transaccion'])) {
    // Verifica si hay un mensaje de éxito en la sesión
    echo '<div class="alert alert-success mt-4">' . $_SESSION['success_transaccion'] . '</div>'; // Muestra el mensaje de éxito
    unset($_SESSION['success_transaccion']); // Elimina el mensaje después de mostrarlo
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php"); // Redireccionar si no está autenticado
    exit; // Termina la ejecución del script
}

include 'includes/header.php'; // Incluye el encabezado
include 'includes/conexion.php'; // Incluye la conexión a la base de datos

$id_usuario = $_SESSION['usuario_id']; // Almacena el ID del usuario en una variable

// --- Manejo de filtros ---

$condiciones = [];
$tipos_parametros = "i";
$valores_parametros = [&$id_usuario];

// Manejar filtro por descripción
if (isset($_GET['descripcion_filtro']) && !empty($_GET['descripcion_filtro'])) {
    // Sanitizar y validar
    $descripcion_filtro = trim(strip_tags($_GET['descripcion_filtro']));
    if (strlen($descripcion_filtro) <= 255) { // Limitar longitud
        $descripcion_filtro = "%" . $descripcion_filtro . "%";
        $condiciones[] = "descripcion LIKE ?";
        $tipos_parametros .= "s";
        $valores_parametros[] = &$descripcion_filtro;
    } else {
        $_SESSION['error'] = "La descripción es demasiado larga (máximo 255 caracteres).";
    }
}

// Manejar filtro por período o rango de fechas
if (isset($_GET['periodo']) && $_GET['periodo'] !== 'todos') {
    // lógica de filtrado por período
} elseif (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio']) &&
          isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];

    // Validar formato de fecha
    if (DateTime::createFromFormat('Y-m-d', $fecha_inicio) && DateTime::createFromFormat('Y-m-d', $fecha_fin)) {
        $condiciones[] = "fecha BETWEEN ? AND ?";
        $tipos_parametros .= "ss";
        $valores_parametros[] = &$fecha_inicio;
        $valores_parametros[] = &$fecha_fin;
    } else {
        $_SESSION['error'] = "Formato de fecha incorrecto. Debe ser YYYY-MM-DD.";
    }
}

$condicion_sql = count($condiciones) > 1 ? " AND " . implode(" AND ", array_slice($condiciones, 1)) : "";
$condicion_sql_total = count($condiciones) > 0 ? " AND " . implode(" AND ", $condiciones) : "";
// --- Fin del manejo de filtros ---

// Consultar ingresos totales
$sql_ingresos = "SELECT SUM(monto) as ingresos FROM transacciones WHERE id_usuario = ? AND tipo = 'ingreso'{$condicion_sql_total}";
$stmt_ingresos = $conexion->prepare($sql_ingresos);
$stmt_ingresos->bind_param($tipos_parametros, ...$valores_parametros);
$stmt_ingresos->execute();
$resultado_ingresos = $stmt_ingresos->get_result();
$ingresos = $resultado_ingresos->fetch_assoc()['ingresos'] ?? 0;
$stmt_ingresos->close();

// Consultar gastos totales
$sql_gastos = "SELECT SUM(monto) as gastos FROM transacciones WHERE id_usuario = ? AND tipo = 'gasto'{$condicion_sql_total}";
$stmt_gastos = $conexion->prepare($sql_gastos);
$stmt_gastos->bind_param($tipos_parametros, ...$valores_parametros);
$stmt_gastos->execute();
$resultado_gastos = $stmt_gastos->get_result();
$gastos = $resultado_gastos->fetch_assoc()['gastos'] ?? 0;
$stmt_gastos->close();

$balance = $ingresos - $gastos;

// Calcular gastos por categoría
$gastos_por_categoria = [];
$sql_categorias = "SELECT categoria, SUM(monto) as total FROM transacciones WHERE id_usuario = ? AND tipo = 'gasto'{$condicion_sql_total} GROUP BY categoria";
$stmt_categorias = $conexion->prepare($sql_categorias);
$stmt_categorias->bind_param($tipos_parametros, ...$valores_parametros);
$stmt_categorias->execute();
$resultado_categorias = $stmt_categorias->get_result();
while ($fila = $resultado_categorias->fetch_assoc()) {
    $gastos_por_categoria[$fila['categoria']] = $fila['total'];
}
$stmt_categorias->close();

// Calcular presupuestos según la regla 50/30/20
$presupuesto_50 = $ingresos * 0.50;
$presupuesto_30 = $ingresos * 0.30;
$presupuesto_20 = $ingresos * 0.20;

// Obtener transacciones recientes
$sql_recientes = "SELECT * FROM transacciones WHERE id_usuario = ?{$condicion_sql_total} ORDER BY fecha DESC";
$stmt_recientes = $conexion->prepare($sql_recientes);
$stmt_recientes->bind_param($tipos_parametros, ...$valores_parametros);
$stmt_recientes->execute();
$resultado_recientes = $stmt_recientes->get_result();

// Obtener categorías para el formulario
$sql_form_categorias = "SELECT * FROM categorias WHERE id_usuario = 0 OR id_usuario = ? ORDER BY nombre ASC";
$stmt_form_categorias = $conexion->prepare($sql_form_categorias);
$stmt_form_categorias->bind_param("i", $id_usuario);
$stmt_form_categorias->execute();
$resultado_form_categorias = $stmt_form_categorias->get_result();
$form_categorias = $resultado_form_categorias->fetch_all(MYSQLI_ASSOC);
$stmt_form_categorias->close();
?>

<div class="row">
    <!-- Columna principal (izquierda) -->
    <div class="col-md-8">
        <h1 class="mb-4">Panel de Control</h1>

        <!-- Tarjeta de Filtros -->
        <div class="card p-3 mb-4">
            <h5 class="mb-3">Filtrar por Fecha y Descripción</h5>
            <form action="dashboard.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="periodo" class="form-label">Período predefinido</label>
                    <select class="form-select" id="periodo" name="periodo">
                        <option value="todos" <?php echo isset($_GET['periodo']) && $_GET['periodo'] == 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="semanal" <?php echo isset($_GET['periodo']) && $_GET['periodo'] == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                        <option value="quincenal" <?php echo isset($_GET['periodo']) && $_GET['periodo'] == 'quincenal' ? 'selected' : ''; ?>>Quincenal</option>
                        <option value="mensual" <?php echo isset($_GET['periodo']) && $_GET['periodo'] == 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                        <option value="anual" <?php echo isset($_GET['periodo']) && $_GET['periodo'] == 'anual' ? 'selected' : ''; ?>>Anual</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha de fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                <div class="col-md-12 mt-3">
                    <label for="descripcion_filtro" class="form-label">Buscar en descripción</label>
                    <input type="text" class="form-control" id="descripcion_filtro" name="descripcion_filtro" value="<?php echo isset($_GET['descripcion_filtro']) ? htmlspecialchars($_GET['descripcion_filtro']) : ''; ?>">
                </div>
            </form>
        </div>

        <!-- Tarjeta de Resumen de Balance -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between mb-3">
                <h4>Balance Actual:</h4>
                <h4><?php echo number_format($balance, 2); ?> €</h4>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span>Ingresos:</span>
                <span class="text-success">+<?php echo number_format($ingresos, 2); ?> €</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Gastos:</span>
                <span class="text-danger">-<?php echo number_format($gastos, 2); ?> €</span>
            </div>
        </div>

        <!-- Tarjeta de Desglose 50/30/20 -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">Desglose 50/30/20</h4>

            <h5>50% - Necesidades</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, ($ingresos > 0 ? (($gastos_por_categoria['50'] ?? 0) / $presupuesto_50) * 100 : 0)); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['50'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_50; ?>">
                    <?php echo number_format($gastos_por_categoria['50'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_50, 2); ?> €
                </div>
            </div>

            <h5>30% - Deseos</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, ($ingresos > 0 ? (($gastos_por_categoria['30'] ?? 0) / $presupuesto_30) * 100 : 0)); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['30'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_30; ?>">
                    <?php echo number_format($gastos_por_categoria['30'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_30, 2); ?> €
                </div>
            </div>

            <h5>20% - Ahorro/Deudas</h5>
            <div class="progress mb-2">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, ($ingresos > 0 ? (($gastos_por_categoria['20'] ?? 0) / $presupuesto_20) * 100 : 0)); ?>%" aria-valuenow="<?php echo $gastos_por_categoria['20'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="<?php echo $presupuesto_20; ?>">
                    <?php echo number_format($gastos_por_categoria['20'] ?? 0, 2); ?> € / <?php echo number_format($presupuesto_20, 2); ?> €
                </div>
            </div>
        </div>

        <!-- Tarjeta de Transacciones Recientes -->
        <div class="card p-4 mt-4">
            <h4>Transacciones Recientes</h4>
            <ul class="list-group">
                <?php if ($resultado_recientes->num_rows > 0): ?>
                    <?php while ($transaccion = $resultado_recientes->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span><?php echo $transaccion['descripcion']; ?></span>
                                <small class="text-muted d-block"><?php echo $transaccion['fecha']; ?></small>
                            </div>
                            <span>
                                <span class="badge bg-<?php echo ($transaccion['tipo'] == 'ingreso') ? 'success' : 'danger'; ?> rounded-pill me-2">
                                    <?php echo ($transaccion['tipo'] == 'ingreso' ? '+' : '-') . number_format($transaccion['monto'], 2); ?> €
                                </span>
                                <a href="eliminar_transaccion.php?id=<?php echo $transaccion['id']; ?>" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> <!-- Icono de papelera -->
                                </a>
                            </span>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item text-center text-muted">Aún no hay transacciones registradas.</li>
                <?php endif; ?>
            </ul>
        </div>

        <?php $stmt_recientes->close(); ?>
    </div>

    <!-- Columna del formulario (derecha) -->
    <div class="col-md-4">
        <h4 class="mt-4 mt-md-0">Registrar Transacción</h4>
        <div class="card p-4">
            <form action="procesar_transaccion.php" method="POST">
                <!-- AQUI VA EL TIPO  -->
                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="gasto" selected>Gasto</option>
                        <option value="ingreso">Ingreso</option>
                    </select>
                </div>
                <!-- FIN DEL CAMPO TIPO -->
                <!-- CAMPO DE CATEGORIA -->
                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                        <option value="1">Necesidades (50%)</option>
                        <option value="2">Deseos (30%)</option>
                        <option value="3">Ahorro (20%)</option>
                    </select>
                    <!-- Select específico para ingresos (Nomina / Pension) -->
                    <select class="form-select mt-2 d-none" id="ingreso_origen" name="ingreso_origen">
                        <option value="nomina">Nómina</option>
                        <option value="pension">Pensión</option>
                    </select>
                    <!-- Select de subcategoría de primer nivel -->
                    <div class="mb-3" id="subcategoria-container" style="display: none;">
                        <label for="subcategoria" class="form-label">Subcategoría</label>
                        <select class="form-select" id="subcategoria" name="subcategoria">
                            <option value="">Seleccione una subcategoría</option>
                        </select>
                    </div>
                    <!-- Select de sub-subcategoría -->
                    <div class="mb-3" id="subsubcategoria-container" style="display: none;">
                        <label for="subsubcategoria" class="form-label">Sub-subcategoría</label>
                        <select class="form-select" id="subsubcategoria" name="subsubcategoria">
                            <option value="">Seleccione una sub-subcategoría</option>
                        </select>
                    </div>
                </div>
                <!-- FIN DEL CAMPO DE CATEGORIA -->

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                </div>
                <div class="mb-3">
                    <label for="monto" class="form-label">Monto</label>
                    <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrar</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Modal de bienvenida con icono y animación
if (!sessionStorage.getItem('bienvenidaMostrada')) {
    Swal.fire({
        title: '<span style="color:#3085d6;font-weight:bold;font-size:2rem;">¡Bienvenido! 🎉</span>',
        html: '<div style="font-size:1.1rem;">Gestiona tus <b>gastos</b> de forma visual y sencilla.<br><small>Utiliza los menús para registrar y analizar tus movimientos.</small></div>',
        icon: 'info',
        background: '#f0f8ff',
        showClass: { popup: 'animate__animated animate__fadeInDown' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp' },
        confirmButtonText: '¡Empezar!',
        confirmButtonColor: '#3085d6',
        customClass: { popup: 'shadow-lg rounded' }
    });
    sessionStorage.setItem('bienvenidaMostrada', '1');
}
// Advertencia de presupuesto con icono y color
if (typeof gastos_por_categoria !== 'undefined' && typeof presupuesto_50 !== 'undefined') {
    if (gastos_por_categoria['50'] > presupuesto_50) {
        Swal.fire({
            icon: 'warning',
            title: '<span style="color:#d33;font-weight:bold;">¡Atención! ⚠️</span>',
            html: '<b>Has superado el presupuesto de Necesidades (50%).</b><br>Revisa tus gastos para evitar sorpresas.',
            background: '#fffbe6',
            timer: 5000,
            showConfirmButton: false,
            customClass: { popup: 'shadow rounded' }
        });
    }
}
// Confirmación de logout con icono
window.addEventListener('DOMContentLoaded', function() {
    var logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '<span style="color:#d33;">¿Cerrar sesión? 🚪</span>',
                text: '¿Seguro que deseas salir de tu cuenta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                customClass: { popup: 'shadow rounded' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutBtn.getAttribute('href');
                }
            });
        });
    }
    // Ayuda contextual con icono
    var ayudaBtn = document.getElementById('ayuda-btn');
    if (ayudaBtn) {
        ayudaBtn.addEventListener('click', function() {
            Swal.fire({
                title: '<span style="color:#3085d6;">Ayuda rápida 💡</span>',
                html: '<ul style="text-align:left;font-size:1.1rem;"><li>Registra tus <b>gastos</b> e <b>ingresos</b> desde el formulario.</li><li>Usa las <b>subcategorías</b> para mayor detalle.</li><li>Consulta los <b>gráficos</b> y <b>listados</b> para analizar tu economía.</li></ul>',
                icon: 'info',
                confirmButtonText: '¡Entendido! 👍',
                background: '#f0f8ff',
                customClass: { popup: 'shadow rounded' }
            });
        });
    }
    // Mensajes de éxito/error con iconos y animación
    var msgSuccess = <?php echo isset($_SESSION['success_transaccion']) ? json_encode($_SESSION['success_transaccion']) : 'null'; unset($_SESSION['success_transaccion']); ?>;
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
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" />
<script>
// Mostrar/ocultar selects según el tipo seleccionado
document.addEventListener('DOMContentLoaded', function () {
    const tipoSelect = document.getElementById('tipo');
    const catSelect = document.getElementById('id_categoria');
    const ingresoSelect = document.getElementById('ingreso_origen');
    const subcatContainer = document.getElementById('subcategoria-container');

    function updateSelects() {
        if (!tipoSelect) return;
        if (tipoSelect.value === 'ingreso') {
            // mostrar origen de ingreso y ocultar categorias de gasto
            if (ingresoSelect) ingresoSelect.classList.remove('d-none');
            if (catSelect) catSelect.classList.add('d-none');
            // quitar required para catSelect
            if (catSelect) catSelect.removeAttribute('required');
            // ocultar subcategoría
            if (subcatContainer) subcatContainer.style.display = 'none';
        } else {
            if (ingresoSelect) ingresoSelect.classList.add('d-none');
            if (catSelect) catSelect.classList.remove('d-none');
            if (catSelect) catSelect.setAttribute('required', 'required');
            // mostrar subcategoría si existe
            if (subcatContainer) subcatContainer.style.display = 'block';
        }
    }

    if (tipoSelect) {
        tipoSelect.addEventListener('change', updateSelects);
        updateSelects();
    }
});
</script>
<script src="js/categoria_subcategoria.js"></script>

