        </div> <!-- Cierre del container -->
    <!-- Enlace a Bootstrap JS -->

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodoSelect = document.getElementById('periodo');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');

            // Función para limpiar los campos de fecha
            function clearDateInputs() {
                fechaInicioInput.value = '';
                fechaFinInput.value = '';
            }

            // Evento que se dispara al cambiar el periodo predefinido
            periodoSelect.addEventListener('change', function() {
                if (this.value !== 'todos') {
                    clearDateInputs();
                }
            });

            // Evento que se dispara al cambiar la fecha de inicio
            fechaInicioInput.addEventListener('change', function() {
                if (this.value !== '') {
                    periodoSelect.value = 'todos';
                }
            });

            // Evento que se dispara al cambiar la fecha de fin
            fechaFinInput.addEventListener('change', function() {
                if (this.value !== '') {
                    periodoSelect.value = 'todos';
                }
            });
        });
           // Nuevo código para sincronizar categoría y tipo
        const idCategoriaSelect = document.getElementById('id_categoria');
        const tipoSelect = document.getElementById('tipo');

        idCategoriaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const selectedType = selectedOption.getAttribute('data-type');

            if (selectedType) {
                tipoSelect.value = selectedType;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>
