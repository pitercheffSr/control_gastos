<?php
require_once __DIR__ . '/BaseModel.php';

class DashboardModel extends BaseModel {

    public function getKpis($usuario_id, $fecha_inicio, $fecha_fin) {
        $col = $this->getNombreColumnaImporte();

        $stmtIngresos = $this->pdo->prepare("SELECT SUM($col) FROM transacciones WHERE usuario_id = ? AND $col > 0 AND fecha >= ? AND fecha <= ?");
        $stmtIngresos->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        $ingresos = $stmtIngresos->fetchColumn() ?: 0;

        $stmtGastos = $this->pdo->prepare("SELECT SUM(ABS(t.{$col})) FROM transacciones t LEFT JOIN categorias c ON t.categoria_id = c.id WHERE t.usuario_id = ? AND t.{$col} < 0 AND t.fecha >= ? AND t.fecha <= ? AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente')");
        $stmtGastos->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        $gastos = $stmtGastos->fetchColumn() ?: 0;

        return [
            'ingresos' => (float)$ingresos,
            'gastos' => (float)$gastos
        ];
    }

    public function getDistribucionGastos($usuario_id, $fecha_inicio, $fecha_fin) {
        $col = $this->getNombreColumnaImporte();
        // Agrupamos por el ID real de la categoría
        $stmt = $this->pdo->prepare("
            SELECT t.categoria_id, SUM(ABS(t.{$col})) as total
            FROM transacciones t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.usuario_id = ?
              AND t.{$col} < 0
              AND t.fecha >= ?
              AND t.fecha <= ?
              AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente')
            GROUP BY categoria_id
        ");
        $stmt->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistoricalBalance($usuario_id, $startDate, $endDate) {
        $col = $this->getNombreColumnaImporte();

        // 1. Obtener el balance inicial antes de la fecha de inicio del periodo
        // Esto asegura que el gráfico comience con el balance real acumulado hasta ese momento.
        $sqlInitialBalance = "
            SELECT SUM(
                CASE
                    WHEN t.{$col} > 0 THEN t.{$col}
                    WHEN t.{$col} < 0 AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente') THEN t.{$col}
                    ELSE 0
                END
            ) AS initial_balance
            FROM transacciones t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.usuario_id = ? AND t.fecha < ?
        ";
        $stmtInitialBalance = $this->pdo->prepare($sqlInitialBalance);
        $stmtInitialBalance->execute([$usuario_id, $startDate]);
        $initialBalance = (float)($stmtInitialBalance->fetchColumn() ?? 0);

        // 2. Obtener los cambios netos mensuales dentro del periodo seleccionado
        $sqlMonthlyChanges = "
            SELECT
                DATE_FORMAT(t.fecha, '%Y-%m-01') AS month_start,
                SUM(
                    CASE
                        WHEN t.{$col} > 0 THEN t.{$col}
                        WHEN t.{$col} < 0 AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente') THEN t.{$col}
                        ELSE 0
                    END
                ) AS monthly_net_change
            FROM
                transacciones t
            LEFT JOIN
                categorias c ON t.categoria_id = c.id
            WHERE
                t.usuario_id = ?
                AND t.fecha >= ?
                AND t.fecha <= ?
            GROUP BY
                month_start
            ORDER BY
                month_start ASC
        ";
        $stmtMonthlyChanges = $this->pdo->prepare($sqlMonthlyChanges);
        $stmtMonthlyChanges->execute([$usuario_id, $startDate, $endDate]);
        $monthlyChanges = $stmtMonthlyChanges->fetchAll(PDO::FETCH_ASSOC);

        // 3. Calcular el balance acumulado
        $cumulativeData = [];
        $currentBalance = $initialBalance;
        foreach ($monthlyChanges as $change) {
            $currentBalance += (float)$change['monthly_net_change'];
            $cumulativeData[] = ['month_start' => $change['month_start'], 'balance' => $currentBalance];
        }

        return $cumulativeData;
    }
}
?>
