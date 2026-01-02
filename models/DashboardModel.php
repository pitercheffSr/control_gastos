<?php

/**
 * ------------------------------------------------------------
 * DashboardModel
 * ------------------------------------------------------------
 * Acceso a datos del dashboard.
 *
 * Reglas:
 * - SOLO SQL
 * - SOLO PDO
 * - NO sesiones
 * - NO JSON
 * ------------------------------------------------------------
 */

class DashboardModel
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * Resumen general:
	 * - Total ingresos
	 * - Total gastos
	 * - Balance
	 */
	public function resumenGeneral(int $usuarioId): array
	{
		$stmt = $this->pdo->prepare("
            SELECT
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS ingresos,
                SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END) AS gastos
            FROM transacciones
            WHERE id_usuario = :uid
        ");

		$stmt->execute(['uid' => $usuarioId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		$ingresos = (float) ($row['ingresos'] ?? 0);
		$gastos   = (float) ($row['gastos'] ?? 0);

		return [
			'ingresos' => $ingresos,
			'gastos'   => $gastos,
			'balance'  => $ingresos - $gastos
		];
	}

	/**
	 * Distribución 50 / 30 / 20
	 *
	 * Se basa en categorías raíz:
	 * - 50% Necesidades
	 * - 30% Deseos
	 * - 20% Ahorro
	 */
	public function distribucion503020(int $usuarioId): array
	{
		// Total de gastos
		$stmtTotal = $this->pdo->prepare("
        SELECT SUM(monto) AS total
        FROM transacciones
        WHERE id_usuario = :uid
          AND tipo = 'gasto'
    ");
		$stmtTotal->execute(['uid' => $usuarioId]);
		$totalGastos = (float) ($stmtTotal->fetchColumn() ?? 0);

		// Distribución por categoría raíz
		$stmt = $this->pdo->prepare("
        SELECT
            c.nombre AS categoria,
            SUM(t.monto) AS total
        FROM transacciones t
        JOIN categorias c ON c.id = t.id_categoria
        WHERE t.id_usuario = :uid
          AND t.tipo = 'gasto'
          AND c.parent_id IS NULL
        GROUP BY c.id
    ");

		$stmt->execute(['uid' => $usuarioId]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Añadir porcentaje a cada categoría
		foreach ($rows as &$r) {
			$r['total'] = (float) $r['total'];
			$r['porcentaje'] = ($totalGastos > 0)
				? round(($r['total'] / $totalGastos) * 100, 2)
				: 0.0;
		}

		return $rows;
	}
	/**
	 * Porcentajes reales de gasto
	 *
	 * Devuelve:
	 * - ingresos
	 * - gastos
	 * - porcentaje_gasto (0–100)
	 *
	 * Regla:
	 * - Si ingresos = 0 → porcentaje = 0 (evitar división por cero)
	 */
	public function porcentajeGasto(int $usuarioId): array
	{
		$stmt = $this->pdo->prepare("
				SELECT
					SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS ingresos,
					SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END) AS gastos
				FROM transacciones
				WHERE id_usuario = :uid
			");

		$stmt->execute(['uid' => $usuarioId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		$ingresos = (float) ($row['ingresos'] ?? 0);
		$gastos   = (float) ($row['gastos'] ?? 0);

		$porcentaje = ($ingresos > 0)
			? round(($gastos / $ingresos) * 100, 2)
			: 0.0;

		return [
			'ingresos'          => $ingresos,
			'gastos'            => $gastos,
			'porcentaje_gasto'  => $porcentaje
		];
	}
}
