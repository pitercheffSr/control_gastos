<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Importar Movimientos desde CSV</h2>
    <p>Sube un archivo CSV con tus movimientos bancarios para clasificarlos y añadirlos a tu cuenta.</p>

    <div class="card bg-light mb-4">
        <div class="card-body">
            <h5 class="card-title">¡Importante! Antes de subir</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-light">Asegúrate de que tu archivo CSV contenga columnas como <strong>Fecha</strong>, <strong>Descripción</strong> e <strong>Importe</strong>.</li>
                <li class="list-group-item bg-light">Por seguridad, <strong>elimina cualquier columna con tu número de cuenta, IBAN</strong> o datos personales sensibles. El sistema intentará detectarlo y rechazará el archivo.</li>
                <li class="list-group-item bg-light">El sistema intentará auto-clasificar los movimientos basándose en las descripciones. Podrás revisarlos antes de guardarlos.</li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="form-importar">
                <div class="mb-3">
                    <label for="archivo_csv" class="form-label">Selecciona tu archivo CSV</label>
                    <input class="form-control" type="file" id="archivo_csv" name="archivo_csv" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Procesar Archivo</button>
            </form>
        </div>
    </div>

    <!-- Área de resultados y revisión -->
    <div id="area-revision" class="d-none">
        <hr>
        <h4>Revisa y Confirma tus Movimientos</h4>
        <p>Hemos procesado tu archivo. Corrige las categorías si es necesario y luego haz clic en "Guardar Todo".</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Importe</th>
                        <th>Categoría Sugerida</th>
                    </tr>
                </thead>
                <tbody id="tabla-revision-body">
                    <!-- Las filas se insertarán aquí con JavaScript -->
                </tbody>
            </table>
        </div>
        <button id="btn-guardar-todo" class="btn btn-success w-100 mt-3">Guardar Todos los Movimientos</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
document.addEventListener('DOMContentLoaded', () => {
    const formImportar = document.getElementById('form-importar');
    const areaRevision = document.getElementById('area-revision');
    const tablaBody = document.getElementById('tabla-revision-body');
    const btnGuardarTodo = document.getElementById('btn-guardar-todo');
    let transaccionesParaGuardar = [];

    // 1. Procesar el archivo CSV
    formImportar.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formImportar);
        const submitButton = formImportar.querySelector('button');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

        try {
            const response = await fetch('procesar_importacion.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'error') {
                Swal.fire('Error', result.message, 'error');
                return;
            }

            transaccionesParaGuardar = result.data;
            renderizarTablaRevision(result.data, result.categorias_disponibles);

        } catch (error) {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Procesar Archivo';
        }
    });

    // 2. Renderizar la tabla de revisión
    function renderizarTablaRevision(transacciones, categoriasDisponibles) {
        tablaBody.innerHTML = '';
        // Construir el HTML de las opciones una sola vez para mayor eficiencia
        const categoriasOptionsHTML = categoriasDisponibles.map(cat =>
            `<option value="${escapeHtml(cat.id)}">${escapeHtml(cat.nombre)}</option>`
        ).join('');

        transacciones.forEach((trx, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(trx.fecha)}</td>
                <td>${escapeHtml(trx.descripcion)}</td>
                <td class="text-end ${trx.importe > 0 ? 'text-success' : 'text-danger'}">${parseFloat(trx.importe).toFixed(2)} €</td>
                <td>
                    <select class="form-select form-select-sm" data-index="${index}">
                        <option value="">-- Sin Clasificar --</option>
                        ${categoriasOptionsHTML}
                    </select>
                </td>
            `;
            tablaBody.appendChild(row);
            // Pre-seleccionar la categoría sugerida
            const select = row.querySelector('select');
            if (trx.categoria_id) {
                select.value = trx.categoria_id;
            }
        });
        areaRevision.classList.remove('d-none');
    }

    // 3. Guardar todo
    btnGuardarTodo.addEventListener('click', async () => {
        // Actualizar las transacciones con las categorías seleccionadas por el usuario
        const selects = tablaBody.querySelectorAll('select');
        selects.forEach(select => {
            const index = select.dataset.index;
            transaccionesParaGuardar[index].categoria_id = select.value || null;
        });

        btnGuardarTodo.disabled = true;
        btnGuardarTodo.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        try {
            const response = await fetch('../controllers/TransaccionRouter.php?action=saveBulk', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrf_token
                },
                body: JSON.stringify(transaccionesParaGuardar)
            });
            const result = await response.json();

            if (result.success) {
                let successMessage = `Se importaron ${result.inserted} nuevos movimientos.`;
                if (result.skipped > 0) {
                    successMessage += `\nSe omitieron ${result.skipped} por ser duplicados.`;
                }

                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: successMessage,
                    willClose: () => {
                        window.location.href = 'transacciones.php'; // O a donde quieras redirigir
                    }
                });
            } else {
                Swal.fire('Error', result.error || 'Ocurrió un problema al guardar.', 'error');
            }

        } catch (error) {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
        } finally {
            btnGuardarTodo.disabled = false;
            btnGuardarTodo.innerHTML = 'Guardar Todos los Movimientos';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>