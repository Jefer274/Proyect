
<?php
// Configurar zona horaria a Perú (UTC-5)
date_default_timezone_set('America/Lima');

$host = "localhost";
$user = "root";
$pass = "";       // si tienes contraseña, cámbiala
$db   = "Basededatos";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// También configurar la zona horaria en MySQL
$conn->query("SET time_zone = '-05:00'");

if (!function_exists('fechaHoraActual')) {
    function fechaHoraActual() {
        return date('d/m/Y H:i:s');
    }
}
