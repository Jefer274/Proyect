<?php
// Verificar autenticación
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] != 'admin') {
    die("Solo administradores pueden importar archivos");
}

include "conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
    die("No se recibió archivo");
}

$archivo = $_FILES['archivo'];
$modulo = $_POST['tabla'] ?? $_POST['modulo'] ?? ''; // Aceptar 'tabla' o 'modulo'

// Validar archivo
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    die("Error al subir archivo: " . $archivo['error']);
}

$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['xlsx', 'xlsm', 'csv'])) {
    die("Solo se permiten archivos .xlsx, .xlsm o .csv");
}

// Crear directorio temporal
$tempDir = 'temp/';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$rutaArchivo = $tempDir . uniqid() . '.' . $extension;
if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
    die("Error al guardar archivo");
}

try {
    $filas = [];

    if ($extension === 'csv') {
        // Leer CSV
        if (($handle = fopen($rutaArchivo, "r")) !== FALSE) {
            $primeraFila = true;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($primeraFila) {
                    $primeraFila = false;
                    continue; // Saltar headers
                }
                if (!empty(array_filter($data))) {
                    $filas[] = $data;
                }
            }
            fclose($handle);
        }
    } else {
        // Intentar leer Excel con PhpSpreadsheet
        if (file_exists('phpspreadsheet/src/Bootstrap.php')) {
            require_once 'phpspreadsheet/src/Bootstrap.php';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($rutaArchivo);

            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 2; $row <= $highestRow; $row++) { // Empezar en fila 2 (saltar headers)
                $fila = [];
                $filaVacia = true;

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $valor = $worksheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                    $fila[] = trim($valor);
                    if (!empty(trim($valor))) {
                        $filaVacia = false;
                    }
                }

                if (!$filaVacia) {
                    $filas[] = $fila;
                }
            }
        } else {
            // Si no hay PhpSpreadsheet, convertir usando método alternativo
            die("PhpSpreadsheet no está instalado. Por favor:\n\n1. Descarga PhpSpreadsheet desde GitHub\n2. Extrae en carpeta 'phpspreadsheet/'\n3. O convierte tu archivo Excel a CSV y vuelve a intentar");
        }
    }

    if (empty($filas)) {
        throw new Exception("No se encontraron datos válidos en el archivo");
    }

    $insertados = 0;
    $errores = 0;
    $erroresDetalle = [];

    // Función auxiliar para convertir fecha a formato d/m/Y
    function formatearFecha($fechaInput) {
        // Si está vacío, devolver cadena vacía
        if (empty($fechaInput) || trim($fechaInput) === '') {
            return '';
        }

        // Si viene en formato Y-m-d (Excel/CSV)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInput)) {
            $partes = explode('-', $fechaInput);
            return $partes[2] . '/' . $partes[1] . '/' . $partes[0]; // d/m/Y
        }

        // Si ya viene en formato d/m/Y
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaInput)) {
            return $fechaInput;
        }

        // Si no cumple ningún formato válido, devolver vacío
        return '';
    }

    // Procesar cada fila según el módulo
    foreach ($filas as $indice => $fila) {
        try {
            $numeroFila = $indice + 2; // +2 porque empezamos en fila 2 del Excel

            switch($modulo) {
                case 'productos':
                case 'TallerComunicacionDatos':
                    if (count($fila) < 8) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 8: CodigoBase, Descripcion, Marca, Modelo, Serie, Color, Estado, FechaIngreso)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO TallerComunicacionDatos (CodigoBase, Descripcion, Marca, Modelo, Serie, Color, Estado, FechaIngreso)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    $fecha = formatearFecha($fila[7]);

                    $stmt->bind_param("ssssssss",
                        $fila[0], // CodigoBase
                        $fila[1], // Descripcion
                        $fila[2], // Marca
                        $fila[3], // Modelo
                        $fila[4], // Serie
                        $fila[5], // Color
                        $fila[6], // Estado
                        $fecha    // FechaIngreso
                    );
                    break;

                case 'almacencomunicacion':
                case 'AlmacenComunicacion':
                    if (count($fila) < 9) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 9: General, Detalle, Marca, Modelo, Serie, Otros, Color, Estado, FechaIngreso)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO AlmacenComunicacion (General, Detalle, Marca, Modelo, Serie, Otros, Color, Estado, FechaIngreso)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    $fecha = formatearFecha($fila[8]);

                    $stmt->bind_param("sssssssss",
                        $fila[0], $fila[1], $fila[2], $fila[3],
                        $fila[4], $fila[5], $fila[6], $fila[7], $fecha
                    );
                    break;

                case 'tallersoftware':
                    if (count($fila) < 11) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 11: general, correl, detalle, marca, tipo, modulo, serie, color, procesador, otros, fecha_ingreso)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO tallersoftware (general, correl, detalle, marca, tipo, modulo, serie, color, procesador, otros, fecha_ingreso)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    $fecha = formatearFecha($fila[10]);

                    $stmt->bind_param("sssssssssss",
                        $fila[0],  // general
                        $fila[1],  // correl
                        $fila[2],  // detalle
                        $fila[3],  // marca
                        $fila[4],  // tipo
                        $fila[5],  // modulo
                        $fila[6],  // serie
                        $fila[7],  // color
                        $fila[8],  // procesador
                        $fila[9],  // otros
                        $fecha     // fecha_ingreso
                    );
                    break;

                case 'almacensoftware':
                    if (count($fila) < 8) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 8: general, detalle, marca, modelo, serie, dimensiones, otros, fechaingreso)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO almacensoftware (general, detalle, marca, modelo, serie, dimensiones, otros, fechaingreso)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    $fecha = formatearFecha($fila[7]);

                    $stmt->bind_param("ssssssss",
                        $fila[0], // general
                        $fila[1], // detalle
                        $fila[2], // marca
                        $fila[3], // modelo
                        $fila[4], // serie
                        $fila[5], // dimensiones
                        $fila[6], // otros
                        $fecha    // fechaingreso
                    );
                    break;

                case 'almacenredes':
                    if (count($fila) < 9) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 9: general, detalle, marca, modelo, serie, dimension, otros, estado, fechaingreso)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO almacenredes (general, detalle, marca, modelo, serie, dimension, otros, estado, fechaingreso)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    $fecha = formatearFecha($fila[8]);

                    $stmt->bind_param("sssssssss",
                        $fila[0], // general
                        $fila[1], // detalle
                        $fila[2], // marca
                        $fila[3], // modelo
                        $fila[4], // serie
                        $fila[5], // dimension
                        $fila[6], // otros
                        $fila[7], // estado
                        $fecha    // fechaingreso
                    );
                    break;

                case 'tallerredes':
                    if (count($fila) < 8) {
                        $erroresDetalle[] = "Fila $numeroFila: Faltan columnas (se esperan 8: general, correl, detalle, marca, tipo, modelo, serie, otros)";
                        $errores++;
                        continue 2;
                    }

                    $sql = "INSERT INTO tallerredes (general, correl, detalle, marca, tipo, modelo, serie, otros)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    // NOTA: tallerredes NO tiene campo de fecha
                    $stmt->bind_param("ssssssss",
                        $fila[0], // general
                        $fila[1], // correl
                        $fila[2], // detalle
                        $fila[3], // marca
                        $fila[4], // tipo
                        $fila[5], // modelo
                        $fila[6], // serie
                        $fila[7]  // otros
                    );
                    break;

                default:
                    throw new Exception("Módulo '$modulo' no soportado para importación");
            }

            if ($stmt->execute()) {
                $insertados++;
            } else {
                $erroresDetalle[] = "Fila $numeroFila: " . $stmt->error;
                $errores++;
            }

        } catch (Exception $e) {
            $erroresDetalle[] = "Fila $numeroFila: " . $e->getMessage();
            $errores++;
        }
    }

    // Limpiar archivo temporal
    unlink($rutaArchivo);

    // Respuesta detallada
    $resultado = "=== RESULTADO DE IMPORTACIÓN ===\n\n";
    $resultado .= "Archivo: " . $archivo['name'] . "\n";
    $resultado .= "Módulo: " . ucfirst($modulo) . "\n";
    $resultado .= "Registros insertados: $insertados\n";
    $resultado .= "Errores: $errores\n";
    $resultado .= "Fecha: " . date('d/m/Y H:i:s') . "\n\n";

    if (!empty($erroresDetalle)) {
        $resultado .= "=== DETALLE DE ERRORES ===\n";
        $resultado .= implode("\n", array_slice($erroresDetalle, 0, 10)); // Mostrar máximo 10 errores
        if (count($erroresDetalle) > 10) {
            $resultado .= "\n... y " . (count($erroresDetalle) - 10) . " errores más";
        }
    }

    echo $resultado;

} catch (Exception $e) {
    if (file_exists($rutaArchivo)) {
        unlink($rutaArchivo);
    }
    die("ERROR: " . $e->getMessage());
}
?>