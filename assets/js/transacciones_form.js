/**
 * transacciones_form.js
 * 
 * Lógica para un formulario de creación de transacciones dedicado (no el panel lateral).
 * Este código está refactorizado para usar el endpoint unificado `action=save`.
 * 
 * ASUME que en tu HTML tienes un formulario con id="formNuevaTransaccion" y campos
 * con los IDs: t_fecha, t_desc, t_monto, t_tipo, t_cat, t_subcat, t_subsub.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formNuevaTransaccion');
    if (!form) {
        // Si no se encuentra el formulario, no hacemos nada.
        // Esto evita errores si el script se carga en páginas que no lo tienen.
        return;
    }

    // --- Referencias a los campos del formulario ---
    // Usamos prefijo 't_' para evitar colisiones con el panel de edición ('e_')
    const fFecha = document.getElementById('t_fecha');
    const fDesc = document.getElementById('t_desc');
    const fMonto = document.getElementById('t_monto');
    const fTipo = document.getElementById('t_tipo');
    const fCat = document.getElementById('t_cat');
    const fSubcat = document.getElementById('t_subcat');
    const fSubsub = document.getElementById('t_subsub');

    // Usamos la nueva función reutilizable para cargar las categorías en cascada.
    // La función `initializeCascadingCategories` debe estar disponible globalmente.
    if (fCat && fSubcat && fSubsub) {
        initializeCascadingCategories({
            cat: fCat,
            subcat: fSubcat,
            subsubcat: fSubsub
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 1. Determinar la categoría final (la más específica seleccionada)
        // Si no se selecciona ninguna, el valor será `null`, que es lo que la BD espera.
        const categoriaFinalId = fSubsub.value || fSubcat.value || fCat.value || null;

        // 2. Construir el payload unificado
        const payload = {
            id: null, // Siempre null porque este formulario es para crear
            fecha: fFecha.value,
            descripcion: fDesc.value,
            monto: fMonto.value,
            tipo: fTipo.value,
            categoria_id: categoriaFinalId,
        };

        // Validación básica
        if (!payload.fecha || !payload.monto) {
            alert("La fecha y el importe son campos obligatorios.");
            return;
        }

        try {
            // 3. Enviar los datos al endpoint unificado 'save'
            const resp = await fetch(`controllers/TransaccionRouter.php?action=save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrf_token // Asegúrate de que el token CSRF esté disponible
                },
                body: JSON.stringify(payload)
            });

            const json = await resp.json();

            if (json.success) {
                alert('Transacción guardada con éxito.');
                window.location.href = 'transacciones.php'; // Redirigir a la lista
            } else {
                alert('Error al guardar: ' + (json.error || 'Ocurrió un problema.'));
            }
        } catch (err) {
            console.error('Error en la petición de guardado:', err);
            alert('Error de conexión. No se pudo guardar la transacción.');
        }
    });
});