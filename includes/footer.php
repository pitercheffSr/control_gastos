        </div> <!-- Cierre del container -->
    <!-- No se usa Bootstrap; eliminar script innecesario -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodoSelect = document.getElementById('periodo');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');

            // Función para limpiar los campos de fecha
            function clearDateInputs() {
                if (!fechaInicioInput || !fechaFinInput) return;
                fechaInicioInput.value = '';
                fechaFinInput.value = '';
            }

            // Añadir manejadores solo si los elementos existen
            if (periodoSelect && fechaInicioInput && fechaFinInput) {
                periodoSelect.addEventListener('change', function() {
                    if (this.value !== 'todos') {
                        clearDateInputs();
                    }
                });

                fechaInicioInput.addEventListener('change', function() {
                    if (this.value !== '') {
                        periodoSelect.value = 'todos';
                    }
                });

                fechaFinInput.addEventListener('change', function() {
                    if (this.value !== '') {
                        periodoSelect.value = 'todos';
                    }
                });
            }
        });
           // Nuevo código para sincronizar categoría y tipo
        const idCategoriaSelect = document.getElementById('id_categoria');
        const tipoSelect = document.getElementById('tipo');

        if (idCategoriaSelect && tipoSelect) {
            idCategoriaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const selectedType = selectedOption ? selectedOption.getAttribute('data-type') : null;

                if (selectedType) {
                    tipoSelect.value = selectedType;
                }
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>
