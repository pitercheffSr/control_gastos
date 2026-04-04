CREATE TABLE IF NOT EXISTS subcategorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    id_categoria INT NOT NULL,
    id_usuario INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uk_nombre_categoria (nombre, id_categoria)
);

-- Índices para optimizar búsquedas
CREATE INDEX idx_subcategoria_categoria ON subcategorias(id_categoria);
CREATE INDEX idx_subcategoria_usuario ON subcategorias(id_usuario);
CREATE INDEX idx_subcategoria_activo ON subcategorias(activo);
