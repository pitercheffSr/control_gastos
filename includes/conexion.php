<?php

// Datos de conexión
$host = 'localhost';
$usuario = 'root'; // Usuario por defecto de MySQL en LAMP
$password = '1234'; // Contraseña por defecto vacía en LAMP. Si configuraste una, úsala aquí.
$bd = 'bd_503020';

// Crear la conexión
$conexion = new mysqli($host, $usuario, $password, $bd);

// Comprobar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: configurar la codificación para asegurar compatibilidad de caracteres
$conexion->set_charset("utf8");
