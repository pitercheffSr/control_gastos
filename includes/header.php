<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlGastos Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* --- ESTILOS PERSONALIZADOS --- */
        :root {
            --primary-color: #4e73df; /* Azul profesional */
            --bg-light: #f8f9fc;      /* Fondo gris muy suave */
        }
        
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding-top: 70px; /* Espacio para la barra superior fija */
        }
        
        /* Menú Lateral (Offcanvas) */
        .offcanvas-start {
            width: 280px;
            background-color: #2c3e50; /* Azul oscuro elegante */
            color: white;
        }
        
        /* Efecto Blur (Desenfoque) en el fondo al abrir menú */
        .offcanvas-backdrop.show {
            backdrop-filter: blur(5px);
            opacity: 0.5;
            background-color: rgba(0,0,0,0.5);
        }
        
        /* Enlaces del Menú */
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            font-size: 1.1rem;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        /* Enlace Activo (Página actual) */
        .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-link i {
            margin-right: 12px;
            width: 25px;
            text-align: center;
        }
        
        /* Colores de la Regla 50/30/20 */
        .text-necesidad { color: #e74a3b; } /* Rojo */
        .bg-necesidad { background-color: #e74a3b; }
        
        .text-deseo { color: #f6c23e; } /* Amarillo */
        .bg-deseo { background-color: #f6c23e; }
        
        .text-ahorro { color: #1cc88a; } /* Verde */
        .bg-ahorro { background-color: #1cc88a; }
        
        /* Tarjetas con sombra suave */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-dark me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <i class="fas fa-wallet me-2"></i>Finanzas 50/30/20
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <span class="text-secondary me-2 d-none d-md-inline">
                    Hola, <strong><?= $_SESSION['user_name'] ?? 'Usuario' ?></strong>
                </span>
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title">Menú Principal</h5>
            <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column mt-3">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='transacciones.php'?'active':'' ?>" href="transacciones.php">
                    <i class="fas fa-list-ul"></i> Transacciones
                </a>
                
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='categorias.php'?'active':'' ?>" href="categorias.php">
                    <i class="fas fa-tags"></i> Categorías
                </a>
                
                <div class="border-top border-secondary my-3 mx-3"></div>
                
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="container" style="max-width: 1200px;">