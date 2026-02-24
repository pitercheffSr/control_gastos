<?php
class DashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getKpis($usuario_id, $fecha_inicio, $fecha_fin) {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN importe > 0 THEN importe ELSE 0 END), 0) as ingresos,
                COALESCE(ABS(SUM(CASE WHEN importe < 0 THEN importe ELSE 0 END)), 0) as gastos
            FROM transacciones 
            WHERE usuario_id = ? AND fecha >= ? AND fecha <= ?
        ");
        $stmt->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDistribucionGastos($usuario_id, $fecha_inicio, $fecha_fin) {
        // 1. Obtenemos TODAS las categorías del usuario para reconstruir el "Árbol Genealógico"
        $stmtCats = $this->db->prepare("SELECT id, parent_id, tipo_fijo FROM categorias WHERE usuario_id = ?");
        $stmtCats->execute([$usuario_id]);
        $categorias = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
        
        $diccionarioCats = [];
        foreach ($categorias as $c) {
            $diccionarioCats[$c['id']] = $c;
        }
        
        // 2. Función recursiva: Si una subcategoría no tiene etiqueta, le pregunta a su padre
        $resolverTipo = function($id) use (&$diccionarioCats, &$resolverTipo) {
            if (!isset($diccionarioCats[$id])) return 'otros';
            
            $tipoActual = strtolower(trim($diccionarioCats[$id]['tipo_fijo'] ?? ''));
            
            // Si la categoría tiene la etiqueta oficial, la devolvemos
            if (in_array($tipoActual, ['necesidad', 'deseo', 'ahorro'])) {
                return $tipoActual;
            }
            
            // Si no tiene etiqueta pero tiene un padre, subimos un nivel para heredar su sangre
            if (!empty($diccionarioCats[$id]['parent_id'])) {
                return $resolverTipo($diccionarioCats[$id]['parent_id']);
            }
            
            return 'otros';
        };

        // 3. Obtenemos todos los gastos (importe negativo) del periodo de fechas
        $stmtTrans = $this->db->prepare("
            SELECT categoria_id, importe 
            FROM transacciones 
            WHERE usuario_id = ? AND fecha >= ? AND fecha <= ? AND importe < 0
        ");
        $stmtTrans->execute([$usuario_id, $fecha_inicio, $fecha_fin]);
        $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

        // 4. Repartimos cada gasto sumándolo al tipo que heredó de su padre/abuelo
        $totales = ['necesidad' => 0, 'deseo' => 0, 'ahorro' => 0];
        
        foreach ($transacciones as $t) {
            $tipoHeredado = $resolverTipo($t['categoria_id']);
            if (isset($totales[$tipoHeredado])) {
                $totales[$tipoHeredado] += abs((float)$t['importe']);
            }
        }

        // 5. Formateamos la respuesta para que las gráficas la entiendan
        $resultado = [];
        foreach ($totales as $tipo => $total) {
            $resultado[] = ['tipo' => $tipo, 'total' => $total];
        }
        
        return $resultado;
    }
}
?>