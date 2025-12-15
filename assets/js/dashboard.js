// =========================================================
//  DASHBOARD JS — Versión estable y validada
//  Funciona: menú lateral, toggle, filtros, tabla y totales
// =========================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Dashboard.js cargado correctamente');

    const state = {
        page: 1,
        per_page: 50,
        filter_type: 'month',
    };

    // --------------------------
    // Helpers
    // --------------------------
    const qs = (id) => document.getElementById(id);

    const fmtCurrency = (v) => {
        return Number(v || 0).toLocaleString('es-ES', {
            style: 'currency',
            currency: 'EUR',
        });
    };

    // --------------------------
    // Init selects
    // --------------------------
    function initDateSelectors() {
        const monthSel = qs('filter_month');
        const yearSel = qs('filter_year');
        const now = new Date();

        // Meses
        for (let m = 1; m <= 12; m++) {
            let opt = document.createElement('option');
            opt.value = m;
            opt.text = m;
            monthSel.appendChild(opt);
        }

        // Años
        for (let y = now.getFullYear(); y >= now.getFullYear() - 5; y--) {
            let opt = document.createElement('option');
            opt.value = y;
            opt.text = y;
            yearSel.appendChild(opt);
        }

        monthSel.value = now.getMonth() + 1;
        yearSel.value = now.getFullYear();
    }

    // --------------------------
    // Update filter inputs visibility
    // --------------------------
    function updateFilterInputs() {
        const ft = qs('filter_type').value;

        qs('filter_date').style.display = 'none';
        qs('filter_date_from').style.display = 'none';
        qs('filter_date_to').style.display = 'none';
        qs('filter_month').style.display = 'none';
        qs('filter_year').style.display = 'none';

        if (ft === 'day' || ft === 'week')
            qs('filter_date').style.display = 'inline-block';

        if (ft === 'month') {
            qs('filter_month').style.display = 'inline-block';
            qs('filter_year').style.display = 'inline-block';
        }

        if (ft === 'range') {
            qs('filter_date_from').style.display = 'inline-block';
            qs('filter_date_to').style.display = 'inline-block';
        }
    }

    // --------------------------
    // Fetch data
    // --------------------------
    async function fetchData() {
        const params = new URLSearchParams();
        params.set('filter_type', state.filter_type);
        params.set('page', state.page);

        if (state.filter_type === 'month') {
            params.set('month', qs('filter_month').value);
            params.set('year', qs('filter_year').value);
        }

        if (state.filter_type === 'day' || state.filter_type === 'week') {
            const d =
                qs('filter_date').value ||
                new Date().toISOString().slice(0, 10);
            params.set('date', d);
        }

        if (state.filter_type === 'range') {
            params.set('date_from', qs('filter_date_from').value);
            params.set('date_to', qs('filter_date_to').value);
        }

        const r = await fetch('/control_gastos/api/ftch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params,
        });

        const text = await r.text();
        console.log('Respuesta RAW ftch.php:', text);

        try {
            const data = JSON.parse(text);
            //	 renderTotals(data.totals);
            renderTable(data.transactions);
            qs('pageInfo').innerText = `Página ${data.page}`;
        } catch (e) {
            console.error('Error parseando JSON:', e, text);
        }
    }

    // --------------------------
    // Render totales
    // --------------------------
    function renderTotals(t) {
        qs('t_ingresos').innerText = fmtCurrency(t.total_ingresos);
        qs('t_gastos').innerText = fmtCurrency(t.total_gastos);
        qs('t_saldo').innerText = fmtCurrency(t.saldo);
    }

    // --------------------------
    // Render tabla
    // --------------------------
    function renderTable(rows) {
        const tbody = qs('transactionsTable').querySelector('tbody');
        tbody.innerHTML = '';

        if (!rows || rows.length === 0) {
            let tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="6" class="text-center">No hay datos</td>`;
            tbody.appendChild(tr);
            return;
        }

        rows.forEach((r) => {
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${r.fecha}</td>
                <td>${r.descripcion || ''}</td>
                <td>${r.id_categoria || ''}</td>
                <td>${r.id_subcategoria || ''}</td>
                <td>${fmtCurrency(r.monto)}</td>
                <td>${r.tipo}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --------------------------
    // EVENTOS
    // --------------------------
    qs('filter_type').addEventListener('change', () => {
        state.filter_type = qs('filter_type').value;
        updateFilterInputs();
    });

    qs('btnApplyFilter').addEventListener('click', () => {
        state.page = 1;
        fetchData();
    });

    qs('prevPage').addEventListener('click', () => {
        if (state.page > 1) {
            state.page--;
            fetchData();
        }
    });

    qs('nextPage').addEventListener('click', () => {
        state.page++;
        fetchData();
    });

    // --------------------------
    // SIDEBAR TOGGLE (☰)
    // --------------------------
    qs('btnToggleSidebar').addEventListener('click', () => {
        console.log('Toggle sidebar!');
        document.body.classList.toggle('sidebar-collapsed');
    });

    // --------------------------
    // NAV MENU
    // --------------------------
    document.querySelectorAll('.menu-item').forEach((item) => {
        item.addEventListener('click', (e) => {
            // Si el enlace tiene href válido, dejamos que el navegador navegue.
            const href = item.getAttribute('href');
            if (href && href.trim() !== '' && href.trim() !== '#') {
                // añadimos la clase visual pero NO prevenimos la navegación
                document
                    .querySelectorAll('.menu-item')
                    .forEach((e2) => e2.classList.remove('is-active'));
                item.classList.add('is-active');
                // no call to e.preventDefault(); let navigation happen
                return;
            }
            // Si no tiene href (modo SPA), evitamos la navegación y actuamos como SPA:
            e.preventDefault();
            document
                .querySelectorAll('.menu-item')
                .forEach((e2) => e2.classList.remove('is-active'));
            item.classList.add('is-active');
            // aquí podrías mostrar la sección SPA correspondiente
        });
    });

    // --------------------------
    // INICIALIZAR
    // --------------------------
    initDateSelectors();
    updateFilterInputs();
    fetchData();
});
