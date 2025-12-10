<?php

include_once "config.php";

// Vaciar variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Eliminar cookie PHPSESSID
setcookie("PHPSESSID", "", time() - 3600, "/control_gastos");

// Redirigir al login oficial
header("Location: login.php");
exit;
