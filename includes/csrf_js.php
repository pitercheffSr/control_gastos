<?php
// Inyecta el token CSRF en JS (sin HTML)
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['csrf_token'])) {
	echo '<script>window.csrf_token = "' . $_SESSION['csrf_token'] . '";</script>';
}
