<?php
class CategoriaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtiene todas las categorías planas (para los desplegables)
    public function getAll($usuario_id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nombre ASC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene el árbol genealógico (Madres e Hijas) para la pantalla principal
    public function getAllTree($usuario_id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY parent_id, nombre ASC");
        $stmt->execute([$usuario_id]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->buildTree($categorias);
    }

    // Función mágica que construye las ramas del árbol
    private function buildTree(array $elements, $parentId = null) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function save($data, $usuario_id) {
        $parent = !empty($data['parent_id']) ? $data['parent_id'] : null;
        $color = !empty($data['color']) ? $data['color'] : '#cbd5e1';

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("UPDATE categorias SET nombre = ?, parent_id = ?, color = ? WHERE id = ? AND usuario_id = ?");
            return $stmt->execute([$data['nombre'], $parent, $color, $data['id'], $usuario_id]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO categorias (usuario_id, nombre, parent_id, color) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$usuario_id, $data['nombre'], $parent, $color]);
        }
    }

    public function delete($id, $usuario_id) {
        // Por seguridad, borramos primero las subcategorías que dependan de esta
        $stmtHijas = $this->db->prepare("DELETE FROM categorias WHERE parent_id = ? AND usuario_id = ?");
        $stmtHijas->execute([$id, $usuario_id]);

        // Luego borramos la categoría principal
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }
}
?>