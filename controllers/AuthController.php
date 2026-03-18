<?php
require_once __DIR__ . '/../models/CategoriaModel.php';

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT id, password FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Sincronizamos con el nombre de variable que usa el header.php
            return $user; // Devolvemos el usuario completo para que el script de login lo use
        }
        return false;
    }

    public function register($nombre, $email, $password) { // El método ahora maneja toda la lógica
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $this->db->beginTransaction();

            // 1. Comprobar si el usuario ya existe
            $stmtCheck = $this->db->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                throw new Exception('Este nombre de usuario ya está en uso.');
            }

            // 2. Insertar el nuevo usuario
            $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash]);
            $nuevo_id = $this->db->lastInsertId();

            // 3. Establecer valores por defecto (día de inicio y fecha de borrado)
            $fechaRegistro = new DateTime();
            $fechaBorrado = (clone $fechaRegistro)->modify('+4 months');
            $fechaBorradoStr = $fechaBorrado->format('Y-m-d H:i:s');
            $stmtUpdate = $this->db->prepare("UPDATE usuarios SET dia_inicio_mes = 1, fecha_borrado = ? WHERE id = ?");
            $stmtUpdate->execute([$fechaBorradoStr, $nuevo_id]);
            
            // 4. Crear las categorías por defecto para el usuario
            $catModel = new CategoriaModel($this->db);
            $catModel->crearCategoriasPorDefecto($nuevo_id);

            $this->db->commit();

            return $nuevo_id; // Devolvemos el ID del nuevo usuario

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // error_log('Error en registro (AuthController): ' . $e->getMessage());
            return false;
        }
    }
}