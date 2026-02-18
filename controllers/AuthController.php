<?php
// controllers/AuthController.php
require_once __DIR__ . '/../config.php';

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // --- LOGIN ---
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Guardamos datos en sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            return true;
        }
        return false;
    }

    // --- REGISTRO (Con creación automática de categorías) ---
    public function registro($nombre, $email, $password) {
        try {
            // Iniciamos una transacción (todo o nada)
            $this->pdo->beginTransaction();

            // 1. Validar si el email ya existe
            $stmtCheck = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->rowCount() > 0) {
                return "El correo electrónico ya está registrado.";
            }

            // 2. Crear el Usuario
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash]);
            $userId = $this->pdo->lastInsertId();

            // 3. Crear las 3 Categorías PADRE Obligatorias
            // Estas categorías tendrán parent_id = NULL y un tipo fijo
            $categoriasFijas = [
                [
                    'nombre' => '50% Necesidades', 
                    'tipo' => 'necesidad', 
                    'color' => '#e74a3b' // Rojo Bootstrap
                ],
                [
                    'nombre' => '30% Deseos', 
                    'tipo' => 'deseo', 
                    'color' => '#f6c23e' // Amarillo Bootstrap
                ],
                [
                    'nombre' => '20% Ahorro', 
                    'tipo' => 'ahorro', 
                    'color' => '#1cc88a' // Verde Bootstrap
                ]
            ];

            $sqlCat = "INSERT INTO categorias (usuario_id, nombre, parent_id, tipo_fijo, color) VALUES (?, ?, NULL, ?, ?)";
            $stmtCat = $this->pdo->prepare($sqlCat);

            foreach ($categoriasFijas as $cat) {
                $stmtCat->execute([$userId, $cat['nombre'], $cat['tipo'], $cat['color']]);
            }

            // Confirmar transacción
            $this->pdo->commit();
            return true; // Éxito

        } catch (Exception $e) {
            // Si algo falla, deshacemos todo
            $this->pdo->rollBack();
            return "Error en el sistema: " . $e->getMessage();
        }
    }
}
?>