-- =======================================================
-- MIGRACIÓN DE BASE DE DATOS: ESPAÑOL A INGLÉS
-- =======================================================

-- 1. Renombrar las tablas principales
RENAME TABLE usuarios TO users;
RENAME TABLE categorias TO categories;
RENAME TABLE transacciones TO transactions;

-- 2. Actualizar columnas de la tabla 'users'
ALTER TABLE users
    CHANGE COLUMN nombre name VARCHAR(100) NOT NULL,
    CHANGE COLUMN rol role VARCHAR(50) DEFAULT 'user',
    CHANGE COLUMN dia_inicio_mes month_start_day INT DEFAULT 1,
    CHANGE COLUMN fecha_borrado deletion_date DATETIME DEFAULT NULL,
    CHANGE COLUMN fecha_registro registration_date DATETIME DEFAULT CURRENT_TIMESTAMP;

-- 3. Actualizar columnas de la tabla 'categories'
ALTER TABLE categories
    CHANGE COLUMN usuario_id user_id INT DEFAULT NULL,
    CHANGE COLUMN nombre name VARCHAR(100) NOT NULL,
    CHANGE COLUMN tipo_fijo fixed_type VARCHAR(20) DEFAULT 'expense',
    CHANGE COLUMN orden sort_order INT DEFAULT 0;

-- 4. Actualizar columnas de la tabla 'transactions'
-- (Nota: Asumimos que usabas 'importe' o 'monto' para el dinero. Si usabas 'monto', cambia 'importe' por 'monto')
ALTER TABLE transactions
    CHANGE COLUMN usuario_id user_id INT NOT NULL,
    CHANGE COLUMN categoria_id category_id INT DEFAULT NULL,
    CHANGE COLUMN fecha date DATE NOT NULL,
    CHANGE COLUMN descripcion description VARCHAR(255) NOT NULL,
    CHANGE COLUMN importe amount DECIMAL(10, 2) NOT NULL;

-- Si tenías columnas directas de subcategorías como en tu antigua API, descomenta esto:
-- ALTER TABLE transactions CHANGE COLUMN id_categoria category_id INT DEFAULT NULL;
-- ALTER TABLE transactions CHANGE COLUMN id_subcategoria subcategory_id INT DEFAULT NULL;
-- ALTER TABLE transactions CHANGE COLUMN id_subsubcategoria subsubcategory_id INT DEFAULT NULL;

-- =======================================================
-- 5. TRADUCCIÓN DE DATOS INTERNOS (DATA MIGRATION)
-- =======================================================

-- Traducir los tipos contables de las categorías
UPDATE categories SET fixed_type = 'expense' WHERE fixed_type = 'gasto';
UPDATE categories SET fixed_type = 'income' WHERE fixed_type = 'ingreso';
UPDATE categories SET fixed_type = 'savings' WHERE fixed_type = 'ahorro';
UPDATE categories SET fixed_type = 'bridge' WHERE fixed_type = 'puente';

-- Traducir los roles de usuario (si aplicable)
UPDATE users SET role = 'user' WHERE role = 'usuario';
