<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ¡AQUÍ ESTÁ LA CLAVE! Llamamos a la conexión de la base de datos
require_once __DIR__ . '/../config.php';

// --- ALGORITMO DE CADUCIDAD DE 4 MESES ---
$fecha_borrado_str = 'Desconocida';
$dias_restantes = 120;

if (isset($_SESSION['usuario_id']) && isset($pdo)) {
    try {
        $stmtUser = $pdo->prepare("SELECT fecha_registro FROM usuarios WHERE id = ?");
        $stmtUser->execute([$_SESSION['usuario_id']]);
        $userObj = $stmtUser->fetch();

        if ($userObj && !empty($userObj['fecha_registro'])) {
            $fechaRegistro = new DateTime($userObj['fecha_registro']);
            $fechaActual = new DateTime();
            
            // Calculamos la fecha límite (Registro + 4 meses)
            $fechaCaducidad = clone $fechaRegistro;
            $fechaCaducidad->modify('+4 months');
            
            $fecha_borrado_str = $fechaCaducidad->format('d/m/Y');
            $dias_restantes = $fechaActual->diff($fechaCaducidad)->days;
            $ya_caducado = $fechaActual >= $fechaCaducidad;

            // Si ya ha pasado la fecha... ¡KABOOM!
            if ($ya_caducado) {
                $uid = $_SESSION['usuario_id'];
                
                $pdo->prepare("DELETE FROM transacciones WHERE usuario_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM categorias WHERE usuario_id = ?")->execute([$uid]);
                $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]);
                
                session_destroy();
                header('Location: index.php?expired=1');
                exit;
            }
        }
    } catch (Exception $e) {
        // Ignorar error para no romper la vista
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Gastos 50/30/20</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { padding-top: 4rem; background-color: #f8fafc; }
    </style>
</head>
<body class="text-gray-800 antialiased">

    <nav class="bg-indigo-600 shadow-md fixed w-full top-0 z-40 transition-all">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center gap-2">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <span class="text-indigo-600 font-extrabold text-xl">€</span>
                    </div>
                    <a href="dashboard.php" class="text-white font-bold text-xl tracking-wide">Control<span class="font-light">Gastos</span></a>
                </div>
                <div class="flex items-center">
                    <button id="btnMenuToggle" class="text-indigo-100 hover:text-white focus:outline-none p-2 rounded-lg hover:bg-indigo-700 transition" aria-label="Abrir menú">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div id="menuOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-40 hidden transition-opacity opacity-0"></div>
    <aside id="menuLateral" class="fixed inset-y-0 right-0 z-50 w-64 md:w-[20%] bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-l border-gray-100">
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-100 bg-gray-50">
            <span class="font-bold text-gray-700 uppercase tracking-wider text-sm">Menú Principal</span>
            <button id="btnMenuCerrar" class="text-gray-400 hover:text-red-500 focus:outline-none transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-xl hover:bg-indigo-50 hover:text-indigo-700 transition font-medium"><svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg> Dashboard</a>
            <a href="transacciones.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-xl hover:bg-indigo-50 hover:text-indigo-700 transition font-medium"><svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg> Movimientos</a>
            <a href="categorias.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-xl hover:bg-indigo-50 hover:text-indigo-700 transition font-medium"><svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg> Categorías</a>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-bold text-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg> Cerrar Sesión</a>
        </div>
    </aside>

    <script>
        const btnToggle = document.getElementById('btnMenuToggle');
        const btnCerrar = document.getElementById('btnMenuCerrar');
        const menu = document.getElementById('menuLateral');
        const overlay = document.getElementById('menuOverlay');

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
    </script>