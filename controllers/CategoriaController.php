<?php

/**
 * ------------------------------------------------------------
 * CategoriaController.php
 * ------------------------------------------------------------
 * Controlador para acciones relacionadas con categorías.
 *
 * - Orquesta la lógica (no SQL directo)
 * - Usa CategoriaModel
 * - Devuelve arrays (no imprime ni redirige)
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/CategoriaModel.php';

class CategoriaController
{
    private CategoriaModel $model;

public function __construct(PDO $pdo)
{
    $this->pdo = $pdo;
    $this->model = new CategoriaModel($pdo);
}

    /**
     * Alta de categoría
     */
    public function addCategoria(array $data): array
    {
        $nombre = trim($data['nombre'] ?? '');

        if ($nombre === '') {
            return [
                'status'  => 'error',
                'message' => 'Nombre vacío'
            ];
        }

        $id = $this->model->crearCategoria($nombre);

        return [
            'status'  => 'success',
            'message' => 'Categoría agregada',
            'id'      => $id,
            'nombre'  => $nombre
        ];
    }
}
/**
 * Alta de subcategoría
 */
public function addSubcategoria(array $data)
{
    $idCategoria = (int) ($data['id_categoria'] ?? 0);
    $nombre      = trim($data['nombre'] ?? '');

    if ($idCategoria <= 0 || $nombre === '') {
        return [
            'status'  => 'error',
            'message' => 'Datos inválidos'
        ];
    }

    require_once __DIR__ . '/../models/SubcategoriaModel.php';

    $model = new SubcategoriaModel($this->pdo);
    $id = $model->crearSubcategoria($idCategoria, $nombre);

    return [
        'status'  => 'success',
        'message' => 'Subcategoría agregada',
        'id'      => $id,
        'nombre'  => $nombre
    ];
}
public function addSubSubcategoria(array $data)
{
    $idSubcategoria = (int) ($data['id_subcategoria'] ?? 0);
    $nombre         = trim($data['nombre'] ?? '');

    if ($idSubcategoria <= 0 || $nombre === '') {
        return [
            'status'  => 'error',
            'message' => 'Datos inválidos'
        ];
    }

    require_once __DIR__ . '/../models/SubSubcategoriaModel.php';

    $model = new SubSubcategoriaModel($this->pdo);
    $id = $model->crearSubSubcategoria($idSubcategoria, $nombre);

    return [
        'status'  => 'success',
        'message' => 'Sub-Subcategoría agregada',
        'id'      => $id,
        'nombre'  => $nombre
    ];
}
