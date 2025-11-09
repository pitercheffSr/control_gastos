USE bd_503020;

-- Agregar columnas nuevas solo si no existen
ALTER TABLE transacciones
ADD COLUMN id_categoria INT NULL AFTER categoria,
ADD COLUMN id_subcategoria INT NULL AFTER id_categoria,
ADD COLUMN id_subsubcategoria INT NULL AFTER id_subcategoria;

-- Crear tabla de categorías si no existe
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  clasificacion ENUM('50', '30', '20', '10') DEFAULT '50',
  descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de subcategorías
CREATE TABLE IF NOT EXISTS subcategorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla de sub-subcategorías
CREATE TABLE IF NOT EXISTS subsubcategorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_subcategoria INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_subcategoria) REFERENCES subcategorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
