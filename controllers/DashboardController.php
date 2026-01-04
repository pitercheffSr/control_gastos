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
	/**
	 * Movimientos recientes con paginación
	 */
	public function movimientos(int $usuarioId, int $page, int $limit): array
{
    $offset = ($page - 1) * $limit;

    // Total de transacciones (para paginación)
    $total = $this->model->contarMovimientos($usuarioId);
    $totalPages = max(1, (int) ceil($total / $limit));

    // Datos paginados
    $rows = $this->model->ultimosMovimientos(
        $usuarioId,
        $limit,
        $offset
    );

    return [
        'ok'         => true,
        'data'       => $rows,
        'page'       => $page,
        'totalPages' => $totalPages,
        'total'      => $total
    ];
}


}
