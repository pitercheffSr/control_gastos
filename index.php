<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['usuario_id'])) {
    header('Location: transacciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Gastos Inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="min-h-screen flex flex-col">
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2 text-indigo-600 font-extrabold text-xl tracking-tight">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                FinanzasPro
            </div>
            <div class="flex gap-4">
                <a href="login.php" class="px-5 py-2.5 text-gray-600 font-bold hover:text-indigo-600 transition">Entrar</a>
                <a href="registro.php" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md transition">Crear cuenta libre</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        <div class="max-w-6xl mx-auto px-6 py-20 text-center">
            <h1 class="text-5xl md:text-7xl font-extrabold text-gray-900 tracking-tight mb-6">
                Domina tu dinero con el método <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">50/30/20</span>
            </h1>
            <p class="text-xl text-gray-500 max-w-2xl mx-auto mb-10 font-medium">
                La aplicación inteligente que categoriza tus extractos bancarios automáticamente y te ayuda a ahorrar sin hojas de cálculo complejas.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="registro.php" class="px-8 py-4 bg-indigo-600 text-white font-bold text-lg rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition transform hover:-translate-y-1">Comenzar ahora gratis</a>
                <a href="#funcionalidades" class="px-8 py-4 bg-white text-gray-700 font-bold text-lg rounded-2xl border border-gray-200 hover:bg-gray-50 transition">Ver cómo funciona</a>
            </div>
        </div>

        <div id="funcionalidades" class="bg-white py-24 border-t border-gray-100">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-extrabold text-gray-900">Todo lo que necesitas, nada de lo que sobra</h2>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-6"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Importador CSV Inteligente</h3>
                        <p class="text-gray-600 font-medium">Sube el excel de tu banco. Nuestro motor lee las descripciones, ignora acentos y auto-asigna los gastos a tus categorías mágicamente.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Árbol 50/30/20 Integrado</h3>
                        <p class="text-gray-600 font-medium">Al registrarte, se genera automáticamente la estructura financiera perfecta. Protegemos las categorías maestras para evitar errores.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center mb-6"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Búsqueda en Tiempo Real</h3>
                        <p class="text-gray-600 font-medium">Escribe una palabra o selecciona una categoría y filtra miles de movimientos al instante. Sin recargar la página.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center mb-6"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Control Total y Masivo</h3>
                        <p class="text-gray-600 font-medium">Equivocarse no es problema. Elimina movimientos por fechas o usa el Botón Nuclear para reiniciar tu cuenta por completo.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Privacidad Asegurada</h3>
                        <p class="text-gray-600 font-medium">Protección anti-bots integrada y contraseñas encriptadas. Solo tú tienes las llaves de tu información financiera.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>