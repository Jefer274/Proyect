<?php
session_start();
include "conexion.php";

$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : '';

$tabla = '';
$campos = array();
$nombreArchivo = '';

switch($modulo) {
    case 'productos':
        $tabla = 'TallerComunicacionDatos';
        $campos = ['Id', 'CodigoBase', 'Descripcion', 'Marca', 'Modelo', 'Serie', 'Color', 'Estado', 'FechaIngreso'];
        $nombreArchivo = 'TallerComunicacion';
        break;

    case 'almacencomunicacion':
        $tabla = 'AlmacenComunicacion';
        $campos = ['Id', 'General', 'Detalle', 'Marca', 'Modelo', 'Serie', 'Otros', 'Color', 'Estado', 'FechaIngreso'];
        $nombreArchivo = 'AlmacenComunicacion';
        break;

    case 'tallersoftware':
        $tabla = 'tallersoftware';
        $campos = ['Id', 'general', 'correl', 'detalle', 'marca', 'tipo', 'modulo', 'serie', 'color', 'procesador', 'otros', 'fecha_ingreso'];
        $nombreArchivo = 'TallerSoftware';
        break;

    case 'almacensoftware':
        $tabla = 'almacensoftware';
        $campos = ['id', 'general', 'detalle', 'marca', 'modelo', 'serie', 'dimensiones', 'otros', 'fechaingreso'];
        $nombreArchivo = 'AlmacenSoftware';
        break;

    case 'almacenredes':
        $tabla = 'almacenredes';
        $campos = ['Id', 'general', 'detalle', 'marca', 'modelo', 'serie', 'dimension', 'otros', 'estado', 'fechaingreso'];
        $nombreArchivo = 'AlmacenRedes';
        break;

    case 'tallerredes':
        $tabla = 'tallerredes';
        $campos = ['Id', 'general', 'correl', 'detalle', 'marca', 'tipo', 'modelo', 'serie', 'otros'];
        $nombreArchivo = 'TallerRedes';
        break;

    default:
        die('Módulo no válido');
}

$sql = "SELECT " . implode(', ', $campos) . " FROM $tabla ORDER BY " . $campos[0] . " ASC";
$result = $conn->query($sql);

if (!$result) {
    die('Error en consulta: ' . $conn->error);
}

// Headers para descarga CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="' . $nombreArchivo . '_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');

// BOM para UTF-8 (para que Excel reconozca tildes)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Escribir encabezados
fputcsv($output, $campos);

// Escribir datos
while ($row = $result->fetch_assoc()) {
    $datos = array();
    foreach ($campos as $campo) {
        $datos[] = isset($row[$campo]) ? $row[$campo] : '';
    }
    fputcsv($output, $datos);
}

fclose($output);
$conn->close();
exit;
?>