-- Eliminar categorías existentes y dejar solo las 3 principales fijas
DELETE FROM categorias;
INSERT INTO categorias (id, nombre, tipo, clasificacion, id_usuario) VALUES
  (1, 'Necesidades', 'gasto', 50, 0),
  (2, 'Deseos', 'gasto', 30, 0),
  (3, 'Ahorro', 'gasto', 20, 0);

-- Modificar tabla subcategorias para permitir sub-subcategorías
ALTER TABLE subcategorias 
  ADD COLUMN parent_id INT DEFAULT NULL,
  ADD CONSTRAINT fk_subcat_parent FOREIGN KEY (parent_id) REFERENCES subcategorias(id) ON DELETE CASCADE;

-- Opcional: Limpiar subcategorías si quieres empezar de cero
-- DELETE FROM subcategorias;
