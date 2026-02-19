<?php
class CategoriaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Obtiene las categorías ordenadas jerárquicamente para el desplegable
     */
    public function getAll($usuario_id) {
        try {
            // 1. Obtenemos TODAS las categorías (incluyendo a quién pertenecen)
            $sql = "SELECT id, nombre, parent_id, tipo_fijo as tipo 
                    FROM categorias 
                    WHERE usuario_id = ? 
                    ORDER BY nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id]);
            $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Las organizamos en forma de Árbol
            $arbol = $this->construirArbol($todas, null);

            // 3. Las aplanamos en una lista ordenada con sangría visual
            $listaOrdenada = [];
            $this->aplanarArbol($arbol, $listaOrdenada, 0);

            return $listaOrdenada;
            
        } catch (PDOException $e) {
            error_log("Error cargando categorías: " . $e->getMessage());
            return [];
        }
    }

    // --- FUNCIONES AUXILIARES PARA CREAR EL EFECTO DESPLEGABLE ---

    /**
     * Agrupa las subcategorías dentro de sus padres correspondientes
     */
    private function construirArbol(array &$elementos, $parentId = null) {
        $rama = [];
        foreach ($elementos as $elemento) {
            if ($elemento['parent_id'] == $parentId) {
                $hijos = $this->construirArbol($elementos, $elemento['id']);
                if ($hijos) {
                    $elemento['subcategorias'] = $hijos;
                }
                $rama[] = $elemento;
            }
        }
        return $rama;
    }

    /**
     * Convierte el árbol en una lista simple añadiendo guiones (—) a las hijas
     */
    private function aplanarArbol($arbol, &$resultado, $nivel = 0) {
        foreach ($arbol as $nodo) {
            
            // Generamos la "sangría" visual. 
            // Nivel 0: "", Nivel 1: "— ", Nivel 2: "— — "
            $prefijo = '';
            if ($nivel > 0) {
                $prefijo = str_repeat('— ', $nivel) . ' ';
            }

            // Añadimos la categoría a la lista final que leerá el formulario
            $resultado[] = [
                'id' => $nodo['id'],
                'nombre' => $prefijo . $nodo['nombre'],
                'tipo' => $nodo['tipo']
            ];

            // Si tiene hijas, repetimos el proceso aumentando el nivel
            if (isset($nodo['subcategorias'])) {
                $this->aplanarArbol($nodo['subcategorias'], $resultado, $nivel + 1);
            }
        }
    }
}