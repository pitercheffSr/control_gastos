<?php
// Aseguramos que las rutas coinciden exactamente con los nombres de los archivos
require_once __DIR__ . '/../models/TransaccionModel.php';
require_once __DIR__ . '/../models/CategoriaModel.php';

class TransaccionesController {
    private $model;
    private $categoriaModel;

    public function __construct($pdo) {
        $this->model = new TransaccionModel($pdo);
        $this->categoriaModel = new CategoriaModel($pdo);
    }

    public function manejarPeticion($uid) {
        header('Content-Type: application/json');
        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            switch ($action) {
                case 'get':
                    echo json_encode($this->model->getById($_GET['id'], $uid));
                    break;
                case 'getCategorias':
                    echo json_encode($this->categoriaModel->getAll($uid));
                    break;
                case 'getAllLimit':
                    echo json_encode($this->model->getAll($uid, 5));
                    break;
                default:
                    echo json_encode($this->model->getAll($uid));
                    break;
            }
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($action === 'save') {
                $input['usuario_id'] = $uid; 
                echo json_encode(['success' => $this->model->save($input)]);
            } elseif ($action === 'delete') {
                echo json_encode(['success' => $this->model->delete($input['id'], $uid)]);
            }
        }
        exit;
    }
}