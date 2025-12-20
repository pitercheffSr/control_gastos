<?php

/**
 * Modelo para sub-subcategorías
 */
class SubSubcategoriaModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearSubSubcategoria(int $idSubcategoria, string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subsubcategorias (id_subcategoria, nombre)
             VALUES (:id_subcategoria, :nombre)'
        );

        $stmt->execute([
            'id_subcategoria' => $idSubcategoria,
            'nombre'          => $nombre
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
