<?php
class UsuarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function buscarPorEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $email, $password) {
        // Encriptamos la contraseña de forma segura
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        try {
            return $stmt->execute([$nombre, $email, $hash]);
        } catch (PDOException $e) {
            return false;
        }
    }
}