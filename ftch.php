<?php
// ftch.php — Listar transacciones y permitir eliminar/editar

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/db.php'; // Asegura la conexión PDO

if (!isset($_SESSION['usuario_id'])) {
    if (!defined('FROM_DASHBOARD')) {
        echo "<tr><td colspan='6'>Debe iniciar sesión.</td></tr>";
    }
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

try {
    // 🔹 Obtener las transacciones del usuario
    $stmt = $conn->prepare("
        SELECT t.id, t.fecha, t.descripcion, t.tipo, t.categoria, t.monto,
               c.nombre AS categoria_nombre,
               sc.nombre AS subcategoria_nombre,
               ssc.nombre AS subsubcategoria_nombre
        FROM transacciones t
        LEFT JOIN categorias c ON t.id_categoria = c.id
        LEFT JOIN subcategorias sc ON t.id_subcategoria = sc.id
        LEFT JOIN subsubcategorias ssc ON t.id_subsubcategoria = ssc.id
        WHERE t.id_usuario = :id_usuario
        ORDER BY t.fecha DESC, t.id DESC
    ");
    $stmt->execute(['id_usuario' => $id_usuario]);
    $transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Solo imprimir si estamos dentro del <tbody> (dashboard)
    if (defined('FROM_DASHBOARD')) {
        if (empty($transacciones)) {
            echo "<tr><td colspan='6' class='text-center text-muted'>No hay transacciones registradas.</td></tr>";
        } else {
            foreach ($transacciones as $t) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($t['fecha']) . "</td>";
                echo "<td>" . htmlspecialchars($t['categoria_nombre'] ?? $t['categoria']) . "</td>";
                echo "<td>" . htmlspecialchars($t['subcategoria_nombre'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($t['subsubcategoria_nombre'] ?? '') . "</td>";
                echo "<td class='text-end'>" . number_format($t['monto'], 2, ',', '.') . "</td>";
                echo "<td class='text-center'>
                        <button class='btn btn-sm btn-danger eliminar' data-id='" . $t['id'] . "' title='Eliminar'>
                            <i class='bi bi-trash'></i>
                        </button>
                      </td>";
                echo "</tr>";
            }
        }
    } else {
        // 🚀 Si se usa por AJAX, devolver JSON
        echo json_encode($transacciones);
    }

} catch (PDOException $e) {
    if (defined('FROM_DASHBOARD')) {
        echo "<tr><td colspan='6' class='text-danger'>Error al cargar transacciones.</td></tr>";
    } else {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
