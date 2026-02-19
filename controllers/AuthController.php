<?php
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
            $_SESSION['usuario_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function register($nombre, $email, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            return $stmt->execute([$nombre, $email, $hash]);
        } catch (PDOException $e) {
            return false;
        }
    }
}