<?php
// Configuración de la Base de Datos (Intentará conectar, pero si falla no detendrá el flujo)
$host = "localhost";
$user = "root";       
$pass = "";           
$db   = "sistema_escanner";

$conexion = null;
try {
    // Desactivar temporalmente los errores fatales automáticos de mysqli para controlarlo nosotros
    mysqli_report(MYSQLI_REPORT_OFF);
    $conexion = new mysqli($host, $user, $pass, $db);
} catch (Exception $e) {
    $conexion = null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['images_package'])) {
    
    $paquete_imagenes = json_decode($_POST['images_package'], true);
    
    if (is_array($paquete_imagenes) && count($paquete_imagenes) > 0) {
        
        require('fpdf.php'); // Asegurar la carga de la librería en la raíz
        $pdf = new FPDF('P', 'mm', 'A4'); 
        
        $imagenes_temporales = [];

        foreach ($paquete_imagenes as $index => $raw_image) {
            $img_parts = explode(";base64,", $raw_image);
            $image_type_aux = explode("image/", $img_parts[0]);
            $image_type = $image_type_aux[1]; 
            $image_base64 = base64_decode($img_parts[1]);
            
            $nombre_temp = "temp_" . uniqid() . "_p" . ($index + 1) . "." . $image_type;
            $ruta_temp = "uploads/" . $nombre_temp;
            
            file_put_contents($ruta_temp, $image_base64);
            $imagenes_temporales[] = $ruta_temp;
        }

        foreach ($imagenes_temporales as $ruta_hoja) {
            $pdf->AddPage();
            $pdf->Image($ruta_hoja, 10, 10, 190, 0);
        }

        $nombre_unico = "doc_web_" . time();
        $ruta_pdf_final = "uploads/" . $nombre_unico . ".pdf";
        $pdf->Output('F', $ruta_pdf_final);

        foreach ($imagenes_temporales as $ruta_hoja) {
            if (file_exists($ruta_hoja)) {
                unlink($ruta_hoja);
            }
        }

        // Si la conexión a la BD falló, igual mostramos el éxito del PDF
        echo "<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>";
        echo "<h2 style='color: #28a745;'>¡Documento Guardado!</h2>";
        echo "<p>Se unificaron exitosamente " . count($paquete_imagenes) . " páginas en un solo archivo PDF.</p>";
        
        if (!$conexion || $conexion->connect_error) {
            echo "<p style='color: #ff9800; font-size: 13px;'>⚠️ Nota: Guardado de forma local en el almacenamiento temporal (Sin persistencia en BD).</p><br>";
        } else {
            $nombre_db_archivo = $nombre_unico . ".pdf";
            $sql = "INSERT INTO documentos (nombre_archivo, ruta_pdf) VALUES ('$nombre_db_archivo', '$ruta_pdf_final')";
            $conexion->query($sql);
            $conexion->close();
        }
        
        echo "<a href='$ruta_pdf_final' target='_blank' style='display:inline-block; background:#007bff; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold; margin-right:12px;'>📄 Abrir PDF Integrado</a>";
        echo "<a href='index.php' style='display:inline-block; background:#6c757d; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold;'>📸 Iniciar Nuevo Escaneo</a>";
        echo "</div>";
        
    } else {
        echo "El paquete no contiene hojas válidas.";
    }
} else {
    echo "Petición inválida.";
}
?>
