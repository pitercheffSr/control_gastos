<?php

// api/auth/logout.php
session_start();
$_SESSION = [];
setcookie(session_name(), '', time() - 3600, '/control_gastos');
session_destroy();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
