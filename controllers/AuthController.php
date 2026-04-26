<?php
require_once __DIR__ . '/../models/CategoriaModel.php';

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Sincronizamos con el nombre de variable que usa el header.php
            unset($user['password']); // No devolver el hash de la contraseña por seguridad (Clean Code)
            return $user; // Devolvemos el usuario completo para que el script de login lo use
        }
        return false;
    }

    public function register($nombre, $email, $password) { // El método ahora maneja toda la lógica
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Generar un código de recuperación único de 8 caracteres
        $codigoRecuperacion = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $hashRecuperacion = password_hash($codigoRecuperacion, PASSWORD_DEFAULT);

        try {
            $this->pdo->beginTransaction();

            // 1. Comprobar si el usuario ya existe
            $stmtCheck = $this->pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                throw new Exception('Este nombre de usuario ya está en uso.');
            }

            // 2. Insertar el nuevo usuario
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password, recovery_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $hashRecuperacion]);
            $nuevo_id = $this->pdo->lastInsertId();

            // 3. Establecer valores por defecto (día de inicio y fecha de borrado)
            $fechaRegistro = new DateTime();
            $fechaBorrado = (clone $fechaRegistro)->modify('+4 months');
            $fechaBorradoStr = $fechaBorrado->format('Y-m-d H:i:s');
            $stmtUpdate = $this->pdo->prepare("UPDATE usuarios SET dia_inicio_mes = 1, fecha_borrado = ? WHERE id = ?");
            $stmtUpdate->execute([$fechaBorradoStr, $nuevo_id]);

            // 4. Crear las categorías por defecto para el nuevo usuario llamando a un método privado.
            $this->crearCategoriasPorDefecto($nuevo_id);

            $this->pdo->commit();

            return [
                'id' => $nuevo_id,
                'recovery_code' => $codigoRecuperacion
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Error en registro (AuthController): ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Verifica si la contraseña proporcionada coincide con la del usuario dado.
     * @param int $userId El ID del usuario.
     * @param string $password La contraseña en texto plano a verificar.
     * @return bool True si la contraseña es correcta, false en caso contrario.
     */
    public function verifyPasswordForUser(int $userId, string $password): bool {
        $stmt = $this->pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user && password_verify($password, $user['password']);
    }

    /**
     * Restablece la contraseña utilizando el código de recuperación.
     */
    public function resetPasswordWithCode(string $email, string $code, string $newPassword): bool {
        $stmt = $this->pdo->prepare("SELECT id, recovery_hash FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['recovery_hash']) && password_verify($code, $user['recovery_hash'])) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpd = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            return $stmtUpd->execute([$newHash, $user['id']]);
        }
        return false;
    }

    /**
     * Genera un nuevo código de recuperación, invalida el anterior en la BD
     * y devuelve el nuevo código en texto plano para mostrarlo al usuario.
     * @param int $userId El ID del usuario.
     * @return string El nuevo código generado.
     */
    public function generateNewRecoveryCode(int $userId): string {
        $codigoRecuperacion = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $hashRecuperacion = password_hash($codigoRecuperacion, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("UPDATE usuarios SET recovery_hash = ? WHERE id = ?");
        $stmt->execute([$hashRecuperacion, $userId]);

        return $codigoRecuperacion;
    }

    /**
     * Actualiza la contraseña de un usuario directamente.
     * @param int $userId El ID del usuario.
     * @param string $newPassword La nueva contraseña en texto plano.
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$newHash, $userId]);
    }

    /**
     * Crea un conjunto de categorías por defecto para un nuevo usuario.
     * Esta función se llama durante el registro.
     * @param int $usuario_id El ID del nuevo usuario.
     */
    private function crearCategoriasPorDefecto(int $usuario_id): void {
        // Estructura de categorías por defecto: [Nombre, Nombre del Padre, Tipo Fijo (opcional)]
        // El array debe estar ordenado para que los padres se creen antes que los hijos.
        $categorias = [
            // Nivel 1 (Padres)
            ['Necesidades (50%)', null, 'gasto'],
            ['Deseos (30%)', null, 'gasto'],
            ['Ahorro (20%)', null, 'ahorro'],
            ['Ingresos', null, 'ingreso'],
            // Nivel 2 (Hijos)
            ['Vivienda', 'Necesidades (50%)', 'gasto'],
            ['Alimentación', 'Necesidades (50%)', 'gasto'],
            ['Transporte', 'Necesidades (50%)', 'gasto'],
            ['Salud', 'Necesidades (50%)', 'gasto'],
            ['Ocio', 'Deseos (30%)', 'gasto'],
            ['Restaurantes', 'Deseos (30%)', 'gasto'],
            ['Compras', 'Deseos (30%)', 'gasto'],
            ['Vacaciones', 'Deseos (30%)', 'gasto'],
            ['Fondo de Emergencia', 'Ahorro (20%)', 'ahorro'],
            ['Inversiones', 'Ahorro (20%)', 'ahorro'],
            ['Salario', 'Ingresos', 'ingreso'],
            ['Otros Ingresos', 'Ingresos', 'ingreso'],
        ];

        $sql = "INSERT INTO categorias (usuario_id, nombre, parent_id, tipo_fijo) VALUES (:usuario_id, :nombre, :parent_id, :tipo_fijo)";
        $stmt = $this->pdo->prepare($sql);

        $ids_por_nombre = [];

        foreach ($categorias as $cat) {
            $nombre = $cat[0];
            $nombre_padre = $cat[1];
            $parent_id = ($nombre_padre !== null) ? ($ids_por_nombre[$nombre_padre] ?? null) : null;

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':nombre' => $nombre,
                ':parent_id' => $parent_id,
                ':tipo_fijo' => $cat[2]
            ]);

            // Guardamos el ID de la categoría recién creada para usarla como padre de las siguientes.
            $ids_por_nombre[$nombre] = $this->pdo->lastInsertId();
        }
    }
}
