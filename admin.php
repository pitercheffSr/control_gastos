<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Bloqueo de puerta: Si no eres admin, te devuelvo al dashboard
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$_SESSION['usuario_id']]);
$userRole = $stmtUser->fetchColumn();

if ($userRole !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php'; 
?>
<script>
    // La comprobación de rol ahora se hace en el backend (PHP) antes de renderizar la página,
    // lo cual es mucho más seguro. El código JS de comprobación ya no es necesario aquí.
</script>

<div class="container mx-auto p-6 max-w-6xl min-h-screen pb-24">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Panel de Administrador</h1>
            <p class="text-sm text-gray-500 mt-1">Gestión global de usuarios de FinanzasPro.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="registro.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-indigo-700 font-bold transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Nuevo Usuario Manual
            </a>
            <button onclick="eliminarTodosLosUsuarios()" class="bg-red-600 text-white px-5 py-2.5 rounded-xl shadow-md hover:bg-red-700 font-bold transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                Borrar Todos los Usuarios
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

<!-- Modal Editar Usuario -->
<div id="modalEditarUsuario" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 transform transition-all">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Editar Usuario</h2>
        <form id="formEditarUsuario" onsubmit="guardarEdicionUsuario(event)">
            <input type="hidden" id="edit_user_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" id="edit_user_nombre" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="edit_user_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select id="edit_user_rol" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="usuario">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEditar()" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg font-medium transition">Guardar Cambios</button>
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
                    <button onclick="abrirModalEditar(${u.id}, '${u.nombre.replace(/'/g, "&#39;")}', '${u.email}', '${u.rol}')" class="text-gray-400 hover:text-blue-500 mx-1 p-1.5 rounded hover:bg-blue-50 transition" title="Editar Usuario">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button onclick="resetearPasswordUsuario(${u.id}, '${u.nombre.replace(/'/g, "&#39;")}')" class="text-gray-400 hover:text-green-500 mx-1 p-1.5 rounded hover:bg-green-50 transition" title="Forzar cambio de contraseña">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    </button>
                    ${!esAdmin ? `
                    <button onclick="eliminarTransaccionesUsuario(${u.id}, '${u.nombre}')" class="text-gray-400 hover:text-orange-500 mx-1 p-1.5 rounded hover:bg-orange-50 transition" title="Borrar todas las transacciones de este usuario">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </button>
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

/**
 * Solicita la contraseña del administrador dos veces para confirmar una acción crítica.
 * @returns {Promise<string|null>} La contraseña si coincide y no es nula, o null si no coincide o se cancela.
 */
async function promptAdminPassword() {
    const p1 = prompt("Por favor, introduce tu contraseña de administrador para confirmar:");
    if (p1 === null) return null; // Cancelado
    if (p1.trim() === "") {
        alert("La contraseña no puede estar vacía.");
        return null;
    }
    const p2 = prompt("Por favor, repite tu contraseña de administrador:");
    if (p2 === null) return null; // Cancelado
    if (p1 !== p2) {
        alert("Las contraseñas no coinciden.");
        return null;
    }
    return p1;
}

async function eliminarUsuario(id, nombre) {
    if (!confirm(`⚠️ ATENCIÓN: Estás a punto de borrar definitivamente al usuario "${nombre}".\n\nTodos sus movimientos, categorías y datos se perderán para siempre.\n\n¿Estás absolutamente seguro?`)) return;

    const adminPassword = await promptAdminPassword();
    if (adminPassword === null) return; // Usuario canceló o contraseñas no coinciden/vacías

    try {
        const res = await fetch('controllers/AdminRouter.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id, admin_password: adminPassword }),
            headers: { 'Content-Type': 'application/json' }
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

async function eliminarTransaccionesUsuario(id, nombre) {
    if (!confirm(`☢️ MÁXIMA ALERTA: Estás a punto de borrar TODAS las transacciones del usuario "${nombre}".\n\nEsta acción es irreversible y no se puede deshacer.\n\n¿Estás completamente seguro de querer continuar?`)) {
        return;
    }

    const adminPassword = await promptAdminPassword();
    if (adminPassword === null) return; // Usuario canceló o contraseñas no coinciden/vacías

    try {
        const res = await fetch('controllers/AdminRouter.php?action=deleteAllTransactions', {
            method: 'POST',
            body: JSON.stringify({ id, admin_password: adminPassword }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await res.json();
        
        if (data.success) {
            alert(`¡Hecho! Todas las transacciones del usuario "${nombre}" han sido eliminadas.`);
            // No es necesario recargar la tabla, ya que no cambia visualmente.
        } else {
            alert("Error al borrar transacciones: " + (data.error || "Desconocido"));
        }
    } catch (e) {
        console.error(e);
        alert("Hubo un error de comunicación al intentar borrar las transacciones.");
    }
}

async function eliminarTodosLosUsuarios() {
    if (!confirm(`☢️☢️☢️ ALERTA MÁXIMA ☢️☢️☢️\n\nEstás a punto de borrar TODOS los usuarios que NO son administradores.\n\nEsta acción es DEFINITIVA e IRREVERSIBLE.\n\n¿Estás 100% seguro de que quieres hacer esto?`)) {
        return;
    }

    const adminPassword = await promptAdminPassword();
    if (adminPassword === null) return; // Usuario canceló o contraseñas no coinciden/vacías

    try {
        const res = await fetch('controllers/AdminRouter.php?action=deleteAllNonAdmins', {
            method: 'POST', // POST es más seguro para acciones destructivas
            body: JSON.stringify({ admin_password: adminPassword }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            alert('¡Limpieza completada! Todos los usuarios no administradores han sido eliminados.');
            cargarUsuarios(); // Recargar la tabla
        } else {
            alert("Error durante la limpieza masiva: " + (data.error || "Desconocido"));
        }
    } catch (e) {
        console.error(e);
        alert("Hubo un error de comunicación al intentar la limpieza masiva.");
    }
}

function abrirModalEditar(id, nombre, email, rol) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_nombre').value = nombre;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_rol').value = rol;
    
    const modal = document.getElementById('modalEditarUsuario');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModalEditar() {
    const modal = document.getElementById('modalEditarUsuario');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function guardarEdicionUsuario(e) {
    e.preventDefault();
    const id = document.getElementById('edit_user_id').value;
    const nombre = document.getElementById('edit_user_nombre').value;
    const email = document.getElementById('edit_user_email').value;
    const rol = document.getElementById('edit_user_rol').value;

    const adminPassword = await promptAdminPassword();
    if (adminPassword === null) return;

    try {
        const res = await fetch('controllers/AdminRouter.php?action=updateUser', {
            method: 'POST',
            body: JSON.stringify({ id, nombre, email, rol, admin_password: adminPassword }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            cerrarModalEditar();
            cargarUsuarios(); // Recarga la tabla de inmediato
        } else {
            alert("Error al actualizar: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        console.error(err);
        alert("Hubo un error de comunicación.");
    }
}

async function resetearPasswordUsuario(id, nombre) {
    const newPassword = prompt(`Introduce la nueva contraseña para el usuario "${nombre}"\n(Debe tener al menos 6 caracteres):`);
    
    if (newPassword === null) return; // Cancelado
    if (newPassword.length < 6) {
        alert("Operación cancelada: La contraseña debe tener al menos 6 caracteres.");
        return;
    }

    const adminPassword = await promptAdminPassword();
    if (adminPassword === null) return;

    try {
        const res = await fetch('controllers/AdminRouter.php?action=resetUserPassword', {
            method: 'POST',
            body: JSON.stringify({ id, new_password: newPassword, admin_password: adminPassword }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            alert(`¡Éxito! La contraseña del usuario "${nombre}" ha sido actualizada.`);
        } else {
            alert("Error al restablecer la contraseña: " + (data.error || "Desconocido"));
        }
    } catch (err) {
        console.error(err);
        alert("Hubo un error de comunicación.");
    }
}

document.addEventListener('DOMContentLoaded', cargarUsuarios);
</script>

<?php include 'includes/footer.php'; ?>