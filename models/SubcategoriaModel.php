<?php

/**
 * ------------------------------------------------------------
 * SubcategoriaModel.php
 * ------------------------------------------------------------
 * Modelo de acceso a datos para subcategorías.
 * ------------------------------------------------------------
 */

class SubcategoriaModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearSubcategoria(int $idCategoria, string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subcategorias (id_categoria, nombre)
             VALUES (:id_categoria, :nombre)'
        );

        $stmt->execute([
            'id_categoria' => $idCategoria,
            'nombre'       => $nombre
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
