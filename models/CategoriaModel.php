<?php
class CategoriaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? OR usuario_id IS NULL ORDER BY parent_id, orden, nombre");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (usuario_id, nombre, tipo_fijo, parent_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$usuario_id, $nombre, $tipo_fijo, $parent_id]);
    }

    public function update($id, $usuario_id, $nombre, $tipo_fijo, $parent_id = null) {
        // Solo permite actualizar si la categoría te pertenece
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = ?, tipo_fijo = ?, parent_id = ? WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$nombre, $tipo_fijo, $parent_id, $id, $usuario_id]);
    }

    public function delete($id, $usuario_id) {
        // 1. Ponemos en "Por clasificar" (NULL) los movimientos que usaran esta categoría
        $stmtUpdate = $this->pdo->prepare("UPDATE transacciones SET categoria_id = NULL WHERE categoria_id = ? AND usuario_id = ?");
        $stmtUpdate->execute([$id, $usuario_id]);

        // 2. Borramos la categoría (la condición 'usuario_id = ?' bloquea el borrado de las fijas)
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
        return $stmt->execute([$id, $usuario_id]);
    }

    public function updateOrder($movedId, $newParentId, $siblingIds, $userId) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE categorias SET parent_id = ? WHERE id = ? AND (usuario_id = ? OR usuario_id IS NULL)");
            $stmt->execute([$newParentId ?: null, $movedId, $userId]);

            foreach ($siblingIds as $index => $siblingId) {
                $stmt = $this->pdo->prepare("UPDATE categorias SET orden = ? WHERE id = ? AND (usuario_id = ? OR usuario_id IS NULL)");
                $stmt->execute([$index, $siblingId, $userId]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getTotalsRecursive($usuario_id, $startDate, $endDate) {
        $stmtCol = $this->pdo->query("SHOW COLUMNS FROM transacciones LIKE 'importe'");
        $col = ($stmtCol->rowCount() > 0) ? 'importe' : 'monto';

        $allCategories = $this->getAll($usuario_id);
        $childrenMap = [];
        foreach ($allCategories as $cat) {
            $childrenMap[$cat['parent_id'] ?: 0][] = $cat['id'];
        }

        $sql = "SELECT t.categoria_id, SUM(t.{$col}) as total_gastos FROM transacciones t LEFT JOIN categorias c ON t.categoria_id = c.id WHERE t.usuario_id = ? AND t.{$col} < 0 AND t.fecha >= ? AND t.fecha <= ? AND t.categoria_id IS NOT NULL AND COALESCE(c.tipo_fijo, 'gasto') NOT IN ('ahorro', 'puente') GROUP BY t.categoria_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id, $startDate, $endDate]);
        $rawTotals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $cumulativeTotals = [];
        $calculateTotals = function($categoryId) use (&$calculateTotals, &$cumulativeTotals, $childrenMap, $rawTotals) {
            if (isset($cumulativeTotals[$categoryId])) {
                return $cumulativeTotals[$categoryId];
            }
            $myTotal = (float)($rawTotals[$categoryId] ?? 0);
            if (isset($childrenMap[$categoryId])) {
                foreach ($childrenMap[$categoryId] as $childId) {
                    $myTotal += $calculateTotals($childId);
                }
            }
            $cumulativeTotals[$categoryId] = $myTotal;
            return $myTotal;
        };

        foreach ($allCategories as $cat) {
            if (!isset($cumulativeTotals[$cat['id']])) $calculateTotals($cat['id']);
        }
        return $cumulativeTotals;
    }
}
?>