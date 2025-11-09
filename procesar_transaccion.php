<?php
// procesar_transaccion.php
session_start();
include 'db.php'; // Debe definir $conn (PDO)
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

// Helper: respuesta JSON
function respond($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Comprobar método
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    respond('error', 'Solicitud inválida.');
}

// Recibir acción
$action = $_POST['action'] ?? '';

try {
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) respond('error', 'ID de transacción no válido.');

        $stmt = $conn->prepare("DELETE FROM transacciones WHERE id = :id AND id_usuario = :id_usuario");
        $stmt->execute(['id' => $id, 'id_usuario' => $id_usuario]);

        respond('success', 'Transacción eliminada.', ['id' => $id]);

    } else {
        // Campos recibidos
        $descripcion = trim($_POST['descripcion'] ?? '');
        $monto = filter_var($_POST['monto'] ?? 0, FILTER_VALIDATE_FLOAT);
        $fecha = $_POST['fecha'] ?? '';
        $tipo = in_array($_POST['tipo'] ?? '', ['ingreso','gasto']) ? $_POST['tipo'] : 'gasto';
        $id_categoria = intval($_POST['id_categoria'] ?? 0) ?: null;
        $id_subcategoria = intval($_POST['subcategoria'] ?? 0) ?: null;
        $id_subsubcategoria = intval($_POST['subsubcategoria'] ?? 0) ?: null;
        $ingreso_origen = trim($_POST['ingreso_origen'] ?? '');

        // Validaciones
        if ($descripcion === '') respond('error', 'La descripción es obligatoria.');
        if ($monto === false || $monto <= 0) respond('error', 'El monto debe ser positivo.');
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!($d && $d->format('Y-m-d') === $fecha)) respond('error', 'Formato de fecha incorrecto.');

        // Obtener clasificación/nombre categoría
        $categoria_param = 'Sin categoría';
        if ($tipo === 'ingreso') {
            $categoria_param = $ingreso_origen !== '' ? $ingreso_origen : 'Ingreso';
            $id_categoria = null;
        } elseif ($id_categoria) {
            $stmtc = $conn->prepare("SELECT nombre, clasificacion FROM categorias WHERE id = :id LIMIT 1");
            $stmtc->execute(['id' => $id_categoria]);
            $row = $stmtc->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $categoria_param = $row['clasificacion'] ?: $row['nombre'];
            }
        }

        $edit_id = intval($_POST['id'] ?? 0);

        if ($edit_id > 0) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE transacciones SET
                    descripcion = :descripcion,
                    monto = :monto,
                    fecha = :fecha,
                    tipo = :tipo,
                    categoria = :categoria,
                    id_categoria = :id_categoria,
                    id_subcategoria = :id_subcategoria,
                    id_subsubcategoria = :id_subsubcategoria
                WHERE id = :id AND id_usuario = :id_usuario
            ");
            $stmt->execute([
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fecha' => $fecha,
                'tipo' => $tipo,
                'categoria' => $categoria_param,
                'id_categoria' => $id_categoria,
                'id_subcategoria' => $id_subcategoria,
                'id_subsubcategoria' => $id_subsubcategoria,
                'id' => $edit_id,
                'id_usuario' => $id_usuario
            ]);

            // Devolver la fila actualizada
            $stmt = $conn->prepare("SELECT * FROM transacciones WHERE id = :id AND id_usuario = :id_usuario");
            $stmt->execute(['id' => $edit_id, 'id_usuario' => $id_usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            respond('success', 'Transacción actualizada.', $row);

        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO transacciones
                (descripcion, monto, fecha, tipo, categoria, id_usuario, id_categoria, id_subcategoria, id_subsubcategoria)
                VALUES
                (:descripcion, :monto, :fecha, :tipo, :categoria, :id_usuario, :id_categoria, :id_subcategoria, :id_subsubcategoria)
            ");
            $stmt->execute([
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fecha' => $fecha,
                'tipo' => $tipo,
                'categoria' => $categoria_param,
                'id_usuario' => $id_usuario,
                'id_categoria' => $id_categoria,
                'id_subcategoria' => $id_subcategoria,
                'id_subsubcategoria' => $id_subsubcategoria
            ]);

            $new_id = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM transacciones WHERE id = :id AND id_usuario = :id_usuario");
            $stmt->execute(['id' => $new_id, 'id_usuario' => $id_usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            respond('success', 'Transacción registrada.', $row);
        }

    }

} catch (PDOException $e) {
    respond('error', 'Error en base de datos: ' . $e->getMessage());
}
?>
