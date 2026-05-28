<?php
// 1. Configuración de la Base de Datos
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistema_escanner";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión a la BD: " . $conexion->connect_error);
}

// 2. Configuración del Servidor de Correo
$correo_servidor = '{imap.gmail.com:993/imap/ssl}INBOX';
$usuario_correo  = 'tu_correo_escanner@gmail.com'; 
$password_correo = 'tu_contraseña_de_aplicacion'; 

$inbox = imap_open($correo_servidor, $usuario_correo, $password_correo) or die('No se pudo conectar: ' . imap_last_error());

// Buscar correos no leídos
$correos = imap_search($inbox, 'UNSEEN');

if ($correos) {
    require('fpdf/fpdf.php');

    foreach ($correos as $numero_correo) {
        $estructura = imap_fetchstructure($inbox, $numero_correo);
        
        // Creamos un array para acumular todas las imágenes de ESTE correo
        $imagenes_del_correo = [];

        if (isset($estructura->parts) && count($estructura->parts)) {
            for ($i = 0; $i < count($estructura->parts); $i++) {
                $part = $estructura->parts[$i];
                $is_attachment = false;
                $filename = '';

                if ($part->ifdparameters) {
                    foreach ($part->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $is_attachment = true;
                            $filename = $object->value;
                        }
                    }
                }

                // Si es un archivo adjunto válido, verificamos si es una imagen
                if ($is_attachment) {
                    $tipo_archivo = pathinfo($filename, PATHINFO_EXTENSION);
                    $tipos_permitidos = array('jpg', 'jpeg', 'png');

                    if (in_array(strtolower($tipo_archivo), $tipos_permitidos)) {
                        $data = imap_fetchbody($inbox, $numero_correo, $i+1);
                        if ($part->encoding == 3) { 
                            $data = base64_decode($data);
                        }

                        // Guardamos la imagen temporalmente en el servidor
                        $nombre_temp = "temp_" . uniqid() . "." . $tipo_archivo;
                        $ruta_temp = "uploads/" . $nombre_temp;
                        file_contents_save: file_put_contents($ruta_temp, $data);

                        // Guardamos la ruta en nuestra lista de hojas
                        $imagenes_del_correo[] = $ruta_temp;
                    }
                }
            }
        }

        // 3. Si el correo contenía imágenes, creamos el PDF ÚNICO
        if (count($imagenes_del_correo) > 0) {
            
            $pdf = new FPDF('P', 'mm', 'A4'); // Iniciamos el documento
            
            // Recorremos cada imagen guardada para meterla como una HOJA NUEVA
            foreach ($imagenes_del_correo as $ruta_imagen) {
                $pdf->AddPage(); // Agrega una nueva página en blanco al PDF
                $pdf->Image($ruta_imagen, 10, 10, 190, 0); // Dibuja la hoja correspondiente
            }

            // Nombre definitivo del PDF integrado
            $nombre_unico = "doc_multi_" . time();
            $ruta_pdf_final = "uploads/" . $nombre_unico . ".pdf";
            
            // Guardamos el PDF final con todas sus páginas
            $pdf->Output('F', $ruta_pdf_final);

            // Limpieza: Eliminamos todas las imágenes temporales sueltas
            foreach ($imagenes_del_correo as $ruta_imagen) {
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }

            // 4. Insertar un único registro en phpMyAdmin
            $nombre_db_archivo = $nombre_unico . ".pdf";
            $sql = "INSERT INTO documentos (nombre_archivo, ruta_pdf) VALUES ('$nombre_db_archivo', '$ruta_pdf_final')";
            $conexion->query($sql);
            
            echo "¡Éxito! Creado PDF compuesto por " . count($imagenes_del_correo) . " páginas.<br>";
        }
        
        // Marcar el correo como leído
        imap_setflag_full($inbox, $numero_correo, "\\Seen");
    }
} else {
    echo "No hay correos nuevos con hojas para escanear.";
}

imap_close($inbox);
$conexion->close();
?>