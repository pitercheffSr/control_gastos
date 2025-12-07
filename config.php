<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/control_gastos',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
?>
