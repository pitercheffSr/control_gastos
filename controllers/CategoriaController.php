<?php

require_once __DIR__ . '/../models/CategoriaModel.php';

class CategoriaController
{
	private CategoriaModel $model;

	public function __construct(PDO $pdo)
	{
		$this->model = new CategoriaModel($pdo);
	}

	/* =====================================================
       LISTAR
    ===================================================== */
	public function listar(): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return [];
		}

		return $this->model->listarTodas();
	}

	/* =====================================================
       CREAR
    ===================================================== */
	public function crear(array $data): array
	{
		$nombre = trim($data['nombre'] ?? '');
		$tipo   = $data['tipo'] ?? 'gasto';
		$parent = $data['parent_id'] ?? null;

		if ($nombre === '') {
			return ['ok' => false, 'error' => 'Nombre vacío'];
		}

		$id = $this->model->crear([
			'nombre'    => $nombre,
			'tipo'      => $tipo,
			'parent_id' => $parent,
		]);

		return ['ok' => true, 'id' => $id];
	}

	/* =====================================================
       EDITAR
    ===================================================== */
	public function editar(array $data): array
	{
		if (empty($data['id'])) {
			return ['ok' => false, 'error' => 'ID no proporcionado'];
		}

		$id = (int) $data['id'];
		$parent = $data['parent_id'] ?? null;

		// 🚫 No permitir que una categoría sea padre de sí misma
		if ($parent !== null && (int)$parent === $id) {
			return [
				'ok' => false,
				'error' => 'Una categoría no puede depender de sí misma'
			];
		}

		$ok = $this->model->editar(
			$id,
			[
				'nombre'    => trim($data['nombre'] ?? ''),
				'tipo'      => $data['tipo'] ?? 'gasto',
				'parent_id' => $parent,
			]
		);

		return ['ok' => $ok];
	}

	/* =====================================================
       ELIMINAR
    ===================================================== */
	public function eliminar(array $data): array
	{
		if (empty($data['id'])) {
			return ['ok' => false, 'error' => 'ID no proporcionado'];
		}

		$ok = $this->model->eliminarConHijos((int) $data['id']);
		return ['ok' => $ok];
	}
}
