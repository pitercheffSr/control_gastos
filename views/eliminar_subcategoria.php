<?php

/**
 * ------------------------------------------------------------
 * eliminar_categoria.php
 * ------------------------------------------------------------
 * Elimina una subcategoría perteneciente al usuario autenticado.
 *
 * Migrado de mysqli a PDO.
 * - Usa db.php como conexión única
 * - Mantiene validaciones, mensajes y redirección
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

// Validar parámetro ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de subcategoría no proporcionado';
    header('Location: gestion_conceptos.php');
    exit;
}

$id_subcategoria = (int) $_GET['id'];
$id_usuario      = (int) $_SESSION['usuario_id'];

try {
    /**
     * --------------------------------------------------------
     * Verificar que la subcategoría pertenezca al usuario
     * --------------------------------------------------------
     */
    $stmt = $pdo->prepare(
        'SELECT id
         FROM subcategorias
         WHERE id = :id
           AND id_usuario = :id_usuario'
    );
    $stmt->execute([
        'id'         => $id_subcategoria,
        'id_usuario' => $id_usuario
    ]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Subcategoría no encontrada o sin permiso';
        header('Location: gestion_conceptos.php');
        exit;
    }

    /**
     * --------------------------------------------------------
     * Eliminar la subcategoría
     * --------------------------------------------------------
     */
    $stmt = $pdo->prepare(
        'DELETE FROM subcategorias
         WHERE id = :id
           AND id_usuario = :id_usuario'
    );
    $stmt->execute([
        'id'         => $id_subcategoria,
        'id_usuario' => $id_usuario
    ]);

    $_SESSION['mensaje'] = 'Subcategoría eliminada exitosamente';
} catch (PDOException $e) {
    // No exponer detalles de BD
    $_SESSION['error'] = 'Error al eliminar la subcategoría';
}

// Redirección final
header('Location: gestion_conceptos.php');
exit;
