<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController {
    private $model;

    public function __construct($db) {
        $this->model = new UsuarioModel($db);
    }

    public function login($email, $password) {
        $user = $this->model->buscarPorEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            return true;
        }
        return false;
    }

    public function registrar($nombre, $email, $password) {
        // 1. Verificar si ya existe
        if ($this->model->buscarPorEmail($email)) {
            return "El email ya está registrado.";
        }
        
        // 2. Crear usuario
        if ($this->model->crear($nombre, $email, $password)) {
            return true;
        } else {
            return "Error al guardar en la base de datos.";
        }
    }
}