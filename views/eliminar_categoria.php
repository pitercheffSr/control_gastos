<?php

/**
 * ------------------------------------------------------------
 * eliminar_categoria.php
 * ------------------------------------------------------------
 * Elimina una categoría personalizada del usuario autenticado.
 *
 * Migrado de mysqli a PDO.
 * - Usa db.php como conexión única
 * - Mantiene mensajes y redirecciones originales
 * ------------------------------------------------------------
 */

session_start();

// Conexión PDO unificada
require_once __DIR__ . '/../db.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

// Validar parámetro ID
if (!isset($_GET['id'])) {
    header('Location: gestion_conceptos.php');
    exit;
}

$id_categoria = (int) $_GET['id'];
$id_usuario   = (int) $_SESSION['usuario_id'];

try {
    /**
     * --------------------------------------------------------
     * Eliminar solo categorías personalizadas del usuario
     * --------------------------------------------------------
     */
    $stmt = $pdo->prepare(
        'DELETE FROM categorias
         WHERE id = :id
           AND id_usuario = :id_usuario'
    );

    $stmt->execute([
        'id'         => $id_categoria,
        'id_usuario' => $id_usuario
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Categoría eliminada con éxito.';
    } else {
        $_SESSION['error'] = 'Error al eliminar la categoría. Asegúrate de que no tenga conceptos asociados.';
    }
} catch (PDOException $e) {
    // No exponer detalles de BD
    $_SESSION['error'] = 'Error al eliminar la categoría.';
}

// Redirección final
header('Location: gestion_conceptos.php');
exit;
