<?php

require_once "config.php";
require_once "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Unauthenticated"]);
    exit;
}

$sql = "
SELECT
    t.id,
    t.date,
    t.description,
    t.amount,
    t.type,
    c1.name AS category,
    c2.name AS subcategory,
    c3.name AS subsubcategory,
    t.category_id,
    t.subcategory_id,
    t.subsubcategory_id
FROM transactions t
LEFT JOIN categories c1 ON c1.id = t.category_id
LEFT JOIN categories c2 ON c2.id = t.subcategory_id
LEFT JOIN categories c3 ON c3.id = t.subsubcategory_id
WHERE t.user_id = :uid
ORDER BY t.date DESC, t.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute(["uid" => $_SESSION['user_id']]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure numeric fields are typed correctly for JSON parsers
foreach ($transactions as &$t) {
    $t['amount'] = (float)$t['amount'];
    $t['id'] = (int)$t['id'];
    $t['category_id'] = $t['category_id'] ? (int)$t['category_id'] : null;
    $t['subcategory_id'] = $t['subcategory_id'] ? (int)$t['subcategory_id'] : null;
    $t['subsubcategory_id'] = $t['subsubcategory_id'] ? (int)$t['subsubcategory_id'] : null;
}

echo json_encode($transactions);
