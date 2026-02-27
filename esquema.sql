-- Crear la base de datos si no existe (usando UTF-8 para evitar problemas con acentos)
CREATE DATABASE IF NOT EXISTS control_gastos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE control_gastos;

-- Limpieza previa: Eliminamos las tablas si ya existen para evitar errores al re-importar
-- Se borran en orden inverso a sus dependencias para no romper las claves foráneas
DROP TABLE IF EXISTS transacciones;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

-- 1. Tabla de Usuarios (Actualizada con los últimos cambios de privacidad y login)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    dia_inicio_mes INT DEFAULT 1,
    fecha_borrado DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Categorías (Soporta subcategorías mediante parent_id)
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    tipo_fijo VARCHAR(50) NOT NULL DEFAULT 'gasto', -- ingreso, necesidad, deseo, ahorro, gasto
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Si borramos un usuario, se borran sus categorías en cascada
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    -- Si borramos una categoría padre, se borran sus hijas
    FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Transacciones / Movimientos
CREATE TABLE transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NULL,
    fecha DATE NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    importe DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Si borramos un usuario, se borran sus transacciones
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    -- Si borramos una categoría, la transacción no se borra, se queda "Sin categoría" (NULL)
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;