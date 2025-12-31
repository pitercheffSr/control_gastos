<?php

/**
 * ------------------------------------------------------------
 * CategoriaModel.php
 * ------------------------------------------------------------
 * Modelo de acceso a datos para categorías (MVC completo)
 * ------------------------------------------------------------
 */

class CategoriaModel
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/* =====================================================
       LISTAR TODAS (PLANO)
    ===================================================== */
	public function listarTodas(): array
	{
		$stmt = $this->pdo->query(
			'SELECT id, nombre, parent_id, tipo FROM categorias ORDER BY nombre'
		);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/* =====================================================
       CREAR
    ===================================================== */
	public function crear(array $data): int
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO categorias (nombre, tipo, parent_id)
             VALUES (:nombre, :tipo, :parent)'
		);

		$stmt->execute([
			'nombre' => $data['nombre'],
			'tipo'   => $data['tipo'],
			'parent' => $data['parent_id'],
		]);

		return (int) $this->pdo->lastInsertId();
	}

	/* =====================================================
       EDITAR
    ===================================================== */
	public function editar(int $id, array $data): bool
	{
		$stmt = $this->pdo->prepare(
			'UPDATE categorias
             SET nombre = :nombre,
                 tipo = :tipo,
                 parent_id = :parent
             WHERE id = :id'
		);

		return $stmt->execute([
			'nombre' => $data['nombre'],
			'tipo'   => $data['tipo'],
			'parent' => $data['parent_id'],
			'id'     => $id,
		]);
	}

	/* =====================================================
       ELIMINAR CON HIJOS (RECURSIVO)
    ===================================================== */
	public function eliminarConHijos(int $id): bool
	{
		// Obtener hijos directos
		$stmt = $this->pdo->prepare(
			'SELECT id FROM categorias WHERE parent_id = :id'
		);
		$stmt->execute(['id' => $id]);

		$hijos = $stmt->fetchAll(PDO::FETCH_COLUMN);

		// Eliminar recursivamente
		foreach ($hijos as $hid) {
			$this->eliminarConHijos((int) $hid);
		}

		// Eliminar la categoría actual
		$stmt = $this->pdo->prepare(
			'DELETE FROM categorias WHERE id = :id'
		);

		return $stmt->execute(['id' => $id]);
	}
}
