# FinanzasPro - Control de Gastos Inteligente

FinanzasPro es una aplicación web diseñada para el seguimiento avanzado de finanzas personales, construida sobre el método de presupuestación **50/30/20** (Necesidades / Deseos / Ahorro).

A diferencia de los gestores tradicionales, esta aplicación destaca por su motor de **Auto-Clasificación**, que permite asignar movimientos bancarios a categorías de forma automática utilizando reglas de texto.

## 🚀 Funcionalidades Principales

*   **Dashboard 50/30/20:** Análisis en tiempo real de tu salud financiera, verificando si tus gastos se adhieren a la regla presupuestaria en base a tus ingresos de ese periodo.
*   **Importador CSV Inteligente:** Sube extractos directamente desde tu banco. El sistema limpia los textos (ignorando acentos y mayúsculas) y auto-asigna los movimientos basándose en tu histórico y reglas.
*   **Motor de Reglas en Categorías:** Puedes añadir palabras clave entre paréntesis en el nombre de tus categorías (ej. `Alimentación (mercadona, carrefour)`). La interfaz visual ocultará estos paréntesis para mantener un diseño limpio, pero el motor en segundo plano auto-clasificará cualquier movimiento que coincida.
*   **Botón "Auto-Clasificar":** Aplica tus reglas de forma retroactiva a los movimientos huérfanos con un solo clic.
*   **Tipos Contables Avanzados:**
    *   🔴 *Gasto:* Resta de tus cuentas y suma a tus informes de gastos.
    *   🟢 *Ingreso:* Suma a tus cuentas (Nóminas, transferencias recibidas).
    *   🟣 *Ahorro:* Suma directamente a tu meta del 20% en el Dashboard sin penalizar tus gastos del mes.
    *   ⚪ *Puente:* Traspasos entre cuentas propias que son financieramente "invisibles" en el balance global.
*   **Drag & Drop:** Reorganiza tus categorías y reclasifica tus transacciones arrastrando y soltando en la interfaz.
*   **Privacidad por Diseño:** Generación de cuentas locales cerradas (`@cgastos.mi`) con expiración y auto-borrado a los 4 meses, garantizando la seguridad de la información financiera introducida.

## 🛠️ Stack Tecnológico

*   **Backend:** PHP 8+ con PDO (Arquitectura MVC y Routers por endpoints API).
*   **Base de Datos:** MySQL / MariaDB (BBDD relacional para Usuarios, Categorías dinámicas y Transacciones).
*   **Frontend:** Vanilla JavaScript (ES6+), HTML5.
*   **Estilos:** Tailwind CSS (vía CDN) para un diseño responsive y moderno.
*   **Gráficos:** Chart.js para representaciones circulares (Donut) e históricos de balance (Líneas).
*   **Interacciones:** SweetAlert2 para modales y SortableJS para el Drag & Drop.

## 📂 Estructura del Proyecto

```text
control_gastos/
├── controllers/        # Controladores PHP (Lógica de negocio y Routers de la API)
├── models/             # Modelos PDO (Interacción directa con la Base de Datos)
├── views/              # Vistas secundarias (ej. importar.php)
├── assets/             # Recursos estáticos (JS, CSS custom)
├── includes/           # Componentes UI reutilizables (header, footer)
├── config.php          # Configuración global y gestión de Sesiones/CSRF
├── db.php              # Conexión PDO a la base de datos
├── index.php           # Landing page / Landing promocional
├── login.php           # Autenticación y control de acceso
├── dashboard.php       # Panel principal y KPIs financieros
├── transacciones.php   # Tabla principal, filtros, edición y Auto-Clasificador
├── categorias.php      # Árbol de categorías, reglas y gestión
└── admin.php           # Panel de súper-administrador
```

## ⚙️ Instalación y Despliegue

1.  **Requisitos:** Servidor Apache/Nginx con PHP 8.0+ y MySQL 5.7+.
2.  **Base de datos:**
    *   Crea una base de datos MySQL vacía.
    *   Ejecuta el script SQL incluido (no provisto en este readme) para crear las tablas `usuarios`, `categorias` y `transacciones`.
3.  **Configuración:**
    *   Renombra o edita `db.php` y/o `config.php` con tus credenciales de la base de datos (Host, Usuario, Contraseña, Nombre de BD).
4.  **Permisos:** Asegúrate de que tu servidor web tenga permisos de lectura en todo el directorio.

## 🔒 Seguridad Implementada

*   Protección contra inyección SQL utilizando declaraciones preparadas (PDO Prepared Statements) en todas las interacciones con la BD.
*   Validación estricta de tokens Anti-CSRF (`X-CSRF-TOKEN`) en todas las operaciones que mutan información (POST, DELETE, UPDATE).
*   Prevención de Fijación de Sesión (Regeneración de ID en Login y Registro).
*   Hash seguro de contraseñas (`PASSWORD_DEFAULT` - bcrypt/argon2).
*   Recuperación de cuentas basada en códigos de emergencia de un solo uso en lugar de correos electrónicos.

## 💡 Uso de la Auto-Clasificación

Para aprovechar al máximo el sistema:
1. Ve a **Categorías**.
2. Crea o edita una categoría y añade palabras clave entre paréntesis en el título. Ejemplo: `Coche (gasolinera, repsol, taller)`.
3. Importa tu CSV bancario. El sistema leerá automáticamente esas palabras clave y asignará los movimientos a la categoría "Coche".
4. Puedes darle al botón **Auto-Clasificar** en cualquier momento para aplicar nuevas reglas a movimientos antiguos.