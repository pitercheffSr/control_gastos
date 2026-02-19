<?php
class TransaccionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($usuario_id, $limit = null) {
        $sql = "SELECT t.*, t.importe as monto, c.nombre as categoria_nombre 
                FROM transacciones t 
                LEFT JOIN categorias c ON t.categoria_id = c.id 
                WHERE t.usuario_id = ? 
                ORDER BY t.fecha DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $usuario_id) {
        $stmt = $this->db->prepare("SELECT t.*, t.importe as monto FROM transacciones WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save($data) {
        if (!empty($data['id'])) {
            $sql = "UPDATE transacciones SET fecha = ?, descripcion = ?, importe = ?, categoria_id = ? 
                    WHERE id = ? AND usuario_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['fecha'], 
                $data['descripcion'], 
                $data['monto'], 
                $data['categoria_id'], 
                $data['id'], 
                $data['usuario_id']
            ]);
        } else {
            $sql = "INSERT INTO transacciones (fecha, descripcion, importe, categoria_id, usuario_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['fecha'], 
                $data['descripcion'], 
                $data['monto'], 
                $data['categoria_id'], 
                $data['usuario_id']
            ]);
        }
    }

    public function delete($id, $usuario_id) {
        $stmt = $this->db->prepare("DELETE FROM transacciones WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }
}