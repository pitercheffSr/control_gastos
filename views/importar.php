<?php
require_once '../config.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
include '../includes/header.php';
?>

<div class="container mx-auto p-6 max-w-5xl min-h-screen pb-24">
    <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Importar Movimientos</h1>
    <p class="text-sm text-gray-500 mt-1 mb-6">Sube un archivo CSV con tus movimientos bancarios para clasificarlos y añadirlos a tu cuenta.</p>

    <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl mb-8 shadow-sm">
        <h5 class="text-blue-800 font-bold text-lg flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            ¡Importante! Antes de subir
        </h5>
        <ul class="mt-4 space-y-3 text-sm text-blue-900/80">
            <li class="flex items-start gap-2"><span class="text-blue-500 font-bold">➜</span> Asegúrate de que tu archivo CSV contenga columnas como <strong>Fecha</strong>, <strong>Descripción</strong> e <strong>Importe</strong>.</li>
            <li class="flex items-start gap-2"><span class="text-blue-500 font-bold">➜</span> Por seguridad, <strong>elimina cualquier columna con tu número de cuenta o IBAN</strong>. El sistema intentará detectarlo y rechazará el archivo automáticamente.</li>
            <li class="flex items-start gap-2"><span class="text-blue-500 font-bold">➜</span> El sistema intentará auto-clasificar los movimientos basándose en las descripciones. Podrás revisarlos todos antes de guardarlos.</li>
        </ul>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <form id="form-importar">
            <input type="hidden" name="csrf_token" id="csrf_token_input" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="mb-6">
                <label for="archivo_csv" class="block text-sm font-bold text-gray-700 mb-2">Selecciona tu archivo CSV</label>
                <input type="file" id="archivo_csv" name="archivo_csv" accept=".csv" required class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 outline-none transition file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center justify-center gap-2 w-full md:w-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Procesar Archivo
            </button>
        </form>
    </div>

    <!-- Área de resultados y revisión -->
    <div id="area-revision" class="hidden mt-8 pt-8 border-t border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Revisa y Confirma tus Movimientos</h2>
        <p class="text-sm text-gray-500 mb-6">Hemos procesado tu archivo. Corrige las categorías sugeridas si es necesario y luego haz clic en el botón de guardar inferior.</p>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Fecha</th>
                            <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Descripción</th>
                            <th class="p-4 text-right text-gray-500 font-bold tracking-wider uppercase text-xs">Importe</th>
                            <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Categoría Sugerida</th>
                            <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-revision-body" class="divide-y divide-gray-100">
                    <!-- Las filas se insertarán aquí con JavaScript -->
                </tbody>
            </table>
        </div>
        </div>
        <button id="btn-guardar-todo" class="w-full bg-green-600 text-white px-6 py-4 rounded-xl shadow-md hover:bg-green-700 font-extrabold text-lg transition flex items-center justify-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Guardar Todos los Movimientos
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Exponer el token CSRF global para usarlo en las llamadas AJAX
window.csrf_token = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return String(unsafe)
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
        
        const fileInput = document.getElementById('archivo_csv');
        if (!fileInput.files.length) return;
        const file = fileInput.files[0];
        const currentToken = document.getElementById('csrf_token_input').value;

        const submitButton = formImportar.querySelector('button');
        submitButton.disabled = true;
        submitButton.innerHTML = '⏳ Procesando archivo...';

        const reader = new FileReader();

        reader.onload = async function(event) {
            const csvContent = event.target.result;

            try {
                const response = await fetch('procesar_importacion.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': currentToken
                    },
                    body: JSON.stringify({
                        csrf_token: currentToken,
                        file_name: file.name,
                        csv_data: csvContent
                    })
                });

                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    const textResponse = await response.text();
                    console.error("Respuesta del servidor corrupta o bloqueada:", textResponse);
                    throw new Error("El servidor bloqueó la subida o la respuesta no es válida (revisa la consola F12).");
                }

                if (result.status === 'error') {
                    Swal.fire('Error', result.message, 'error');
                    return;
                }

                transaccionesParaGuardar = result.data;
                renderizarTablaRevision(result.data, result.categorias_disponibles);

            } catch (error) {
                Swal.fire('Error de Comunicación', error.message || 'No se pudo comunicar con el servidor.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg> Procesar Archivo';
            }
        };

        reader.onerror = function() {
            Swal.fire('Error', 'No se pudo leer el archivo en tu navegador.', 'error');
            submitButton.disabled = false;
            submitButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg> Procesar Archivo';
        };

        reader.readAsText(file);
    });

    // 2. Renderizar la tabla de revisión
    function renderizarTablaRevision(transacciones, categoriasDisponibles) {
        tablaBody.innerHTML = '';
        // Construir el HTML de las opciones una sola vez para mayor eficiencia
        const categoriasOptionsHTML = categoriasDisponibles.map(cat =>
            `<option value="${escapeHtml(cat.id)}">${escapeHtml(cat.nombre)}</option>`
        ).join('');

        transacciones.forEach((trx, index) => {
            if (trx.deleted) return; // No renderizar si está marcada para descartar

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="p-4 text-sm text-gray-500 font-medium">${escapeHtml(trx.fecha)}</td>
                <td class="p-4 font-bold text-gray-800">${escapeHtml(trx.descripcion)}</td>
                <td class="p-4 text-right font-extrabold ${trx.importe > 0 ? 'text-green-500' : 'text-red-500'}">${parseFloat(trx.importe).toFixed(2)} €</td>
                <td class="p-4">
                    <select class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white" data-index="${index}">
                        <option value="">-- Sin Clasificar --</option>
                        ${categoriasOptionsHTML}
                    </select>
                </td>
                <td class="p-4 text-center">
                    <button type="button" class="text-red-500 border border-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm font-bold transition btn-descartar" data-index="${index}" title="Descartar movimiento">🗑️ Descartar</button>
                </td>
            `;
            tablaBody.appendChild(row);
            // Pre-seleccionar la categoría sugerida
            const select = row.querySelector('select');
            if (trx.categoria_id) {
                select.value = trx.categoria_id;
            }
        });

        // Asignar eventos a los botones de descartar
        document.querySelectorAll('.btn-descartar').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = e.target.getAttribute('data-index');
                transaccionesParaGuardar[idx].deleted = true; // Marcar como descartado
                renderizarTablaRevision(transaccionesParaGuardar, categoriasDisponibles); // Re-renderizar la tabla
            });
        });

        areaRevision.classList.remove('hidden');
    }

    // 3. Guardar todo
    btnGuardarTodo.addEventListener('click', async () => {
        // Actualizar las transacciones y filtrar las descartadas
        const transaccionesFinales = [];
        const selects = tablaBody.querySelectorAll('select');
        selects.forEach(select => {
            const index = select.dataset.index;
            const trx = transaccionesParaGuardar[index];
            if (!trx.deleted) {
                trx.categoria_id = select.value || null;
                transaccionesFinales.push(trx);
            }
        });

        if (transaccionesFinales.length === 0) {
            Swal.fire('Aviso', 'No hay movimientos para guardar.', 'info');
            return;
        }

        btnGuardarTodo.disabled = true;
        btnGuardarTodo.innerHTML = '⏳ Guardando movimientos...';

        try {
            const tokenFinal = document.getElementById('csrf_token_input').value || window.csrf_token;
            const response = await fetch('../controllers/TransaccionRouter.php?action=saveBulk', {
                method: 'POST',
                credentials: 'include', // <-- OBLIGA A ENVIAR LA COOKIE DE SESIÓN AL SERVIDOR
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': tokenFinal
                },
                body: JSON.stringify(transaccionesFinales)
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
                        window.location.href = '../transacciones.php'; // Subimos un nivel hacia la raíz
                    }
                });
            } else {
                Swal.fire('Error', result.error || 'Ocurrió un problema al guardar.', 'error');
            }

        } catch (error) {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
        } finally {
            btnGuardarTodo.disabled = false;
            btnGuardarTodo.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar Todos los Movimientos';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>