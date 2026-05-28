<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner Digital Web Pro</title>
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
        
        /* Cuadro guía para centrar el documento */
        .camera-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 3px dashed #007bff;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
            margin: 30px 25px;
            border-radius: 4px;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-overlay::after {
            content: "ENFOQUE EL TEXTO AQUÍ";
            color: #007bff;
            font-size: 11px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.95);
            padding: 5px 10px;
            border-radius: 4px;
            letter-spacing: 1px;
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 14px 24px;
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
            border: 2px solid #28a745;
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
            color: #333;
            margin-top: 2px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Escáner Digital Nítido</h2>
    <p>Ubique el papel dentro del recuadro. El sistema optimizará el contraste para que sea legible.</p>

    <div class="camera-wrapper" id="cameraWrapper">
        <video id="video" autoplay playsinline></video>
        <div class="camera-overlay"></div>
    </div>

    <button class="btn" id="btnAccion" onclick="iniciarO_Capturar()">📸 Abrir Cámara</button>
    
    <form action="procesar.php" method="POST" id="formScanner">
        <input type="hidden" name="images_package" id="images_package">
        <button type="submit" class="btn btn-success" id="btnGuardar">📦 Guardar PDF Optimizado</button>
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
            // MODIFICADO: Forzamos al sensor a trabajar en alta resolución (Full HD mínimo)
            const opcionesVideo = {
                facingMode: { exact: "environment" },
                width: { ideal: 1920, min: 1280 },
                height: { ideal: 1080, min: 720 }
            };

            try {
                streamLocal = await navigator.mediaDevices.getUserMedia({ video: opcionesVideo, audio: false });
            } catch (err) {
                // Alternativa por si se prueba en PC o cámaras antiguas
                streamLocal = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 1280, height: 720 }, 
                    audio: false 
                });
            }
            video.srcObject = streamLocal;
            cameraWrapper.style.display = "block";
            btnAccion.innerText = "📸 Capturar Documento";
            clickCount = 1;
        } else {
            procesarCapturaNítida();
        }
    }

    function procesarCapturaNítida() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Dimensiones reales del sensor de la cámara
        const videoWidth = video.videoWidth;
        const videoHeight = video.videoHeight;

        // Proporciones en la pantalla del navegador
        const wrapperRect = cameraWrapper.getBoundingClientRect();
        const overlay = document.querySelector('.camera-overlay');
        const overlayRect = overlay.getBoundingClientRect();

        // Mapeo exacto de coordenadas para no perder píxeles reales
        const scaleX = videoWidth / wrapperRect.width;
        const scaleY = videoHeight / wrapperRect.height;

        const cropX = (overlayRect.left - wrapperRect.left) * scaleX;
        const cropY = (overlayRect.top - wrapperRect.top) * scaleY;
        const cropWidth = overlayRect.width * scaleX;
        const cropHeight = overlayRect.height * scaleY;

        // Ajustamos el canvas a la resolución real del recorte de alta definición
        canvas.width = cropWidth;
        canvas.height = cropHeight;

        // Dibujar el fragmento original en alta definición
        ctx.drawImage(video, cropX, cropY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

        // --- FILTRO DE NITIDEZ Y ALTO CONTRASTE (Efecto Escáner) ---
        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imgData.data;

        for (let i = 0; i < data.length; i += 4) {
            // Convertir a escala de grises usando la luminosidad percibida
            let gris = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
            
            // FILTRO UMBRAL (Threshold): Si el píxel es claro, lo vuelve blanco puro. Si es oscuro (letras), negro puro.
            let umbral = 125; // Puedes ajustar este número entre 100 y 140 para calibrar la sensibilidad
            let finalColor = (gris > umbral) ? 255 : 0;

            data[i]     = finalColor; // R
            data[i + 1] = finalColor; // G
            data[i + 2] = finalColor; // B
        }
        ctx.putImageData(imgData, 0, 0);
        // -----------------------------------------------------------

        // Exportamos en JPEG con calidad al 95%
        const fotoProcesada = canvas.toDataURL('image/jpeg', 0.95);
        listaHojasBase64.push(fotoProcesada);

        document.getElementById('images_package').value = JSON.stringify(listaHojasBase64);

        // Crear la vista previa en la grilla de abajo
        const thumbContainer = document.createElement('div');
        thumbContainer.className = 'thumb-container';
        const imgElement = document.createElement('img');
        imgElement.src = fotoProcesada;
        const labelElement = document.createElement('div');
        labelElement.className = 'thumb-label';
        labelElement.innerText = "Pág. " + listaHojasBase64.length + " ✔";

        thumbContainer.appendChild(imgElement);
        thumbContainer.appendChild(labelElement);
        previewZone.appendChild(thumbContainer);

        if (listaHojasBase64.length > 0) {
            btnGuardar.style.display = "block";
        }
    }
</script>

</body>
</html>
