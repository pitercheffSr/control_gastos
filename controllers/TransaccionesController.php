<?php
require_once __DIR__ . '/../models/TransaccionModel.php';
require_once __DIR__ . '/../models/CategoriaModel.php';

class TransaccionesController {
    private $transModel;
    private $catModel;

    public function __construct($db) {
        $userId = $_SESSION['usuario_id'];
        $this->transModel = new TransaccionModel($db, $userId);
        $this->catModel = new CategoriaModel($db, $userId);
    }

    public function index() {
        return [
            'categorias' => $this->catModel->listarArbol(),
            'movimientos' => $this->transModel->listar(100)
        ];
    }

    public function guardar($datos) {
        $fecha = $datos['fecha'];
        $desc  = $datos['descripcion'];
        $cat   = $datos['categoria_id'];
        $importe = abs($datos['importe']);
        if ($datos['tipo'] === 'gasto') { $importe *= -1; }

        if (!empty($datos['id'])) {
            return $this->transModel->actualizar($datos['id'], $fecha, $desc, $importe, $cat);
        } else {
            return $this->transModel->crear($fecha, $desc, $importe, $cat);
        }
    }

    public function eliminar($id) { return $this->transModel->eliminar($id); }
}