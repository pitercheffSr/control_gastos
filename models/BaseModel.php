<?php
abstract class BaseModel {
    protected $pdo;
    private $columnaImporteCache = null;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    protected function getNombreColumnaImporte() {
        if ($this->columnaImporteCache === null) {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM transacciones LIKE 'importe'");
            $this->columnaImporteCache = ($stmt->rowCount() > 0) ? 'importe' : 'monto';
        }
        return $this->columnaImporteCache;
    }
}
?>
