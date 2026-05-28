<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner Digital Web</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        h2 { margin-bottom: 5px; color: #333; }
        p { color: #666; font-size: 14px; margin-bottom: 20px; }
        
        /* Contenedor de la cámara con visor */
        .camera-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 20px auto;
            border-radius: 8px;
            overflow: hidden;
            background: #000;
            display: none;
        }
        video {
            width: 100%;
            display: block;
        }
        
        /* CUADRO INDICADOR: La guía visual de la hoja */
        .camera-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 4px dashed #007bff;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5); /* Oscurece el exterior */
            margin: 40px 30px; /* Margen para centrar el cuadro de la hoja */
            border-radius: 4px;
            pointer-events: none; /* No interrumpe los clics */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-overlay::after {
            content: "ALINEA LA HOJA AQUÍ";
            color: #007bff;
            font-size: 11px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.9);
            padding: 4px 8px;
            border-radius: 4px;
            letter-spacing: 1px;
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; display: none; }
        .btn-success:hover { background: #218838; }
        
        .preview-zone {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .thumb-container {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 2px;
            background: #fff;
        }
        .thumb-container img {
            width: 100%;
            border-radius: 4px;
            display: block;
        }
        .thumb-label {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Escáner Digital Web</h2>
    <p>Alinea tu documento dentro del recuadro azul para recortar el fondo automáticamente.</p>

    <!-- Envoltura de cámara con la máscara de enfoque -->
    <div class="camera-wrapper" id="cameraWrapper">
        <video id="video" autoplay playsinline></video>
        <div class="camera-overlay"></div>
    </div>

    <button class="btn" id="btnAccion" onclick="iniciarO_Capturar()">📸 Iniciar Escáner</button>
    
    <form action="procesar.php" method="POST" id="formScanner">
        <input type="hidden" name="images_package" id="images_package">
        <button type="submit" class="btn btn-success" id="btnGuardar">📦 Convertir y Guardar PDF</button>
    </form>

    <div class="preview-zone" id="previewZone"></div>
</div>

<script>
    let streamLocal = null;
    let clickCount = 0;
    let listaHojasBase64 = [];

    const video = document.getElementById('video');
    const cameraWrapper = document.getElementById('cameraWrapper');
    const btnAccion = document.getElementById('btnAccion');
    const btnGuardar = document.getElementById('btnGuardar');
    const previewZone = document.getElementById('previewZone');

    async function iniciarO_Capturar() {
        if (clickCount === 0) {
            // PASO 1: Encender la cámara trasera del celular
            try {
                streamLocal = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { exact: "environment" } },
                    audio: false
                });
            } catch (err) {
                // Si falla la trasera (ej. en PC), abre la cámara por defecto
                streamLocal = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }
            video.srcObject = streamLocal;
            cameraWrapper.style.display = "block";
            btnAccion.innerText = "📸 Capturar Hoja";
            clickCount = 1;
        } else {
            // PASO 2: Capturar y recortar solo el área del recuadro azul
            procesarCapturaRecortada();
        }
    }

    function procesarCapturaRecortada() {
        // Crear un canvas oculto para procesar el recorte físico
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Obtener las dimensiones reales del stream de video de la cámara
        const videoWidth = video.videoWidth;
        const videoHeight = video.videoHeight;

        // Obtener las proporciones visuales de la interfaz en la pantalla
        const wrapperRect = cameraWrapper.getBoundingClientRect();
        const overlay = document.querySelector('.camera-overlay');
        const overlayRect = overlay.getBoundingClientRect();

        // Calcular los porcentajes y coordenadas de dónde está el cuadro azul respecto al video completo
        const scaleX = videoWidth / wrapperRect.width;
        const scaleY = videoHeight / wrapperRect.height;

        const cropX = (overlayRect.left - wrapperRect.left) * scaleX;
        const cropY = (overlayRect.top - wrapperRect.top) * scaleY;
        const cropWidth = overlayRect.width * scaleX;
        const cropHeight = overlayRect.height * scaleY;

        // Asignar el tamaño final de la imagen ya recortada al canvas
        canvas.width = cropWidth;
        canvas.height = cropHeight;

        // Dibujar en el canvas EXCLUSIVAMENTE la sub-zona recortada del video original
        ctx.drawImage(video, cropX, cropY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

        // Convertir el recorte a formato base64 de alta calidad
        const fotoRecortadaBase64 = canvas.toDataURL('image/jpeg', 0.9);
        listaHojasBase64.push(fotoRecortadaBase64);

        // Actualizar el input invisible del formulario
        document.getElementById('images_package').value = JSON.stringify(listaHojasBase64);

        // Renderizar la miniatura en la pantalla para control del usuario
        const thumbContainer = document.createElement('div');
        thumbContainer.className = 'thumb-container';
        
        const imgElement = document.createElement('img');
        imgElement.src = fotoRecortadaBase64;
        
        const labelElement = document.createElement('div');
        labelElement.className = 'thumb-label';
        labelElement.innerText = "Pág. " + listaHojasBase64.length;

        thumbContainer.appendChild(imgElement);
        thumbContainer.appendChild(labelElement);
        previewZone.appendChild(thumbContainer);

        // Mostrar el botón de guardar PDF si ya hay hojas listas
        if (listaHojasBase64.length > 0) {
            btnGuardar.style.display = "block";
        }
    }
</script>

</body>
</html>
