<?php
class CategoriaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nombre ASC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllTree($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY parent_id, nombre ASC");
        $stmt->execute([$usuario_id]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $arbol = [];
        $referencias = [];

        foreach ($categorias as $cat) {
            $cat['children'] = [];
            $referencias[$cat['id']] = $cat;
        }

        foreach ($referencias as $id => &$cat) {
            if (!empty($cat['parent_id']) && isset($referencias[$cat['parent_id']])) {
                $referencias[$cat['parent_id']]['children'][] = &$cat;
            } else {
                $arbol[] = &$cat;
            }
        }
        return $arbol;
    }

    public function create($usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        return $this->save($usuario_id, $nombre, $tipo_fijo, $parent_id);
    }

    public function save($usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        if (empty($parent_id) || $parent_id === '') {
            $parent_id = null;
        }
        $stmt = $this->pdo->prepare("INSERT INTO categorias (usuario_id, nombre, tipo_fijo, parent_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $nombre, $tipo_fijo, $parent_id]);
    }

    public function update($id, $usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        if (empty($parent_id) || $parent_id === '') {
            $parent_id = null;
        }
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = ?, tipo_fijo = ?, parent_id = ? WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$nombre, $tipo_fijo, $parent_id, $id, $usuario_id]);
    }

    public function delete($id, $usuario_id) {
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }

    public function crearCategoriasPorDefecto($usuario_id) {
        $categoriasBase = [
            ['nombre' => 'Ingresos', 'tipo_fijo' => 'ingreso'],
            ['nombre' => '50% Necesidades', 'tipo_fijo' => 'necesidad'],
            ['nombre' => '30% Deseos', 'tipo_fijo' => 'deseo'],
            ['nombre' => '20% Ahorro e Inversión', 'tipo_fijo' => 'ahorro'],
            ['nombre' => 'Por clasificar (Banco)', 'tipo_fijo' => 'gasto']
        ];

        $stmt = $this->pdo->prepare("INSERT INTO categorias (usuario_id, nombre, tipo_fijo, parent_id) VALUES (?, ?, ?, NULL)");

        foreach ($categoriasBase as $cat) {
            $stmt->execute([$usuario_id, $cat['nombre'], $cat['tipo_fijo']]);
        }
    }
}
?>