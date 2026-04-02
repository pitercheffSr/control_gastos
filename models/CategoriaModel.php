<?php
class CategoriaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? OR usuario_id IS NULL ORDER BY nombre ASC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (usuario_id, nombre, tipo_fijo, parent_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $nombre, $tipo_fijo, $parent_id]);
    }

    public function update($id, $usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        // Solo permite actualizar si la categoría te pertenece
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = ?, tipo_fijo = ?, parent_id = ? WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$nombre, $tipo_fijo, $parent_id, $id, $usuario_id]);
    }

    public function delete($id, $usuario_id) {
        // 1. Ponemos en "Por clasificar" (NULL) los movimientos que usaran esta categoría
        $stmtUpdate = $this->pdo->prepare("UPDATE transacciones SET categoria_id = NULL WHERE categoria_id = ? AND usuario_id = ?");
        $stmtUpdate->execute([$id, $usuario_id]);

        // 2. Borramos la categoría (la condición 'usuario_id = ?' bloquea el borrado de las fijas)
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }
}
?>