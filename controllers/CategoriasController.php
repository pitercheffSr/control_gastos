<?php
require_once __DIR__ . '/../models/CategoriaModel.php';

class CategoriasController {
    private $model;

    public function __construct($db) {
        $userId = $_SESSION['usuario_id'];
        $this->model = new CategoriaModel($db, $userId);
    }

    public function index() { return $this->model->listarArbol(); }

    public function guardar($datos) {
        $nombre = $datos['nombre'];
        $parent = !empty($datos['parent_id']) ? $datos['parent_id'] : null;
        $tipo   = $datos['tipo'];
        $grupo  = $datos['grupo_503020'];
        $color  = $datos['color'];
        $icono  = $datos['icono'] ?? 'icon-bookmark';

        if (!empty($datos['id'])) {
            $this->model->actualizar($datos['id'], $nombre, $parent, $tipo, $grupo, $color, $icono);
        } else {
            $this->model->crear($nombre, $parent, $tipo, $grupo, $color, $icono);
        }
    }
    public function eliminar($id) { $this->model->eliminar($id); }
}