<?php
class AdminModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllUsers() {
        // Traemos a todos los usuarios ordenados desde el más nuevo al más antiguo
        $stmt = $this->pdo->query("SELECT id, nombre, email, rol, fecha_borrado FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($id) {
        // Al borrar un usuario, ¡sus transacciones se borrarán automáticamente si configuraste bien las relaciones en la BD!
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>