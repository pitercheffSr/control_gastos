<?php

/**
 * ------------------------------------------------------------
 * DashboardController
 * ------------------------------------------------------------
 * Controlador del dashboard.
 *
 * Responsabilidades:
 * - Validar sesión
 * - Orquestar llamadas al modelo
 * - NO contiene SQL
 * - NO imprime JSON directamente
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController
{
	private DashboardModel $model;

	public function __construct(PDO $pdo)
	{
		$this->model = new DashboardModel($pdo);
	}

	/**
	 * Resumen principal del dashboard
	 */
	public function resumen(): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return [
				'ok'    => false,
				'error' => 'No autenticado'
			];
		}

		$usuarioId = (int) $_SESSION['usuario_id'];

		$data = $this->model->resumenGeneral($usuarioId);

		return [
			'ok'   => true,
			'data' => $data
		];
	}

	/**
	 * Distribución 50 / 30 / 20
	 */
	public function distribucion(): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return [
				'ok'    => false,
				'error' => 'No autenticado'
			];
		}

		$data = $this->model->distribucion503020(
			(int) $_SESSION['usuario_id']
		);

		return [
			'ok'   => true,
			'data' => $data
		];
	}
	/**
	 * Porcentaje de gasto sobre ingresos
	 */
	public function porcentaje(): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return [
				'ok'    => false,
				'error' => 'No autenticado'
			];
		}

		$data = $this->model->porcentajeGasto(
			(int) $_SESSION['usuario_id']
		);

		return [
			'ok'   => true,
			'data' => $data
		];
	}
	public function movimientos(int $page, int $limit, int $offset): array
	{
		if (!isset($_SESSION['usuario_id'])) {
			return ['ok' => false, 'error' => 'No autenticado'];
		}

		$data = $this->model->ultimosMovimientos(
			(int)$_SESSION['usuario_id'],
			$limit,
			$offset
		);

		return [
			'ok'   => true,
			'data' => $data,
			'page' => $page
		];
	}

}
