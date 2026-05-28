<?php
$host = "localhost";
$user = "root";       
$pass = "";           
$db   = "sistema_escanner";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión a la BD: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['images_package'])) {
    
    $paquete_imagenes = json_decode($_POST['images_package'], true);
    
    if (is_array($paquete_imagenes) && count($paquete_imagenes) > 0) {
        
        require('fpdf/fpdf.php');
        $pdf = new FPDF('P', 'mm', 'A4'); 
        
        $imagenes_temporales = [];
        $todo_el_texto_ocr = ""; // Variable para acumular el texto de todas las hojas

        // 1. Procesar y guardar temporalmente cada hoja
        foreach ($paquete_imagenes as $index => $raw_image) {
            $img_parts = explode(";base64,", $raw_image);
            $image_type_aux = explode("image/", $img_parts[0]);
            $image_type = $image_type_aux[1]; 
            $image_base64 = base64_decode($img_parts[1]);
            
            $nombre_temp = "temp_" . uniqid() . "_p" . ($index + 1) . "." . $image_type;
            $ruta_temp = "uploads/" . $nombre_temp;
            
            file_put_contents($ruta_temp, $image_base64);
            $imagenes_temporales[] = $ruta_temp;

            // --- NUEVO: EJECUTAR OCR EN ESTA HOJA ---
            // Ruta exacta donde instalaste Tesseract en Windows
            $tesseract_path = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
            
            // Nombre del archivo temporal de texto donde Tesseract guardará el resultado
            $output_text_file = "uploads/txt_" . uniqid();
            
            // Construir el comando: tesseract.exe [imagen] [archivo_salida] -l spa (idioma español)
            $comando = "$tesseract_path \"$ruta_temp\" \"$output_text_file\" -l spa";
            
            // Ejecutar el comando en el sistema operativo
            shell_exec($comando);
            
            // Tesseract genera automáticamente un archivo .txt, leemos su contenido
            $archivo_txt_generado = $output_text_file . ".txt";
            if (file_exists($archivo_txt_generado)) {
                $texto_hoja = file_get_contents($archivo_txt_generado);
                $todo_el_texto_ocr .= "--- Página " . ($index + 1) . " ---\n" . $texto_hoja . "\n";
                
                // Borrar el archivo .txt para mantener limpio el servidor
                unlink($archivo_txt_generado);
            }
        }

        // 2. Armar el PDF consolidado
        foreach ($imagenes_temporales as $ruta_hoja) {
            $pdf->AddPage();
            $pdf->Image($ruta_hoja, 10, 10, 190, 0);
        }

        $nombre_unico = "doc_ocr_" . time();
        $ruta_pdf_final = "uploads/" . $nombre_unico . ".pdf";
        $pdf->Output('F', $ruta_pdf_final);

        // Limpieza de imágenes temporales
        foreach ($imagenes_temporales as $ruta_hoja) {
            if (file_exists($ruta_hoja)) {
                unlink($ruta_hoja);
            }
        }

        // Escapar los caracteres extraños del texto leído para que no rompa la consulta SQL
        $texto_limpio_bd = $conexion->real_escape_string($todo_el_texto_ocr);

        // 3. Registrar en phpMyAdmin incluyendo el texto plano obtenido por el OCR
        $nombre_db_archivo = $nombre_unico . ".pdf";
        $sql = "INSERT INTO documentos (nombre_archivo, ruta_pdf, texto_extraido) VALUES ('$nombre_db_archivo', '$ruta_pdf_final', '$texto_limpio_bd')";
        
        if ($conexion->query($sql) === TRUE) {
            echo "<div style='text-align:center; font-family:sans-serif; margin-top:40px; max-width:600px; margin-left:auto; margin-right:auto;'>";
            echo "<h2 style='color: #28a745;'>¡Documento Guardado con OCR!</h2>";
            echo "<p>Se procesaron " . count($paquete_imagenes) . " páginas.</p>";
            echo "<a href='$ruta_pdf_final' target='_blank' style='display:inline-block; background:#007bff; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold; margin-right:12px;'>📄 Ver PDF</a>";
            echo "<a href='index.php' style='display:inline-block; background:#6c757d; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold;'>📸 Escanear Otro</a>";
            
            // Mostrar una vista previa en la página de lo que el sistema "leyó"
            echo "<h3 style='margin-top:30px; text-align:left;'>Texto detectado por el sistema:</h3>";
            echo "<textarea style='width:100%; height:150px; padding:10px; border-radius:6px; border:1px solid #ccc; background:#fafafa;' readonly>" . htmlspecialchars($todo_el_texto_ocr) . "</textarea>";
            echo "</div>";
        } else {
            echo "Error al registrar en BD: " . $conexion->error;
        }
        
    } else {
        echo "El paquete no contiene hojas válidas.";
    }

    $conexion->close();
} else {
    echo "Petición inválida.";
}
?>