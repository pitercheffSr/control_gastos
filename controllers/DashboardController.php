<?php
require_once __DIR__ . '/../models/TransaccionModel.php';
require_once __DIR__ . '/../models/CategoriaModel.php';

class DashboardController {
    private $transModel;
    private $catModel;

    public function __construct($db) {
        // ¡OJO! Ahora pasamos el ID del usuario al crear el modelo
        $userId = $_SESSION['usuario_id']; 
        $this->transModel = new TransaccionModel($db, $userId);
        $this->catModel = new CategoriaModel($db, $userId);
    }

    public function obtenerResumen() {
        return [
            'balance'       => $this->transModel->obtenerBalance(),
            'recientes'     => $this->transModel->listar(7),
            'por_categoria' => $this->transModel->obtenerDesgloseGastos(),
            'regla_503020'  => $this->catModel->obtenerTotalesPorGrupo()
        ];
    }
}