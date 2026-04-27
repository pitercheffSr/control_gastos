<?php
require_once __DIR__ . '/BaseModel.php';

class TransaccionModel extends BaseModel {

    public function getAll($usuario_id, $limit = null) {
        $col = $this->getNombreColumnaImporte();
        $sql = "
            SELECT t.id, t.fecha, t.descripcion, t.{$col} as importe, t.categoria_id, c.nombre as categoria_nombre
            FROM transacciones t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.usuario_id = ?
            ORDER BY t.fecha DESC, t.id DESC
        ";

        $params = [$usuario_id];
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $categoria_id, $fecha, $descripcion, $importe) {
        $col = $this->getNombreColumnaImporte();
        $stmt = $this->pdo->prepare("INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, {$col}) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $categoria_id, $fecha, $descripcion, $importe]);
        return $this->pdo->lastInsertId();
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

    public function deleteMultiple($ids, $usuario_id) {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        // Sanitize all IDs to be integers to prevent SQL injection
        $ids = array_map('intval', $ids);

        // Create placeholders for the IN clause: ?,?,?
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "DELETE FROM transacciones WHERE id IN ({$placeholders}) AND usuario_id = ?";

        $stmt = $this->pdo->prepare($sql);

        // Bind all the IDs and the user_id at the end
        return $stmt->execute(array_merge($ids, [$usuario_id]));
    }

    public function updateCategoryMultiple($ids, $categoryId, $usuario_id) {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        // Sanitize all IDs to be integers
        $ids = array_map('intval', $ids);

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // The categoryId can be null, so we handle that.
        $sql = "UPDATE transacciones SET categoria_id = ? WHERE id IN ({$placeholders}) AND usuario_id = ?";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(array_merge([$categoryId], $ids, [$usuario_id]));
    }

    public function reassignCategory($transactionId, $categoryId, $usuario_id) {
        $sql = "UPDATE transacciones SET categoria_id = ? WHERE id = ? AND usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$categoryId, $transactionId, $usuario_id]);
    }

    public function getById($id, $usuario_id) {
        $col = $this->getNombreColumnaImporte();
        // 1. Obtener datos básicos de la transacción, incluyendo el tipo (gasto/ingreso)
        $stmt = $this->pdo->prepare("
            SELECT id, fecha, descripcion, {$col} as importe, categoria_id,
                   IF({$col} < 0, 'gasto', 'ingreso') as tipo
            FROM transacciones
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->execute([$id, $usuario_id]);
        $transaccion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaccion) {
            return null; // No se encontró o no pertenece al usuario
        }

        // El formulario espera un importe positivo, el tipo ya lo indica.
        $transaccion['importe'] = abs($transaccion['importe']);

        // 2. Si tiene categoría, obtener la ruta completa de ancestros
        $transaccion['categoria_path'] = [];
        if (!empty($transaccion['categoria_id'])) {
            // Usamos una CTE recursiva para encontrar todos los padres hasta la raíz.
            $stmtPath = $this->pdo->prepare("
                WITH RECURSIVE CategoriaPath AS (
                    SELECT id, parent_id, 1 as nivel FROM categorias WHERE id = ?
                    UNION ALL
                    SELECT c.id, c.parent_id, cp.nivel + 1 FROM categorias c JOIN CategoriaPath cp ON c.id = cp.parent_id
                )
                SELECT id FROM CategoriaPath ORDER BY nivel DESC
            ");
            $stmtPath->execute([$transaccion['categoria_id']]);
            // Esto nos da un array de IDs, ej: [id_raiz, id_hijo, id_nieto]
            $transaccion['categoria_path'] = $stmtPath->fetchAll(PDO::FETCH_COLUMN);
        }

        return $transaccion;
    }

    public function getAllForExport($usuario_id, $startDate, $endDate, $categoryId, $searchText, $sortBy = 'fecha', $sortOrder = 'DESC', $tipo = null) {
        $col = $this->getNombreColumnaImporte();

        // --- Construcción dinámica de la cláusula WHERE y los parámetros ---
        // Esta lógica es idéntica a la de getPaginated
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

        if ($tipo === 'ingreso') {
            $whereClauses[] = "t.{$col} > 0";
        } elseif ($tipo === 'gasto') {
            $whereClauses[] = "t.{$col} < 0";
        }

        if ($categoryId === 'unclassified') {
            $whereClauses[] = "t.categoria_id IS NULL";
        } elseif ($categoryId) {
            $whereClauses[] = "t.categoria_id IN (
                WITH RECURSIVE subcategorias AS (
                    SELECT id FROM categorias WHERE id = ? AND usuario_id = ?
                    UNION ALL
                    SELECT c.id FROM categorias c JOIN subcategorias s ON c.parent_id = s.id WHERE c.usuario_id = ?
                ) SELECT id FROM subcategorias
            )";
            $params[] = $categoryId;
            $params[] = $usuario_id;
            $params[] = $usuario_id;
        } elseif ($searchText) {
            $whereClauses[] = '(t.descripcion LIKE ? OR c.nombre LIKE ?)';
            $params[] = '%' . $searchText . '%';
            $params[] = '%' . $searchText . '%';
        }

        $whereSql = " WHERE " . implode(' AND ', $whereClauses);

        // --- Construcción de la cláusula ORDER BY ---
        $sortableColumns = ['fecha', 'descripcion', 'importe', 'categoria_nombre'];
        $orderByColumn = 't.fecha';

        if (in_array($sortBy, $sortableColumns)) {
            if ($sortBy === 'importe') {
                $orderByColumn = "t.{$col}";
            } elseif ($sortBy === 'categoria_nombre') {
                $orderByColumn = "c.nombre"; // Ordenar por el nombre de la categoría en el JOIN
            } else {
                $orderByColumn = "t.{$sortBy}";
            }
        }

        $orderDirection = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';
        $orderBySql = " ORDER BY {$orderByColumn} {$orderDirection}, t.id DESC";

        // --- Consulta para obtener TODOS los datos (sin paginación) ---
        $sqlData = "SELECT t.fecha, t.descripcion, c.nombre as categoria_nombre, t.{$col} as importe
                    FROM transacciones t
                    LEFT JOIN categorias c ON t.categoria_id = c.id" . $whereSql . $orderBySql;

        $stmtData = $this->pdo->prepare($sqlData);
        $stmtData->execute($params);
        return $stmtData->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginated($usuario_id, $page, $limit, $startDate, $endDate, $categoryId, $searchText, $sortBy = 'fecha', $sortOrder = 'DESC', $tipo = null) {
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

        if ($tipo === 'ingreso') {
            $whereClauses[] = "t.{$col} > 0";
        } elseif ($tipo === 'gasto') {
            $whereClauses[] = "t.{$col} < 0";
        }

        // El filtro de categoría es prioritario sobre el de texto
        if ($categoryId === 'unclassified') {
            $whereClauses[] = "t.categoria_id IS NULL";
        } elseif ($categoryId) {
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

        // --- Consulta para obtener agregados (total y sumas) ---
        $sqlAggregates = "SELECT
                            COUNT(t.id) as total_count,
                            SUM(IF(t.{$col} > 0, t.{$col}, 0)) as total_ingresos,
                            SUM(IF(t.{$col} < 0 AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente'), t.{$col}, 0)) as total_gastos
                          FROM transacciones t
                          LEFT JOIN categorias c ON t.categoria_id = c.id" . $whereSql;

        $stmtAggregates = $this->pdo->prepare($sqlAggregates);
        $stmtAggregates->execute($params);
        $aggregates = $stmtAggregates->fetch(PDO::FETCH_ASSOC);

        // --- Construcción de la cláusula ORDER BY ---
        $sortableColumns = ['fecha', 'descripcion', 'importe', 'categoria_nombre']; // Nombres de columna seguros para ordenar
        $orderByColumn = 't.fecha'; // Columna por defecto

        if (in_array($sortBy, $sortableColumns)) {
            // Mapeamos a las columnas reales de la BD
            if ($sortBy === 'importe') {
                $orderByColumn = "t.{$col}";
            } elseif ($sortBy === 'categoria_nombre') {
                $orderByColumn = "c.nombre"; // Ordenar por el nombre de la categoría en el JOIN
            } else {
                $orderByColumn = "t.{$sortBy}";
            }
        }

        $orderDirection = 'DESC'; // Dirección por defecto
        if (strtoupper($sortOrder) === 'ASC' || strtoupper($sortOrder) === 'DESC') {
            $orderDirection = strtoupper($sortOrder);
        }

        // Un segundo orden para consistencia en caso de valores iguales
        $orderBySql = " ORDER BY {$orderByColumn} {$orderDirection}, t.id DESC";

        // --- Consulta para obtener los datos paginados ---
        $sqlData = "SELECT t.id, t.fecha, t.descripcion, t.{$col} as importe, t.categoria_id, c.nombre as categoria_nombre
                    FROM transacciones t
                    LEFT JOIN categorias c ON t.categoria_id = c.id" . $whereSql . "
                    " . $orderBySql . "
                    LIMIT ? OFFSET ?";

        $stmtData = $this->pdo->prepare($sqlData);

        // Añadimos los parámetros de paginación al final
        $dataParams = array_merge($params, [$limit, $offset]);
        $stmtData->execute($dataParams);
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => (int)($aggregates['total_count'] ?? 0),
            'totals' => [
                'ingresos' => (float)($aggregates['total_ingresos'] ?? 0),
                'gastos' => (float)($aggregates['total_gastos'] ?? 0) // Este valor es negativo
            ]
        ];
    }

    public function createBulk(int $usuario_id, array $transacciones): array
    {
        if (empty($transacciones)) {
            return ['inserted' => 0, 'skipped' => 0];
        }

        $this->pdo->beginTransaction();
        try {
            $col = $this->getNombreColumnaImporte();

            $sqlCheck = "SELECT id FROM transacciones WHERE usuario_id = ? AND fecha = ? AND descripcion = ? AND {$col} = ?";
            $stmtCheck = $this->pdo->prepare($sqlCheck);

            $sqlInsert = "INSERT INTO transacciones (usuario_id, categoria_id, fecha, descripcion, {$col}) VALUES (?, ?, ?, ?, ?)";
            $stmtInsert = $this->pdo->prepare($sqlInsert);

            $insertedCount = 0;
            $skippedCount = 0;

            foreach ($transacciones as $trx) {
                $stmtCheck->execute([$usuario_id, $trx['fecha'], $trx['descripcion'], $trx['importe']]);
                if ($stmtCheck->fetch()) {
                    // El movimiento ya existe, lo omitimos.
                    $skippedCount++;
                } else {
                    // El movimiento es nuevo, lo insertamos.
                    $stmtInsert->execute([$usuario_id, $trx['categoria_id'] ?? null, $trx['fecha'], $trx['descripcion'], $trx['importe']]);
                    $insertedCount++;
                }
            }

            $this->pdo->commit();
            return ['inserted' => $insertedCount, 'skipped' => $skippedCount];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error al guardar las transacciones en lote: " . $e->getMessage());
        }
    }

    public function autoClassify(array $ids, int $usuario_id, array $categorias): int
    {
        if (!empty($ids)) {
            // Si se envían IDs específicos, reevaluamos esos en concreto
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT id, descripcion FROM transacciones WHERE id IN ($placeholders) AND usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($ids, [$usuario_id]));
        } else {
            // Solo evalúa los que están "Por clasificar"
            $sql = "SELECT id, descripcion FROM transacciones WHERE categoria_id IS NULL AND usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
        }

        $transaccionesToClassify = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updatedCount = 0;

        $limpiarTexto = function($texto) {
            $texto = strtolower(trim($texto));
            $buscar  = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'];
            $reemplazar = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'];
            return str_replace($buscar, $reemplazar, $texto);
        };

        $stmtUpdate = $this->pdo->prepare("UPDATE transacciones SET categoria_id = ? WHERE id = ? AND usuario_id = ?");

        foreach ($transaccionesToClassify as $t) {
            $conceptoLimpio = $limpiarTexto($t['descripcion']);
            $categoria_final = null;

            foreach ($categorias as $cat) {
                $nombreCat = $cat['nombre'];
                $encontrado = false;

                // Buscar por reglas entre paréntesis ej: (mercadona, carrefour)
                if (preg_match('/\((.*?)\)/', $nombreCat, $coincidencias)) {
                    $palabrasClave = explode(',', $coincidencias[1]);
                    foreach ($palabrasClave as $palabra) {
                        $palabraLimpia = $limpiarTexto($palabra);
                        if (!empty($palabraLimpia)) {
                            $patron = '/(^|[^a-z0-9])' . preg_quote($palabraLimpia, '/') . '([^a-z0-9]|$)/i';
                            if (preg_match($patron, $conceptoLimpio)) { $encontrado = true; break; }
                        }
                    }
                }
                if (!$encontrado) { // Buscar por el nombre base ej: "Supermercado"
                    $nombreBaseLimpio = $limpiarTexto(trim(preg_replace('/\((.*?)\)/', '', $nombreCat)));
                    if (!empty($nombreBaseLimpio) && preg_match('/(^|[^a-z0-9])' . preg_quote($nombreBaseLimpio, '/') . '([^a-z0-9]|$)/i', $conceptoLimpio)) { $encontrado = true; }
                }
                if ($encontrado) { $categoria_final = $cat['id']; break; }
            }
            if ($categoria_final) { $stmtUpdate->execute([$categoria_final, $t['id'], $usuario_id]); $updatedCount++; }
        }

        return $updatedCount;
    }
}
?>
