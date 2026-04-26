<?php

require_once __DIR__ . '/../models/CategoriaModel.php';

class CategoriaController
{
	private CategoriaModel $model;

	public function __construct(PDO $pdo)
	{
		$this->model = new CategoriaModel($pdo);
	}

	private function getUserId(): ?int
	{
		return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
	}

	/* =====================================================
       LISTAR
    ===================================================== */
	public function listar(): array
	{
		$uid = $this->getUserId();
		if (!$uid) {
			return [];
		}

		return $this->model->getAll($uid);
	}

	/* =====================================================
       CREAR
    ===================================================== */
	public function crear(array $data): array
	{
		$nombre = trim($data['nombre'] ?? '');
		$tipos_permitidos = ['gasto', 'ingreso', 'ahorro', 'puente'];
		$tipo   = in_array($data['tipo'] ?? '', $tipos_permitidos) ? $data['tipo'] : 'gasto';
		$parent = !empty($data['parent_id']) ? $data['parent_id'] : null;

		if ($nombre === '') {
			return ['ok' => false, 'error' => 'Nombre vacío'];
		}

		$uid = $this->getUserId();
		if (!$uid) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		$ok = $this->model->create($uid, $nombre, $tipo, $parent);

		return ['ok' => $ok];
	}

	/* =====================================================
       EDITAR
    ===================================================== */
	public function editar(array $data): array
	{
		if (empty($data['id'])) {
			return ['ok' => false, 'error' => 'ID no proporcionado'];
		}

		$uid = $this->getUserId();
		if (!$uid) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		$id = (int) $data['id'];
		$nombre = trim($data['nombre'] ?? '');
		$parent = !empty($data['parent_id']) ? $data['parent_id'] : null;
		$tipos_permitidos = ['gasto', 'ingreso', 'ahorro', 'puente'];
		$tipo = in_array($data['tipo'] ?? '', $tipos_permitidos) ? $data['tipo'] : 'gasto';

		// 🚫 No permitir que una categoría sea padre de sí misma
		if ($parent !== null && (int)$parent === $id) {
			return [
				'ok' => false,
				'error' => 'Una categoría no puede depender de sí misma'
			];
		}

		$ok = $this->model->update($id, $uid, $nombre, $tipo, $parent);

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

		$uid = $this->getUserId();
		if (!$uid) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		$ok = $this->model->delete((int) $data['id'], $uid);
		return ['ok' => $ok];
	}

	/* =====================================================================
	   MÉTODOS PARA EL ROUTER procesar_categoria.php (Arquitectura Mixta)
	   ===================================================================== */

	public function deleteCategoria($data) {
		$uid = $this->getUserId();
		if (!$uid) {
			return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
		}
		if (empty($data['id'])) {
			return ['status' => 'error', 'message' => 'ID de categoría no proporcionado.'];
		}

		$id_categoria = (int) $data['id'];

		try {
			// Delegamos al modelo, que además gestiona los movimientos huérfanos correctamente
			$ok = $this->model->delete($id_categoria, $uid);

			if ($ok) {
				return ['status' => 'success', 'message' => 'Categoría eliminada con éxito.'];
			} else {
				return ['status' => 'error', 'message' => 'No se pudo eliminar. La categoría es fija o no se encontró.'];
			}

		} catch (PDOException $e) {
			return ['status' => 'error', 'message' => 'Ocurrió un error de base de datos al intentar eliminar.'];
		}
	}
}
