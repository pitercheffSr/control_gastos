<?php
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController {
    private $model;

    public function __construct($pdo) {
        $this->model = new DashboardModel($pdo);
    }

    public function manejarPeticion($uid) {
        header('Content-Type: application/json');
        $action = $_GET['action'] ?? '';
        $mes = $_GET['mes'] ?? null;

        switch ($action) {
            case 'getDistribucion':
                echo json_encode($this->model->getDistribucionGastos($uid, $mes));
                break;
            case 'getKpis':
                echo json_encode($this->model->getKpis($uid, $mes));
                break;
            default:
                echo json_encode(['error' => 'Acción no válida']);
                break;
        }
        exit;
    }
}