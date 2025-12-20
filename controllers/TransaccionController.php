<?php

/**
 * ------------------------------------------------------------
 * TransaccionController.php
 * ------------------------------------------------------------
 * Controlador para gestión de transacciones.
 *
 * Corrige problemas del legacy:
 * - session_start consistente
 * - validación correcta de monto = 0
 * - normalización de IDs opcionales
 * - separación de responsabilidades
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/TransaccionModel.php';

class TransaccionController
{
    private PDO $pdo;
    private TransaccionModel $model;

    public function __construct(PDO $pdo)
    {
        $this->pdo   = $pdo;
        $this->model = new TransaccionModel($pdo);
    }

    /**
     * Crear transacción
     */
    public function crear(array $input): array
    {
        // Sesión obligatoria aquí (no en el router)
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'ok'    => false,
                'error' => 'No autenticado'
            ];
        }

        // Normalizar y validar datos
        $fecha       = $input['fecha'] ?? null;
        $descripcion = trim($input['descripcion'] ?? '');
        $tipo        = $input['tipo'] ?? null;

        // IMPORTANTE: monto puede ser 0, no usar !$monto
        if (!array_key_exists('monto', $input)) {
            return [
                'ok'    => false,
                'error' => 'Monto no informado'
            ];
        }

        $monto = (float) $input['monto'];

        if (!$fecha || !$tipo) {
            return [
                'ok'    => false,
                'error' => 'Datos incompletos'
            ];
        }

        // IDs opcionales → NULL real
        $idCategoria     = $this->normalizarId($input['categoria'] ?? null);
        $idSubcategoria  = $this->normalizarId($input['subcategoria'] ?? null);
        $idSubsub        = $this->normalizarId($input['subsub'] ?? null);

        // Delegar persistencia al modelo
        $this->model->crear([
            'id_usuario'        => (int) $_SESSION['usuario_id'],
            'fecha'             => $fecha,
            'descripcion'       => $descripcion,
            'monto'             => $monto,
            'tipo'              => $tipo,
            'id_categoria'      => $idCategoria,
            'id_subcategoria'   => $idSubcategoria,
            'id_subsubcategoria' => $idSubsub
        ]);

        return ['ok' => true];
    }
    /**
     * Listar transacciones del usuario
     */
    public function listar(): array
    {
        if (!isset($_SESSION['usuario_id'])) {
            return [];
        }

        return $this->model->listarPorUsuario((int) $_SESSION['usuario_id']);
    }

    /**
     * Eliminar transacción
     */
    public function eliminar(array $input): array
    {
        if (!isset($_SESSION['usuario_id'])) {
            return ['ok' => false, 'error' => 'No autenticado'];
        }

        if (empty($input['id'])) {
            return ['ok' => false, 'error' => 'ID no proporcionado'];
        }

        $ok = $this->model->eliminar(
            (int) $input['id'],
            (int) $_SESSION['usuario_id']
        );

        return ['ok' => $ok];
    }

    /**
     * Normaliza IDs opcionales:
     * - '', 0, null → null
     * - valores válidos → int
     */
    private function normalizarId($value): ?int
    {
        if ($value === null || $value === '' || (int)$value === 0) {
            return null;
        }

        return (int) $value;
    }
    /**
     * Obtener una transacción para edición
     */
    public function obtener(array $input): array
    {
        if (!isset($_SESSION['usuario_id'])) {
            return ['ok' => false, 'error' => 'No autenticado'];
        }

        if (empty($input['id'])) {
            return ['ok' => false, 'error' => 'ID no proporcionado'];
        }

        $tx = $this->model->obtenerPorId(
            (int) $input['id'],
            (int) $_SESSION['usuario_id']
        );

        if (!$tx) {
            return ['ok' => false, 'error' => 'Transacción no encontrada'];
        }

        return ['ok' => true, 'data' => $tx];
    }
/**
 * Editar transacción
 */
public function editar(array $input): array
{
    if (!isset($_SESSION['usuario_id'])) {
        return ['ok' => false, 'error' => 'No autenticado'];
    }

    if (empty($input['id'])) {
        return ['ok' => false, 'error' => 'ID no proporcionado'];
    }

    $ok = $this->model->editar(
        (int) $input['id'],
        (int) $_SESSION['usuario_id'],
        [
            'fecha'             => $input['fecha'],
            'descripcion'       => $input['descripcion'] ?? '',
            'monto'             => $input['monto'],
            'tipo'              => $input['tipo'],
            'id_categoria'      => $this->normalizarId($input['categoria'] ?? null),
            'id_subcategoria'   => $this->normalizarId($input['subcategoria'] ?? null),
            'id_subsubcategoria'=> $this->normalizarId($input['subsub'] ?? null),
        ]
    );

    return ['ok' => $ok];
}

}

