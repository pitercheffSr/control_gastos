<?php
// Evitamos múltiples session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
// Lista de páginas que NO requieren estar logueado
$paginas_publicas = ['index.php', 'registro.php'];

// PROTECCIÓN: Si no hay sesión y la página no es pública, al login
if (!isset($_SESSION['usuario_id']) && !in_array($pagina_actual, $paginas_publicas)) {
    header('Location: index.php');
    exit();
}

// PREVENCIÓN DE BUCLE: Si YA hay sesión y está en el login, al dashboard
if (isset($_SESSION['usuario_id']) && $pagina_actual === 'index.php') {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Gastos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/base.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-indigo-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="font-bold text-xl">MiCartera</a>
            <button id="btn-hamburger" class="p-2 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
        </div>
    </nav>

    <div id="hamburger-menu" class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-50">
        <div class="bg-white w-64 h-full p-6 shadow-xl">
            <div class="flex justify-between mb-8">
                <h2 class="font-bold text-xl">Menú</h2>
                <button id="close-menu">✕</button>
            </div>
            <nav class="flex flex-col gap-4">
                <a href="dashboard.php" class="hover:text-indigo-600">Dashboard</a>
                <a href="transacciones.php" class="hover:text-indigo-600">Transacciones</a>
                <a href="categorias.php" class="hover:text-indigo-600">Categorías</a>
                <hr>
                <a href="logout.php" class="text-red-500 font-bold">Cerrar Sesión</a>
            </nav>
        </div>
    </div>

    <script>
        const menu = document.getElementById('hamburger-menu');
        const btnOpen = document.getElementById('btn-hamburger');
        const btnClose = document.getElementById('close-menu');

        const toggleMenu = () => menu.classList.toggle('hidden');

        btnOpen.onclick = toggleMenu;
        btnClose.onclick = toggleMenu;
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                menu.classList.add('hidden');
                if (typeof window.closeModal === 'function') window.closeModal();
            }
        });
    </script>