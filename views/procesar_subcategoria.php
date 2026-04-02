<?php
/**
 * ------------------------------------------------------------
 * procesar_subcategoria.php
 * ------------------------------------------------------------
 * Procesa la creación de subcategorías (y subcategorías hijas).
 *
 * Migrado de mysqli a PDO.
 * - Usa db.php como conexión única
 * - Mantiene exactamente la misma lógica y redirecciones
 * ------------------------------------------------------------
 */

session_start();

// Conexión PDO unificada
require_once __DIR__ . '/../db.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido';
    header('Location: gestion_conceptos.php');
    exit;
}

// Datos de entrada
$nombre       = trim($_POST['nombre'] ?? '');
$id_categoria = (int) ($_POST['id_categoria'] ?? 0);
$id_usuario   = (int) $_SESSION['usuario_id'];
$parent_id    = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
    ? (int) $_POST['parent_id']
    : null;

try {
    /**
     * --------------------------------------------------------
     * Validar que la categoría exista y sea fija (id_usuario = 0)
     * --------------------------------------------------------
     */
    $stmt = $pdo->prepare(
        'SELECT id FROM categorias WHERE id = :id AND id_usuario = 0'
    );
    $stmt->execute(['id' => $id_categoria]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Categoría no válida';
        header('Location: gestion_conceptos.php');
        exit;
    }

    /**
     * --------------------------------------------------------
     * Si hay parent_id, validar que exista y pertenezca
     * a la misma categoría y usuario
     * --------------------------------------------------------
     */
    if ($parent_id !== null) {
        $stmt = $pdo->prepare(
            'SELECT id
             FROM subcategorias
             WHERE id = :parent_id
               AND id_categoria = :id_categoria
               AND id_usuario = :id_usuario'
        );
        $stmt->execute([
            'parent_id'    => $parent_
