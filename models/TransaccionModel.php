<?php
class TransaccionModel {
    private $db;
    private $userId;

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    public function listar($limit = 10) {
        // FILTRO CRÍTICO: WHERE t.usuario_id = ?
        $sql = "SELECT t.*, c.nombre as categoria_nombre, c.color as categoria_color 
                FROM transacciones t 
                LEFT JOIN categorias c ON t.categoria_id = c.id 
                WHERE t.usuario_id = ? 
                ORDER BY t.fecha DESC, t.id DESC LIMIT $limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerBalance() {
        $stmt = $this->db->prepare("SELECT SUM(importe) FROM transacciones WHERE usuario_id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function obtenerDesgloseGastos() {
        $sql = "SELECT c.nombre, c.color, SUM(ABS(t.importe)) as total 
                FROM transacciones t 
                JOIN categorias c ON t.categoria_id = c.id 
                WHERE t.usuario_id = ? AND t.importe < 0 
                GROUP BY c.id, c.nombre, c.color HAVING total > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Métodos de escritura
    public function crear($fecha, $desc, $importe, $cat_id) {
        $sql = "INSERT INTO transacciones (usuario_id, fecha, descripcion, importe, categoria_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$this->userId, $fecha, $desc, $importe, $cat_id]);
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM transacciones WHERE id=? AND usuario_id=?");
        return $stmt->execute([$id, $this->userId]);
    }
    
    public function actualizar($id, $fecha, $desc, $importe, $cat_id) {
        $sql = "UPDATE transacciones SET fecha=?, descripcion=?, importe=?, categoria_id=? WHERE id=? AND usuario_id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$fecha, $desc, $importe, $cat_id, $id, $this->userId]);
    }
}