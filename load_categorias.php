<?php
header('Content-Type: application/json');
$pdo=new PDO('mysql:host=localhost;dbname=control','root','');
$q=$pdo->query("SELECT id,nombre FROM categorias ORDER BY nombre");
echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
