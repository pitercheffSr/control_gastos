<?php
require_once 'db.php';
if (!isset($conn)) {
    echo "<tr><td colspan='2'>Error DB</td></tr>";
    exit;
}

// Para que desde dashboard.php se limiten columnas
$soloDashboard = defined('FROM_DASHBOARD');

try {
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.fecha,
            t.monto,
            t.tipo,

            c1.nombre AS nivel1,
            c2.nombre AS nivel2,
            c3.nombre AS nivel3,
            c4.nombre AS nivel4

        FROM transacciones t
        LEFT JOIN categorias c1 ON t.id_categoria = c1.id
        LEFT JOIN categorias c2 ON t.subcategoria = c2.id
        LEFT JOIN categorias c3 ON t.subsubcategoria = c3.id
        LEFT JOIN categorias c4 ON t.concepto = c4.id
        WHERE t.id_usuario = :id
        ORDER BY t.fecha DESC, t.id DESC
        LIMIT 200
    ");

    $stmt->execute([
        ":id" => $_SESSION["usuario_id"]
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "<tr><td colspan='2' class='text-center text-muted py-3'>Sin movimientos</td></tr>";
        exit;
    }

    foreach ($rows as $r) {

        if ($soloDashboard) {

            // COLUMNA RESUMIDA PARA EL DASHBOARD
            $texto = $r['nivel1'];

            if ($r['nivel2']) $texto .= " → " . $r['nivel2'];
            if ($r['nivel3']) $texto .= " → " . $r['nivel3'];
            if ($r['nivel4']) $texto .= " → " . $r['nivel4'];

            echo "
            <tr>
                <td>".htmlspecialchars($r['fecha'])."<br>
                    <small class='text-muted'>".htmlspecialchars($texto)."</small>
                </td>
                <td class='text-end'>
                    ".number_format($r['monto'], 2, ',', '.')."
                </td>
            </tr>";
        }

        else {

            // MODO TABLA COMPLETA (si abres ftch.php directamente)
            echo "
            <tr>
                <td>{$r['fecha']}</td>
                <td>{$r['nivel1']}</td>
                <td>{$r['nivel2']}</td>
                <td>{$r['nivel3']}</td>
                <td>{$r['nivel4']}</td>
                <td>{$r['monto']}</td>
            </tr>";
        }
    }

} catch (Exception $e) {
    echo "<tr><td colspan='2'>Error: ".$e->getMessage()."</td></tr>";
}
