<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$uid = $_SESSION['usuario_id'];

// Bloqueo de puerta: Si no eres admin, te devuelvo al dashboard
$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$uid]);
$uData = $stmtUser->fetch();

if (!$uData || $uData['rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php'; 
?>

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Panel de Administrador</h1>
            <p class="text-sm text-gray-500 mt-1">Gestión global de usuarios de FinanzasPro.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button onclick="abrirModalUsuario()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nuevo Usuario
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">ID</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Nombre</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Email / Rol</th>
                        <th class="p-4 text-gray-500 font-bold tracking-wider uppercase text-xs">Expira en</th>
                        <th class="p-4 text-center text-gray-500 font-bold tracking-wider uppercase text-xs">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaUsuarios" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="p-8 text-center text-gray-400 font-medium italic">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUsuario" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
    <div id="modalUsuarioContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 transform scale-95 opacity-0 transition-all duration-300">
        <h2 id="modalUserTitle" class="text-2xl font-extrabold mb-6 text-gray-800">Usuario</h2>
        <form id="formUsuario" class="space-y-5">
            <input type="hidden" id="usuario_edit_id">
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Nombre de Usuario</label>
                <input type="text" id="user_nombre" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition" required autocomplete="off">
            </div>
            <div>
                <label class="block text-sm font-bold mb-1.5 text-gray-700">Rol</label>
                <select id="user_rol" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer bg-white">
                    <option value="usuario">Usuario Normal</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModalUsuario()" class="px-6 py-2.5 text-gray-500 font-bold hover:bg-gray-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
async function cargarUsuarios() {
    try {
        const res = await fetch('controllers/AdminRouter.php?action=getAll');
        const usuarios = await res.json();
        
        if(usuarios.error) {
            alert(usuarios.error);
            return;
        }

        let html = '';
        usuarios.forEach(u => {
            const esAdmin = u.rol === 'admin';
            const badgeClass = esAdmin ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 text-gray-600 border-gray-200';
            const fechaBorrado = u.fecha_borrado ? new Date(u.fecha_borrado).toLocaleDateString('es-ES') : 'N/A';
            
            html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500 text-sm font-bold">#${u.id}</td>
                <td class="p-4 font-bold text-gray-800">${u.nombre}</td>
                <td class="p-4">
                    <div class="text-sm font-medium text-gray-600">${u.email}</div>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-bold border ${badgeClass}">${u.rol.toUpperCase()}</span>
                </td>
                <td class="p-4 text-sm font-medium text-gray-500">${fechaBorrado}</td>
                <td class="p-4 text-center">
                    <button onclick="abrirModalUsuario(${u.id}, '${u.nombre}', '${u.rol}')" class="text-gray-400 hover:text-indigo-600 mx-1 p-1.5 rounded hover:bg-indigo-50 transition" title="Editar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    ${!esAdmin ? `
                    <button onclick="eliminarUsuario(${u.id}, '${u.nombre}')" class="text-gray-400 hover:text-red-500 mx-1 p-1.5 rounded hover:bg-red-50 transition" title="Eliminar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    ` : '<span class="mx-1 p-1.5 text-gray-300 inline-block w-8 h-8"></span>'}
                </td>
            </tr>`;
        });
        
        document.getElementById('tablaUsuarios').innerHTML = html;
    } catch(e) {
        console.error("Error cargando usuarios:", e);
    }
}

function abrirModalUsuario(id = null, nombre = '', rol = 'usuario') {
    document.getElementById('formUsuario').reset();
    document.getElementById('usuario_edit_id').value = id || '';
    
    if (id) {
        document.getElementById('user_nombre').value = nombre;
        document.getElementById('user_rol').value = rol;
    }
    
    document.getElementById('modalUsuario').classList.remove('hidden');
    setTimeout(() => { document.getElementById('modalUsuarioContent').classList.add('scale-100', 'opacity-100'); }, 10);
}

function cerrarModalUsuario() {
    const content = document.getElementById('modalUsuarioContent');
    if(content) content.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => { 
        const modal = document.getElementById('modalUsuario'); 
        if(modal) modal.classList.add('hidden'); 
    }, 300);
}

// Cierre centralizado con Escape para el modal (y el menú si lo tienes en header)
document.addEventListener('keydown', (e) => { 
    if(e.key === "Escape") { 
        cerrarModalUsuario();
        
        // Cierre del menú hamburguesa si está abierto
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
        }
    } 
});

async function eliminarUsuario(id, nombre) {
    if (!confirm(`⚠️ ATENCIÓN: Estás a punto de borrar definitivamente al usuario "${nombre}".\n\nTodos sus movimientos, categorías y datos se perderán para siempre.\n\n¿Estás absolutamente seguro?`)) return;

    try {
        const res = await fetch('controllers/AdminRouter.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        
        if (data.success) {
            cargarUsuarios();
        } else {
            alert("Error al borrar: " + (data.error || "Desconocido"));
        }
    } catch (e) {
        console.error(e);
        alert("Hubo un error de comunicación.");
    }
}

// Prevenir el envío de momento (puedes enlazarlo luego a una API de guardado)
document.getElementById('formUsuario').addEventListener('submit', (e) => {
    e.preventDefault();
    alert('Función de guardar en construcción.');
    cerrarModalUsuario();
});

document.addEventListener('DOMContentLoaded', cargarUsuarios);
</script>

<?php include 'includes/footer.php'; ?>