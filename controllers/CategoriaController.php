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

		return $this->model->listarTodas();
	}

	/* =====================================================
       CREAR
    ===================================================== */
	public function crear(array $data): array
	{
		$nombre = trim($data['nombre'] ?? '');
		$tipos_permitidos = ['gasto', 'ingreso', 'ahorro', 'puente'];
		$tipo   = in_array($data['tipo'] ?? '', $tipos_permitidos) ? $data['tipo'] : 'gasto';
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
		$tipos_permitidos = ['gasto', 'ingreso', 'ahorro', 'puente'];

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
				'tipo'      => in_array($data['tipo'] ?? '', $tipos_permitidos) ? $data['tipo'] : 'gasto',
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

	/* =====================================================================
	   MÉTODOS PARA EL ROUTER procesar_categoria.php (Arquitectura Mixta)
	   Estos métodos contienen lógica que idealmente estaría en el Modelo.
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
			// La consulta incluye "AND id_usuario = :id_usuario" para seguridad.
			// Esto previene que un usuario borre categorías por defecto (id_usuario=0)
			// o categorías de otros usuarios.
			$stmt = $this->pdo->prepare(
				'DELETE FROM categorias WHERE id = :id AND id_usuario = :id_usuario'
			);
			$stmt->execute(['id' => $id_categoria, 'id_usuario' => $id_usuario]);

			if ($stmt->rowCount() > 0) {
				return ['status' => 'success', 'message' => 'Categoría eliminada con éxito.'];
			} else {
				return ['status' => 'error', 'message' => 'No se pudo eliminar. La categoría es fija o no se encontró.'];
			}

		} catch (PDOException $e) {
			// Capturar error de restricción de clave foránea (si tiene subcategorías)
			if ($e->getCode() == '23000') {
				 return ['status' => 'error', 'message' => 'No se puede eliminar. La categoría tiene subcategorías o transacciones asociadas.'];
			}
			// error_log('Error en deleteCategoria: ' . $e->getMessage());
			return ['status' => 'error', 'message' => 'Ocurrió un error de base de datos al intentar eliminar.'];
		}
	}
}
