<?php
class CategoriaModel {
    private $db;
    private $userId;

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }

    public function listarArbol() {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY tipo, nombre ASC");
        $stmt->execute([$this->userId]);
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Aquí iría tu lógica de ordenar, pero para simplificar devolvemos lista plana filtrada
        // Si tienes la función ordenarCategorias, úsala aquí.
        return $cats; 
    }

    public function obtenerTotalesPorGrupo() {
        $sql = "SELECT c.grupo_503020, SUM(ABS(t.importe)) as total 
                FROM transacciones t 
                JOIN categorias c ON t.categoria_id = c.id 
                WHERE t.usuario_id = ? AND t.importe < 0 AND c.grupo_503020 != 'indefinido'
                GROUP BY c.grupo_503020";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Métodos mínimos para que no falle el dashboard
    public function crear($nombre, $parent, $tipo, $grupo, $color, $icono) {
        $sql = "INSERT INTO categorias (usuario_id, nombre, parent_id, tipo, grupo_503020, color, icono) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$this->userId, $nombre, $parent, $tipo, $grupo, $color, $icono]);
    }
}