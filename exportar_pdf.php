<?php
// Limpiar cualquier salida previa
ob_start();
ob_clean();

// Verificar autenticación
session_start();
if (!isset($_SESSION['usuario'])) {
    ob_end_clean();
    die("Acceso denegado");
}

include "conexion.php";

$modulo = $_GET['modulo'] ?? '';
$sql = '';
$titulo = '';
$campos = [];

// Definir consultas según el módulo
switch($modulo) {
    case 'productos':
        $sql = "SELECT Id, CodigoBase, Descripcion, Marca, Modelo, Serie, Color, Estado, FechaIngreso FROM TallerComunicacionDatos ORDER BY Id ASC";
        $titulo = "Taller de Comunicación de Datos";
        $campos = ['ID', 'Código', 'Descripción', 'Marca', 'Modelo', 'Serie', 'Color', 'Estado', 'Fecha'];
        break;

    case 'almacencomunicacion':
        $sql = "SELECT Id, General, Detalle, Marca, Modelo, Serie, Otros, Color, Estado, FechaIngreso FROM AlmacenComunicacion ORDER BY Id ASC";
        $titulo = "Almacén de Comunicación y Datos";
        $campos = ['ID', 'General', 'Detalle', 'Marca', 'Modelo', 'Serie', 'Otros', 'Color', 'Estado', 'Fecha'];
        break;

    case 'tallersoftware':
        $sql = "SELECT Id, general, correl, detalle, marca, tipo, modulo, serie, color, procesador, otros, fecha_ingreso FROM tallersoftware ORDER BY Id ASC";
        $titulo = "Taller de Software y Sistemas";
        $campos = ['ID', 'General', 'Correl', 'Detalle', 'Marca', 'Tipo', 'Módulo', 'Serie', 'Color', 'Procesador', 'Otros', 'Fecha'];
        break;

    case 'almacensoftware':
        $sql = "SELECT id, general, detalle, marca, modelo, serie, dimensiones, otros, fechaingreso FROM almacensoftware ORDER BY id ASC";
        $titulo = "Almacén de Software y Sistemas";
        $campos = ['ID', 'General', 'Detalle', 'Marca', 'Modelo', 'Serie', 'Dimensiones', 'Otros', 'Fecha'];
        break;

    case 'almacenredes':
        $sql = "SELECT Id, general, detalle, marca, modelo, serie, dimension, otros, estado, fechaingreso FROM almacenredes ORDER BY Id ASC";
        $titulo = "Almacén de Redes y Soporte";
        $campos = ['ID', 'General', 'Detalle', 'Marca', 'Modelo', 'Serie', 'Dimensión', 'Otros', 'Estado', 'Fecha'];
        break;

    case 'tallerredes':
        $sql = "SELECT Id, general, correl, detalle, marca, tipo, modelo, serie, otros FROM tallerredes ORDER BY Id ASC";
        $titulo = "Taller de Redes y Soporte";
        $campos = ['ID', 'General', 'Correl', 'Detalle', 'Marca', 'Tipo', 'Modelo', 'Serie', 'Otros'];
        break;

    default:
        ob_end_clean();
        die("Módulo no válido");
}

$result = $conn->query($sql);

if (!$result) {
    ob_end_clean();
    die("Error en consulta: " . $conn->error);
}

// Limpiar buffer antes de generar PDF
ob_end_clean();

// Intentar usar TCPDF si está disponible
if (file_exists('tcpdf/tcpdf.php')) {
    require_once('tcpdf/tcpdf.php');

    // Crear nueva instancia de TCPDF
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar información del documento
    $pdf->SetCreator('Sistema de Inventario');
    $pdf->SetAuthor($_SESSION['usuario']);
    $pdf->SetTitle($titulo);
    $pdf->SetSubject('Reporte de Inventario');

    // Configurar márgenes
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);

    // Remover header y footer por defecto
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Agregar página
    $pdf->AddPage();

    // Título principal
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(3);

    // Información del reporte
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Exportado por: ' . $_SESSION['usuario'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Fecha: ' . fechaHoraActual(), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Total registros: ' . $result->num_rows, 0, 1, 'C');
    $pdf->Ln(8);

    // Calcular ancho de columnas
    $pageWidth = $pdf->getPageWidth() - 30; // Restar márgenes
    $colWidth = $pageWidth / count($campos);

    // Headers de tabla
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);

    foreach($campos as $campo) {
        $pdf->Cell($colWidth, 8, $campo, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Datos de la tabla
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $fill = false;
    while($row = $result->fetch_assoc()) {
        if ($fill) {
            $pdf->SetFillColor(248, 248, 248);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        foreach($row as $value) {
            $texto = substr($value ?? '', 0, 25); // Limitar texto
            $pdf->Cell($colWidth, 6, $texto, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $fill = !$fill;
    }

    // Generar archivo
    $filename = str_replace(' ', '_', $titulo) . '_' . date('Y-m-d_H-i') . '.pdf';
    $pdf->Output($filename, 'D');

} else {
    // Método alternativo HTML - solo si TCPDF no está disponible
    $filename = str_replace(' ', '_', $titulo) . '_' . date('Y-m-d') . '.pdf';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');

    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $titulo . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $titulo . '</h1>
        <p>Exportado por: ' . $_SESSION['usuario'] . ' | Fecha: ' . fechaHoraActual() . '</p>
    </div>
    <table>
        <thead><tr>';

    foreach($campos as $campo) {
        echo '<th>' . $campo . '</th>';
    }
    echo '</tr></thead><tbody>';

    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        foreach($row as $value) {
            echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>
</body>
</html>';
}

// Cerrar conexión
$conn->close();
?>