<?php
class DashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Rastrea el árbol genealógico de las categorías para saber 
     * si un movimiento es Ingreso, Necesidad, Deseo o Ahorro.
     */
    private function getTiposJerarquia($usuario_id) {
        try {
            $stmt = $this->db->prepare("SELECT id, parent_id, tipo_fijo FROM categorias WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $diccionario = [];
            foreach ($cats as $c) { $diccionario[$c['id']] = $c; }

            $tipos = [];
            foreach ($cats as $c) {
                $idActual = $c['id'];
                $limite = 0;
                while (!empty($diccionario[$idActual]['parent_id']) && $limite < 10) {
                    $idActual = $diccionario[$idActual]['parent_id'];
                    $limite++;
                }
                $tipoRaiz = !empty($diccionario[$idActual]['tipo_fijo']) ? $diccionario[$idActual]['tipo_fijo'] : 'personalizado';
                $tipos[$c['id']] = strtolower(trim($tipoRaiz));
            }
            return $tipos;
        } catch (Exception $e) { return []; }
    }

    /**
     * Obtiene los totales de ingresos y gastos del mes
     */
    public function getKpis($usuario_id, $mes) {
        try {
            $tipos = $this->getTiposJerarquia($usuario_id);
            $mesParam = trim($mes) . '%'; // Ej: "2026-02%"

            // ¡AQUÍ ESTÁ LA MAGIA! Pedimos la columna "importe" (tu nombre real en la BD)
            $stmt = $this->db->prepare("SELECT categoria_id, importe FROM transacciones WHERE usuario_id = ? AND fecha LIKE ?");
            $stmt->execute([$usuario_id, $mesParam]);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ingresos = 0.0;
            $gastos = 0.0;

            foreach ($movimientos as $m) {
                $catId = $m['categoria_id'];
                $tipo = isset($tipos[$catId]) ? $tipos[$catId] : 'personalizado';
                
                // Leemos el 'importe' y lo pasamos a positivo absoluto
                $valor = abs((float)$m['importe']); 

                if ($tipo === 'ingreso') {
                    $ingresos += $valor;
                } else {
                    $gastos += $valor;
                }
            }

            return ['ingresos' => $ingresos, 'gastos' => $gastos];
            
        } catch (Exception $e) {
            error_log("Error fatal en getKpis: " . $e->getMessage());
            return ['ingresos' => 0, 'gastos' => 0];
        }
    }

    /**
     * Obtiene cuánto se ha gastado en cada caja fuerte (50/30/20)
     */
    public function getDistribucionGastos($usuario_id, $mes) {
        try {
            $tipos = $this->getTiposJerarquia($usuario_id);
            $mesParam = trim($mes) . '%';

            // Pedimos 'importe' de nuevo
            $stmt = $this->db->prepare("SELECT categoria_id, importe FROM transacciones WHERE usuario_id = ? AND fecha LIKE ?");
            $stmt->execute([$usuario_id, $mesParam]);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dist = [];

            foreach ($movimientos as $m) {
                $catId = $m['categoria_id'];
                $tipo = isset($tipos[$catId]) ? $tipos[$catId] : 'personalizado';
                $valor = abs((float)$m['importe']);

                // Si no es un ingreso, lo sumamos a su caja (necesidad, deseo o ahorro)
                if ($tipo !== 'ingreso') {
                    if (!isset($dist[$tipo])) $dist[$tipo] = 0.0;
                    $dist[$tipo] += $valor;
                }
            }

            $resultado = [];
            foreach ($dist as $t => $total) {
                $resultado[] = ['tipo' => $t, 'total' => $total];
            }
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error fatal en getDistribucion: " . $e->getMessage());
            return [];
        }
    }
}