<?php

class DashboardController
{

	private $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function resumen()
	{
		if (!isset($_SESSION['usuario_id'])) return ['ok' => false, 'error' => 'No session'];

		$userId = $_SESSION['usuario_id'];
		$mesActual = date('Y-m');

		try {
			$stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(monto), 0) as total
                FROM transacciones
                WHERE id_usuario = ?
                  AND tipo = 'ingreso'
                  AND DATE_FORMAT(fecha, '%Y-%m') = ?
            ");
			$stmt->execute([$userId, $mesActual]);
			$ingresos = (float) $stmt->fetchColumn();

			$stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(monto), 0) as total
                FROM transacciones
                WHERE id_usuario = ?
                  AND tipo = 'gasto'
                  AND DATE_FORMAT(fecha, '%Y-%m') = ?
            ");
			$stmt->execute([$userId, $mesActual]);
			$gastos = (float) $stmt->fetchColumn();

			return [
				'ok' => true,
				'data' => [
					'ingresos_mes' => $ingresos,
					'gastos_mes'   => $gastos,
					'balance_mes'  => $ingresos - $gastos
				]
			];
		} catch (PDOException $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}

	public function porcentaje()
	{
		$res = $this->resumen();
		if (!$res['ok']) return $res;
		$ing = $res['data']['ingresos_mes'];
		$gas = $res['data']['gastos_mes'];
		return [
			'ok' => true,
			'data' => ['porcentaje_gasto' => ($ing > 0) ? ($gas / $ing) * 100 : 0]
		];
	}

	public function distribucion()
	{
		if (!isset($_SESSION['usuario_id'])) return ['ok' => false];
		$userId = $_SESSION['usuario_id'];
		$mesActual = date('Y-m');

		try {
			$sql = "
            SELECT
                CASE
                    WHEN c.nombre LIKE '%50%' THEN '50'
                    WHEN c.nombre LIKE '%30%' THEN '30'
                    WHEN c.nombre LIKE '%20%' THEN '20'
                    ELSE 'Otros'
                END as categoria,
                SUM(t.monto) as total
            FROM transacciones t
            JOIN categorias c ON t.id_categoria = c.id
            WHERE t.id_usuario = ?
              AND t.tipo = 'gasto'
              AND DATE_FORMAT(t.fecha, '%Y-%m') = ?
            GROUP BY 1
            HAVING categoria != 'Otros'
        ";

			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$userId, $mesActual]);
			$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

			return [
				'ok' => true,
				'data' => $resultados
			];
		} catch (PDOException $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}

	/* ------------------------------------------------------------
       NUEVA FUNCIÓN: MOVIMIENTOS CON SUBSUBCATEGORÍAS
    ------------------------------------------------------------ */
	public function movimientos($usuarioId, $page, $limit)
	{
		$offset = ($page - 1) * $limit;
		try {
			$sql = "
                SELECT t.id, t.fecha, t.descripcion, t.monto, t.tipo,
                       c.nombre as categoria_nombre,
                       sc.nombre as subcategoria_nombre,
                       ssc.nombre as subsubcategoria_nombre
                FROM transacciones t
                LEFT JOIN categorias c ON t.id_categoria = c.id
                LEFT JOIN subcategorias sc ON t.id_subcategoria = sc.id
                LEFT JOIN subsubcategorias ssc ON t.id_subsubcategoria = ssc.id
                WHERE t.id_usuario = :uid
                ORDER BY t.fecha DESC
                LIMIT :limit OFFSET :offset
            ";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
			$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
			$stmt->execute();
			return ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
		} catch (PDOException $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}
} // <--- Fin de la clase
