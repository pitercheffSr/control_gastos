<?php

require_once __DIR__ . '/../models/CategoriaModel.php';

class CategoriaController
{
	private CategoriaModel $model;
	private $pdo;

	public function __construct(PDO $pdo)
	{
		$this->model = new CategoriaModel($pdo);
		$this->pdo = $pdo;
	}

	/* =====================================================
       LISTAR
    ===================================================== */
	public function listar(): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return [];
		}

		return $this->model->getAll($_SESSION['usuario_id']);
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

		if (!isset($_SESSION['usuario_id'])) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		$ok = $this->model->create($_SESSION['usuario_id'], $nombre, $tipo, $parent);

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

		if (!isset($_SESSION['usuario_id'])) {
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

		$ok = $this->model->update($id, $_SESSION['usuario_id'], $nombre, $tipo, $parent);

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

		if (!isset($_SESSION['usuario_id'])) {
			return ['ok' => false, 'error' => 'Sesión no válida'];
		}

		$ok = $this->model->delete((int) $data['id'], $_SESSION['usuario_id']);
		return ['ok' => $ok];
	}

	/* =====================================================================
	   MÉTODOS PARA EL ROUTER procesar_categoria.php (Arquitectura Mixta)
	   ===================================================================== */

	public function deleteCategoria($data) {
		if (!isset($_SESSION['usuario_id'])) {
			return ['status' => 'error', 'message' => 'Acceso no autorizado.'];
		}
		if (empty($data['id'])) {
			return ['status' => 'error', 'message' => 'ID de categoría no proporcionado.'];
		}

		$id_categoria = (int) $data['id'];
		$id_usuario   = (int) $_SESSION['usuario_id'];

		try {
			// Delegamos al modelo, que además gestiona los movimientos huérfanos correctamente
			$ok = $this->model->delete($id_categoria, $id_usuario);

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
