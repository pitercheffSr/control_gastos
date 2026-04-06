<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
include '../db.php'; // Usamos la conexión PDO

$id_usuario = $_SESSION['usuario_id'];

// No necesitamos cargar categorías aquí, el JS lo hará.
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Nuevo Movimiento Manual</h3>
                </div>
                <div class="card-body">
                    <form id="formNuevaTransaccion">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="t_fecha" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="t_fecha" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="t_monto" class="form-label">Importe</label>
                                <input type="number" step="0.01" class="form-control" id="t_monto" placeholder="Ej: -25.50 o 1200" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="t_desc" class="form-label">Descripción</label>
                            <input type="text" class="form-control" id="t_desc" placeholder="Ej: Compra en supermercado" required>
                        </div>

                        <div class="mb-3">
                            <label for="t_tipo" class="form-label">Tipo de Movimiento</label>
                            <select id="t_tipo" class="form-select">
                                <option value="gasto">Gasto</option>
                                <option value="ingreso">Ingreso</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría (Opcional)</label>
                            <div class="input-group">
                                <select id="t_cat" class="form-select"><option value="">-- Categoría --</option></select>
                                <select id="t_subcat" class="form-select"><option value="">-- Subcategoría --</option></select>
                                <select id="t_subsub" class="form-select"><option value="">-- Detalle --</option></select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Guardar Movimiento</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- El JS para este formulario ya existe y debería cargarse con el footer -->
<?php include '../includes/footer.php'; ?>