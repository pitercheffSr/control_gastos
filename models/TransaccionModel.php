<?php
class TransaccionModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Averiguamos dinámicamente cómo se llama tu columna de dinero en la BD
    private function getNombreColumnaImporte() {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM transacciones LIKE 'importe'");
        return ($stmt->rowCount() > 0) ? 'importe' : 'monto';
    }

    public function getAll($usuario_id) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.fecha, t.descripcion, t.{$col} as importe, t.categoria_id, c.nombre as categoria_nombre 
            FROM transacciones t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.usuario_id = ? 
            ORDER BY t.fecha DESC, t.id DESC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $categoria_id, $fecha, $descripcion, $importe) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, {$col}) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $categoria_id, $fecha, $descripcion, $importe]);
    }

    public function update($id, $usuario_id, $categoria_id, $fecha, $descripcion, $importe) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("UPDATE transacciones SET categoria_id = ?, fecha = ?, descripcion = ?, {$col} = ? WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$categoria_id, $fecha, $descripcion, $importe, $id, $usuario_id]);
    }

    public function delete($id, $usuario_id) {
        $stmt = $this->pdo->prepare("DELETE FROM transacciones WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }

    public function getPaginated($usuario_id, $page, $limit, $startDate, $endDate, $categoryId, $searchText) {
        $col = $this->getNombreColumnaImporte();
        $offset = ($page - 1) * $limit;

        // --- Construcción dinámica de la cláusula WHERE y los parámetros ---
        $whereClauses = ['t.usuario_id = ?'];
        $params = [$usuario_id];

        if ($startDate) {
            $whereClauses[] = 't.fecha >= ?';
            $params[] = $startDate;
        }
        if ($endDate) {
            $whereClauses[] = 't.fecha <= ?';
            $params[] = $endDate;
        }

        // El filtro de categoría es prioritario sobre el de texto
        if ($categoryId) {
            // Usamos una CTE (Common Table Expression) recursiva para obtener la categoría y todas sus hijas.
            // Esto es mucho más eficiente que hacer múltiples consultas en PHP.
            $whereClauses[] = "t.categoria_id IN (
                WITH RECURSIVE subcategorias AS (
                    SELECT id FROM categorias WHERE id = ? AND usuario_id = ?
                    UNION ALL
                    SELECT c.id FROM categorias c JOIN subcategorias s ON c.parent_id = s.id WHERE c.usuario_id = ?
                ) SELECT id FROM subcategorias
            )";
            $params[] = $categoryId;
            $params[] = $usuario_id; // para la base de la recursión
            $params[] = $usuario_id; // para el paso recursivo
        } elseif ($searchText) {
            $whereClauses[] = '(t.descripcion LIKE ? OR c.nombre LIKE ?)';
            $params[] = '%' . $searchText . '%';
            $params[] = '%' . $searchText . '%';
        }

        $whereSql = " WHERE " . implode(' AND ', $whereClauses);

        // --- Consulta para obtener el total de registros ---
        $sqlCount = "SELECT COUNT(t.id) 
                     FROM transacciones t 
                     LEFT JOIN categorias c ON t.categoria_id = c.id" . $whereSql;

        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        // --- Consulta para obtener los datos paginados ---
        $sqlData = "SELECT t.id, t.fecha, t.descripcion, t.{$col} as importe, t.categoria_id, c.nombre as categoria_nombre 
                    FROM transacciones t 
                    LEFT JOIN categorias c ON t.categoria_id = c.id" . $whereSql . " 
                    ORDER BY t.fecha DESC, t.id DESC 
                    LIMIT ? OFFSET ?";
        
        $stmtData = $this->pdo->prepare($sqlData);

        // Añadimos los parámetros de paginación al final
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmtData->execute($dataParams);
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => (int)$total
        ];
    }
}
?>