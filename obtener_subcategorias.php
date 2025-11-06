<?php
// Control de errores y registro
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/conexion.php';
require_once 'includes/logger.php'; // Asume que tienes un archivo de logging

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

try {
    // Verificación de autorización
    $id_usuario = $_SESSION['usuario_id'] ?? null;
    if (!$id_usuario) {
        throw new Exception('No autorizado', 401);
    }

    // Validación de parámetros
    $nivel = isset($_GET['nivel']) ? intval($_GET['nivel']) : null;
    
    // Función para validar permisos de categoría
    function validarPermisoCategoria($conexion, $id_usuario, $id_categoria) {
        $stmt_validar = $conexion->prepare("SELECT COUNT(*) as count FROM categorias WHERE id = ? AND (id_usuario = ? OR id_usuario = 0)");
        $stmt_validar->bind_param("ii", $id_categoria, $id_usuario);
        $stmt_validar->execute();
        $result = $stmt_validar->get_result();
        $validacion = $result->fetch_assoc();
        $stmt_validar->close();
        
        return $validacion['count'] > 0;
    }

    // Subcategorías de primer nivel
    if ($nivel === 1 && isset($_GET['id_categoria'])) {
        $id_categoria = (int)$_GET['id_categoria'];
        
        // Validar permiso de categoría
        if (!validarPermisoCategoria($conexion, $id_usuario, $id_categoria)) {
            throw new Exception('Acceso denegado a la categoría', 403);
        }

        // Preparar consulta de subcategorías
        $stmt = $conexion->prepare("
            SELECT id, nombre 
            FROM subcategorias 
            WHERE id_categoria = ? 
              AND (id_usuario = ? OR id_usuario = 0)
              AND parent_id IS NULL 
            ORDER BY nombre ASC
        ");
        $stmt->bind_param("ii", $id_categoria, $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $subcategorias = [];
        while ($row = $resultado->fetch_assoc()) {
            $subcategorias[] = [
                'id' => $row['id'],
                'nombre' => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8')
            ];
        }
        $stmt->close();
        
        echo json_encode($subcategorias);
        exit;
    }

    // Sub-subcategorías
    if ($nivel === 2 && isset($_GET['parent_id'])) {
        $parent_id = (int)$_GET['parent_id'];
        
        // Validar existencia del padre
        $stmt_padre = $conexion->prepare("
            SELECT id_categoria 
            FROM subcategorias 
            WHERE id = ? AND (id_usuario = ? OR id_usuario = 0)
        ");
        $stmt_padre->bind_param("ii", $parent_id, $id_usuario);
        $stmt_padre->execute();
        $resultado_padre = $stmt_padre->get_result();
        
        if ($resultado_padre->num_rows === 0) {
            throw new Exception('Subcategoría padre no encontrada', 404);
        }
        
        $stmt_padre->close();

        // Consulta de sub-subcategorías
        $stmt = $conexion->prepare("
            SELECT id, nombre 
            FROM subcategorias 
            WHERE parent_id = ?
