<?php
class DashboardModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function getNombreColumnaImporte() {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM transacciones LIKE 'importe'");
        return ($stmt->rowCount() > 0) ? 'importe' : 'monto';
    }

    public function getKpis($usuario_id, $fecha_inicio, $fecha_fin) {
        $col = $this->getNombreColumnaImporte();
        
        $stmtIngresos = $this->pdo->prepare("SELECT SUM($col) FROM transacciones WHERE usuario_id = ? AND $col > 0 AND fecha >= ? AND fecha <= ?");
        $stmtIngresos->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        $ingresos = $stmtIngresos->fetchColumn() ?: 0;

        $stmtGastos = $this->pdo->prepare("SELECT SUM(ABS($col)) FROM transacciones WHERE usuario_id = ? AND $col < 0 AND fecha >= ? AND fecha <= ?");
        $stmtGastos->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        $gastos = $stmtGastos->fetchColumn() ?: 0;

        return [
            'ingresos' => (float)$ingresos,
            'gastos' => (float)$gastos
        ];
    }

    public function getDistribucionGastos($usuario_id, $fecha_inicio, $fecha_fin) {
        $col = $this->getNombreColumnaImporte();
        // Agrupamos por el ID real de la categoría
        $stmt = $this->pdo->prepare("
            SELECT categoria_id, SUM(ABS($col)) as total 
            FROM transacciones 
            WHERE usuario_id = ? 
              AND $col < 0 
              AND fecha >= ? 
              AND fecha <= ?
            GROUP BY categoria_id
        ");
        $stmt->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>