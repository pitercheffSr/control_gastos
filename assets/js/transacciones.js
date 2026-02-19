document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalTransaccion');
    const form = document.getElementById('formTransaccion');
    const selectCat = document.getElementById('categoria_id');
    const filterCat = document.getElementById('filterCategory');

    async function cargarCategorias() {
        const res = await fetch('controllers/TransaccionRouter.php?action=getCategorias');
        const cats = await res.json();
        const options = cats.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
        selectCat.innerHTML = '<option value="">Seleccione...</option>' + options;
        if(filterCat) filterCat.innerHTML = '<option value="">Todas</option>' + options;
    }

    window.openModal = function() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.transform').classList.replace('scale-90', 'scale-100');
        }, 10);
    };

    window.closeModal = function() {
        modal.classList.replace('opacity-100', 'opacity-0');
        modal.querySelector('.transform').classList.replace('scale-100', 'scale-90');
        setTimeout(() => modal.classList.add('hidden'), 300);
    };

    window.nuevaTransaccion = async function() {
        form.reset();
        document.getElementById('transaccion_id').value = '';
        document.getElementById('modalTitle').innerText = 'Nueva Transacción';
        await cargarCategorias();
        openModal();
    };

    window.editarTransaccion = async function(id) {
        await cargarCategorias();
        const res = await fetch(`controllers/TransaccionRouter.php?action=get&id=${id}`);
        const data = await res.json();
        document.getElementById('transaccion_id').value = data.id;
        document.getElementById('fecha').value = data.fecha;
        document.getElementById('descripcion').value = data.descripcion;
        document.getElementById('monto').value = data.monto;
        document.getElementById('categoria_id').value = data.categoria_id;
        document.getElementById('modalTitle').innerText = 'Editar Transacción';
        openModal();
    };

    form.onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        const res = await fetch('controllers/TransaccionRouter.php?action=save', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        if((await res.json()).success) location.reload();
    };

    window.filtrarTabla = function() {
        const mes = document.getElementById('filterMonth').value;
        const cat = document.getElementById('filterCategory').value;
        document.querySelectorAll('.transaccion-row').forEach(row => {
            const m = !mes || row.dataset.mes === mes;
            const c = !cat || row.dataset.categoria === cat;
            row.style.display = (m && c) ? '' : 'none';
        });
    };

    // Auto-abrir si viene de Dashboard
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'new') {
        window.nuevaTransaccion();
        window.history.replaceState({}, '', 'transacciones.php');
    }

    cargarCategorias();
});