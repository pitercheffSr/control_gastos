<?php
require_once __DIR__ . '/../config.php';

class DashboardController {
    private $pdo;
    private $userId;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->userId = $_SESSION['user_id'];
    }

    public function obtenerDatos($filtros = []) {
        // 1. PREPARAR FILTROS SQL
        $params = [':uid' => $this->userId];
        $where = "WHERE t.usuario_id = :uid";

        // Filtro de Tiempo
        if (!empty($filtros['rango'])) {
            if ($filtros['rango'] == 'mes_actual') {
                $where .= " AND MONTH(t.fecha) = MONTH(CURRENT_DATE()) AND YEAR(t.fecha) = YEAR(CURRENT_DATE())";
            } elseif ($filtros['rango'] == '3_meses') {
                $where .= " AND t.fecha >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            } elseif ($filtros['rango'] == 'custom' && !empty($filtros['fecha_ini']) && !empty($filtros['fecha_fin'])) {
                $where .= " AND t.fecha BETWEEN :f_ini AND :f_fin";
                $params[':f_ini'] = $filtros['fecha_ini'];
                $params[':f_fin'] = $filtros['fecha_fin'];
            }
        }

        // Filtro de Categoría (Complejo: Busca hijos y nietos)
        if (!empty($filtros['cat_padre'])) {
            // Buscamos transacciones de esta categoría O de sus descendientes
            $where .= " AND (
                c.id = :cid OR 
                c.parent_id = :cid OR 
                c.parent_id IN (SELECT id FROM categorias WHERE parent_id = :cid)
            )";
            $params[':cid'] = $filtros['cat_padre'];
        }

        // 2. CONSULTA REGLA 50/30/20 (Magia Jerárquica)
        // Hacemos JOIN con el padre (p) y el abuelo (gp) para encontrar quién tiene el tipo_fijo (necesidad, etc)
        $sqlGrupos = "SELECT 
                        COALESCE(NULLIF(gp.tipo_fijo, 'personalizado'), NULLIF(p.tipo_fijo, 'personalizado'), c.tipo_fijo) as grupo,
                        SUM(t.importe) as total
                      FROM transacciones t
                      JOIN categorias c ON t.categoria_id = c.id
                      LEFT JOIN categorias p ON c.parent_id = p.id
                      LEFT JOIN categorias gp ON p.parent_id = gp.id
                      $where AND t.importe < 0
                      GROUP BY grupo";
        
        $stmt = $this->pdo->prepare($sqlGrupos);
        $stmt->execute($params);
        $gruposRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Normalizamos para que siempre existan las 3 claves
        $grupos = [
            'necesidad' => abs($gruposRaw['necesidad'] ?? 0),
            'deseo'     => abs($gruposRaw['deseo'] ?? 0),
            'ahorro'    => abs($gruposRaw['ahorro'] ?? 0)
        ];

        // 3. OBTENER LISTA DE TRANSACCIONES
        $limit = ($filtros['rango'] == 'todas') ? 1000 : 15;
        $sqlList = "SELECT t.*, c.nombre as cat_nombre, c.color 
                    FROM transacciones t 
                    JOIN categorias c ON t.categoria_id = c.id
                    $where 
                    ORDER BY t.fecha DESC, t.id DESC LIMIT $limit";
        
        $stmtList = $this->pdo->prepare($sqlList);
        $stmtList->execute($params);
        $transacciones = $stmtList->fetchAll();

        // 4. CALCULAR TOTALES GENERALES
        $totalGastos = array_sum($grupos);
        
        // Ingresos (Positivos)
        $sqlIngresos = "SELECT SUM(importe) FROM transacciones t JOIN categorias c ON t.categoria_id = c.id $where AND t.importe > 0";
        $stmtIng = $this->pdo->prepare($sqlIngresos);
        $stmtIng->execute($params);
        $ingresos = $stmtIng->fetchColumn() ?: 0;

        return [
            'grupos' => $grupos,
            'transacciones' => $transacciones,
            'total_gastos' => $totalGastos,
            'ingresos' => $ingresos
        ];
    }

    // Helper para el <select> de filtros
    public function obtenerCategoriasPadre() {
        $stmt = $this->pdo->prepare("SELECT id, nombre FROM categorias WHERE usuario_id = ? AND parent_id IS NULL");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll();
    }
}
?>