document.addEventListener('DOMContentLoaded', () => {
    const btnToggle = document.getElementById('btnMenuToggle');
    const btnCerrar = document.getElementById('btnMenuCerrar');
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('menuOverlay');

    if (!btnToggle || !btnCerrar || !menu || !overlay) return;

    function abrirMenu() {
        menu.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
    }
    function cerrarMenu() {
        menu.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }

    btnToggle.addEventListener('click', abrirMenu);
    btnCerrar.addEventListener('click', cerrarMenu);
    overlay.addEventListener('click', cerrarMenu);
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape" && !menu.classList.contains('translate-x-full')) cerrarMenu();
    });
});
