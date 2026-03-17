<?php
class TransaccionModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Averiguamos dinámicamente cómo se llama tu columna de dinero en la BD
    private function getNombreColumnaImporte() {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM transacciones LIKE 'importe'");
        return ($stmt->rowCount() > 0) ? 'importe' : 'monto';
    }

    public function getAll($usuario_id) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.fecha, t.descripcion, t.{$col} as importe, t.categoria_id, c.nombre as categoria_nombre 
            FROM transacciones t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.usuario_id = ? 
            ORDER BY t.fecha DESC, t.id DESC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $categoria_id, $fecha, $descripcion, $importe) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, {$col}) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $categoria_id, $fecha, $descripcion, $importe]);
    }

    public function update($id, $usuario_id, $categoria_id, $fecha, $descripcion, $importe) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("UPDATE transacciones SET categoria_id = ?, fecha = ?, descripcion = ?, {$col} = ? WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$categoria_id, $fecha, $descripcion, $importe, $id, $usuario_id]);
    }

    public function delete($id, $usuario_id) {
        $stmt = $this->pdo->prepare("DELETE FROM transacciones WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }
}
?>