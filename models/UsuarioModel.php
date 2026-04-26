<?php
require_once __DIR__ . '/BaseModel.php';

class UsuarioModel extends BaseModel {

    public function buscarPorEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $email, $password) {
        // Encriptamos la contraseña de forma segura
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([$nombre, $email, $hash]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
