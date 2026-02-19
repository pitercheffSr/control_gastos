<?php
class DashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getDistribucionGastos($usuario_id, $mes = null) {
        // CORRECCIÓN: Usamos c.tipo_fijo en lugar de c.tipo
        $sql = "SELECT c.tipo_fijo as tipo, SUM(ABS(t.importe)) as total 
                FROM transacciones t
                JOIN categorias c ON t.categoria_id = c.id
                WHERE t.usuario_id = ? AND t.importe < 0";
        
        $params = [$usuario_id];

        if ($mes) {
            $sql .= " AND DATE_FORMAT(t.fecha, '%Y-%m') = ?";
            $params[] = $mes;
        }

        $sql .= " GROUP BY c.tipo_fijo";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getKpis($usuario_id, $mes = null) {
        $sql = "SELECT 
                    SUM(CASE WHEN importe > 0 THEN importe ELSE 0 END) as ingresos,
                    SUM(CASE WHEN importe < 0 THEN ABS(importe) ELSE 0 END) as gastos
                FROM transacciones 
                WHERE usuario_id = ?";
        
        $params = [$usuario_id];

        if ($mes) {
            $sql .= " AND DATE_FORMAT(fecha, '%Y-%m') = ?";
            $params[] = $mes;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}