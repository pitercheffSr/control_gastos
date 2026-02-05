<?php
/**
 * models/TransaccionModel.php
 * Gestiona la lectura, inserción y modificación de movimientos contables.
 */
class TransaccionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Lista las transacciones ordenadas por fecha (más reciente primero).
     * Incluye datos de la categoría asociada (nombre, icono, color).
     */
    public function listar($limit = 50) {
        $sql = "SELECT 
                    t.id, 
                    t.fecha, 
                    t.descripcion, 
                    t.importe, 
                    c.nombre AS categoria_nombre, 
                    c.icono AS categoria_icono, 
                    c.color AS categoria_color 
                FROM transacciones t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                ORDER BY t.fecha DESC, t.id DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el balance total (Suma de importes)
     */
    public function obtenerBalance() {
        $stmt = $this->db->query("SELECT SUM(importe) as total FROM transacciones");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    /**
     * Crea una nueva transacción
     */
    public function crear($fecha, $descripcion, $importe, $categoria_id) {
        $sql = "INSERT INTO transacciones (fecha, descripcion, importe, categoria_id) 
                VALUES (:fecha, :desc, :importe, :cat_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':fecha' => $fecha,
            ':desc' => $descripcion,
            ':importe' => $importe,
            ':cat_id' => !empty($categoria_id) ? $categoria_id : null
        ]);
    }

    /**
     * Elimina una transacción por ID
     */
    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM transacciones WHERE id = ?");
        return $stmt->execute([$id]);
    }
}