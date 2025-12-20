<?php

/**
 * ------------------------------------------------------------
 * CategoriaModel.php
 * ------------------------------------------------------------
 * Modelo de acceso a datos para categorías.
 *
 * - Usa PDO
 * - No maneja sesiones
 * - No redirige
 * - No imprime HTML
 * ------------------------------------------------------------
 */

class CategoriaModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crear una categoría
     */
    public function crearCategoria(string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categorias (nombre) VALUES (:nombre)'
        );

        $stmt->execute(['nombre' => $nombre]);

        return (int) $this->pdo->lastInsertId();
    }
}
