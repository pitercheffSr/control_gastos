<?php

session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/control_gastos',
	'httponly' => true,
	'samesite' => 'Lax'
]);

session_start();

/* ==============================
   CSRF TOKEN
   ============================== */
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
