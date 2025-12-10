<?php

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$id = intval($_SESSION["usuario_id"]);

try {
    // ================================
    //   MODO DASHBOARD (resumen simple)
    // ================================
    if (isset($_GET["dashboard"])) {
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.nombre AS categoria,
                SUM(t.monto) AS total
            FROM transacciones t
            INNER JOIN categorias c ON t.id_categoria = c.id
            WHERE t.id_usuario = :id
            GROUP BY c.id, c.nombre
            ORDER BY total DESC
        ");

        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ================================
    //   MODO GRAFICAS (gastos / ingresos)
    // ================================

    // GASTOS
    $stmt_g = $conn->prepare("
        SELECT 
            c.nombre AS categoria,
            SUM(t.monto) AS total
        FROM transacciones t
        INNER JOIN categorias c ON t.id_categoria = c.id
        WHERE t.id_usuario = :id AND c.tipo = 'gasto'
        GROUP BY c.id
        ORDER BY total DESC
    ");
    $stmt_g->execute([':id' => $id]);
    $gastos = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

    // INGRESOS
    $stmt_i = $conn->prepare("
        SELECT 
            c.nombre AS categoria,
            SUM(t.monto) AS total
        FROM transacciones t
        INNER JOIN categorias c ON t.id_categoria = c.id
        WHERE t.id_usuario = :id AND c.tipo = 'ingreso'
        GROUP BY c.id
        ORDER BY total DESC
    ");
    $stmt_i->execute([':id' => $id]);
    $ingresos = $stmt_i->fetchAll(PDO::FETCH_ASSOC);

    // devolver formato final
    echo json_encode([
        "gastos" => $gastos,
        "ingresos" => $ingresos
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage(),
        "gastos" => [],
        "ingresos" => []
    ]);
}
