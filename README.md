# 📊 Control de Gastos 50/30/20

Una aplicación web moderna y reactiva para gestionar finanzas personales basándose en la popular regla del **50/30/20** (Necesidades, Deseos y Ahorro).

## ✨ Características Principales

- **Dashboard Interactivo:** Gráficos dinámicos (Chart.js) que muestran la distribución de gastos, la evolución del balance histórico y el progreso de la regla 50/30/20.
- **Gestión de Movimientos (SPA):** Interfaz de una sola página para gestionar transacciones sin recargar el navegador. Incluye paginación, edición rápida en panel lateral y acciones masivas (drag & drop).
- **Filtro Global Inteligente:** Capacidad de filtrar instantáneamente toda la aplicación por **"Solo Ingresos"** o **"Solo Gastos"** desde la barra de navegación superior.
- **Búsqueda Avanzada:** Buscador en tiempo real con **resaltado visual** (marcador amarillo) de las coincidencias en las descripciones y categorías.
- **Gestor de Categorías:** Sistema de subcategorías infinitas ordenables mediante *Drag and Drop* (SortableJS).
- **Importación / Exportación:** Importación inteligente de extractos bancarios en CSV con auto-clasificación basada en descripciones, y exportación de datos respetando los filtros activos.
- **Seguridad de Grado Bancario:** Protección integral contra ataques CSRF mediante tokens dinámicos en llamadas AJAX, prevención de Fijación de Sesión (Session Fixation), cookies seguras (HttpOnly, Strict) y cierre de sesión por inactividad. Destrucción automática de cuentas tras 4 meses.

## 🚀 Tecnologías Utilizadas

- **Backend:** PHP 8+ puro (Arquitectura MVC/Router)
- **Base de Datos:** MySQL / MariaDB (Acceso seguro mediante PDO)
- **Frontend (UI):** HTML5, TailwindCSS (v3)
- **Frontend (Lógica):** Vanilla JavaScript (ES6+), Fetch API (AJAX)
- **Librerías de terceros:** Chart.js (Gráficos), SortableJS (Drag & Drop), SweetAlert2 (Alertas modales)

## ⚙️ Instalación y Configuración

1. Clona este repositorio en tu servidor web (Apache/Nginx).
2. Importa la estructura de la base de datos (y ejecuta el script de tablas si es necesario).
3. Renombra el archivo `config (server).php` a `config.php` y configura tus variables de conexión:
   ```php
   $host    = 'tu_host';
   $db_name = 'tu_base_de_datos';
   $user    = 'tu_usuario';
   $pass    = 'tu_contraseña';
   ```
4. Accede a la aplicación desde tu navegador y registra un nuevo usuario.

## 🛠️ Últimas Actualizaciones

- **Seguridad (Hotfix):** Implementación exhaustiva de Tokens CSRF en toda la aplicación y securización en el manejo de sesiones en PHP.
- **Rendimiento:** Unificación de la capa de datos. Se migró la gestión de categorías de `MySQLi` a `PDO`, reduciendo las conexiones simultáneas al servidor.
- **Importación SPA:** El flujo de importación de archivos CSV ahora es 100% AJAX, eliminando redirecciones y mostrando una vista previa pre-categorizada en tiempo real.
- **Importador de CSV Avanzado:** Algoritmo de lectura mejorado con auto-detección de delimitadores, limpieza de caracteres invisibles (BOM), conversión automática a UTF-8 y soporte para formatos de fecha flexibles.
- **Seguridad "Defense in Depth":** Sistema CSRF reforzado con validación de cabeceras nativas (`Sec-Fetch-Site` y `Referer`) para prevenir bloqueos y falsos positivos causados por firewalls de hostings estrictos.
- **Entornos Aislados:** Creación de plantillas separadas (`config_local.php` y `config_produccion.php`) para un despliegue más seguro mediante SFTP.
- **Limpieza de Deuda Técnica:** Eliminación completa de endpoints obsoletos (Zombie APIs) y código heredado.