<?php
class TransaccionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtiene todas las transacciones estrictamente del usuario activo
    public function getAll($usuario_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, c.nombre as categoria_nombre 
            FROM transacciones t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.usuario_id = ? 
            ORDER BY t.fecha DESC, t.id DESC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene solo las últimas X transacciones (Para el Dashboard)
    public function getAllLimit($usuario_id, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT t.*, c.nombre as categoria_nombre 
            FROM transacciones t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.usuario_id = ? 
            ORDER BY t.fecha DESC, t.id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guarda o edita una transacción asegurando que el ID es del usuario
    public function save($data, $usuario_id) {
        try {
            if (!empty($data['id'])) {
                // UPDATE: Solo actualiza si el ID coincide Y pertenece al usuario
                $stmt = $this->db->prepare("UPDATE transacciones SET fecha = ?, descripcion = ?, importe = ?, categoria_id = ? WHERE id = ? AND usuario_id = ?");
                return $stmt->execute([
                    $data['fecha'], 
                    $data['descripcion'], 
                    $data['monto'], // El formulario lo manda como 'monto', pero lo guardamos en la columna 'importe'
                    $data['categoria_id'], 
                    $data['id'], 
                    $usuario_id
                ]);
            } else {
                // INSERT: Guarda el nuevo registro con el ID del usuario
                $stmt = $this->db->prepare("INSERT INTO transacciones (usuario_id, fecha, descripcion, importe, categoria_id) VALUES (?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $usuario_id, 
                    $data['fecha'], 
                    $data['descripcion'], 
                    $data['monto'], 
                    $data['categoria_id']
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error al guardar transacción: " . $e->getMessage());
            return false;
        }
    }

    // Borra una transacción solo si pertenece a este usuario
    public function delete($id, $usuario_id) {
        $stmt = $this->db->prepare("DELETE FROM transacciones WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }
}