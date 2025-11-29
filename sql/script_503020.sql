-- ============================================================
--   BACKUP Y ESTRUCTURA COMPLETA DE LA BASE DE DATOS ACTUAL
--            bd_503020 — versión compatible 2025
-- ============================================================

CREATE DATABASE IF NOT EXISTS bd_503020;
USE bd_503020;

-- ============================================================
--   TABLA: transacciones
-- ============================================================
CREATE TABLE IF NOT EXISTS transacciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  fecha DATE NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  tipo VARCHAR(10) NOT NULL,          -- ingreso / gasto
  descripcion VARCHAR(255) NULL,
  id_categoria INT NULL,
  id_subcategoria INT NULL,
  id_subsubcategoria INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices recomendados:
ALTER TABLE transacciones
  ADD INDEX idx_transacciones_fecha (fecha),
  ADD INDEX idx_transacciones_usuario (id_usuario);

-- ============================================================
--   TABLA: categorias
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  clasificacion ENUM('50', '30', '20', '10') DEFAULT '50',
  descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--   TABLA: subcategorias
-- ============================================================
CREATE TABLE IF NOT EXISTS subcategorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--   TABLA: subsubcategorias
-- ============================================================
CREATE TABLE IF NOT EXISTS subsubcategorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_subcategoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_subcategoria) REFERENCES subcategorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--   NOTAS:
--   - No se elimina ninguna tabla existente.
--   - Compatible con ftch.php actualizado.
--   - Compatible con dashboard C4 (Spectre.css).
--   - Mantiene la estructura real de tu BD sin cambios destructivos.
-- ============================================================
