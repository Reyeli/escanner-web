<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner Profesional - InnovaSoft</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        h2 { margin: 0 0 5px 0; color: #333; }
        p { color: #666; font-size: 14px; margin-bottom: 20px; }
        
        /* Área de edición donde aparece la foto para recortar */
        .editor-wrapper {
            max-width: 100%;
            max-height: 400px;
            margin-bottom: 20px;
            display: none;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
        }
        .editor-wrapper img {
            max-width: 100%;
            display: block;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
            display: inline-block;
            box-sizing: border-box;
        }
        .btn-success { background: #28a745; display: none; }
        .btn-warning { background: #ff9800; display: none; }
        
        /* Ocultar el input de archivo feo por defecto */
        #inputFoto { display: none; }
        
        .preview-zone {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 25px;
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
    <h2>Escáner Profesional Web</h2>
    <p>Toma la foto normal con tu cámara, recorta los bordes con precisión y optimiza la nitidez.</p>

    <label for="inputFoto" class="btn" id="lblSeleccionar">📸 Tomar / Seleccionar Foto</label>
    <input type="file" id="inputFoto" accept="image/*">

    <div class="editor-wrapper" id="editorWrapper">
        <img id="imageToCrop" src="">
    </div>

    <button class="btn btn-warning" id="btnRecortar" onclick="procesarRecorteYNitidez()">✂️ Confirmar Recorte de Hoja</button>
    
    <form action="procesar.php" method="POST" id="formScanner">
        <input type="hidden" name="images_package" id="images_package">
        <button type="submit" class="btn btn-success" id="btnGuardar">📦 Generar PDF Legible</button>
    </form>

    <div class="preview-zone" id="previewZone"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
    let cropper = null;
    let listaHojasBase64 = [];
    
    const inputFoto = document.getElementById('inputFoto');
    const imageToCrop = document.getElementById('imageToCrop');
    const editorWrapper = document.getElementById('editorWrapper');
    const btnRecortar = document.getElementById('btnRecortar');
    const btnGuardar = document.getElementById('btnGuardar');
    const lblSeleccionar = document.getElementById('lblSeleccionar');
    const previewZone = document.getElementById('previewZone');

    // Detectar cuando el usuario toma una foto o selecciona un archivo
    inputFoto.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // Destruir cropper anterior si existía
                if (cropper) {
                    cropper.destroy();
                }
                
                imageToCrop.src = event.target.result;
                editorWrapper.style.display = "block";
                btnRecortar.style.display = "block";
                lblSeleccionar.innerText = "🔄 Cambiar Foto";
                
                // Inicializar Cropper con libertad de movimiento en las esquinas
                cropper = new Cropper(imageToCrop, {
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    function procesarRecorteYNitidez() {
        if (!cropper) return;

        // Obtener el lienzo recortado a la resolución nativa original de la foto
        const canvas = cropper.getCroppedCanvas({
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        const ctx = canvas.getContext('2d');

        // --- FILTRO DE ALTO CONTRASTE INTEGRADO ---
        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imgData.data;

        for (let i = 0; i < data.length; i += 4) {
            // Conversión a escala de grises de alta fidelidad
            let gris = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
            
            // Umbral calibrado (Todo fondo opaco/sombra pasa a blanco puro, texto a negro)
            let umbral = 130; 
            let colorFinal = (gris > umbral) ? 255 : 0;

            data[i]     = colorFinal;
            data[i + 1] = colorFinal;
            data[i + 2] = colorFinal;
        }
        ctx.putImageData(imgData, 0, 0);
        // ------------------------------------------

        // Convertir el canvas final optimizado a Base64 con alta densidad
        const fotoFinalBase64 = canvas.toDataURL('image/jpeg', 0.95);
        listaHojasBase64.push(fotoFinalBase64);

        // Actualizar el paquete para procesar.php
        document.getElementById('images_package').value = JSON.stringify(listaHojasBase64);

        // Crear vista previa en la grilla inferior
        const thumbContainer = document.createElement('div');
        thumbContainer.className = 'thumb-container';
        const imgElement = document.createElement('img');
        imgElement.src = fotoFinalBase64;
        const labelElement = document.createElement('div');
        labelElement.className = 'thumb-label';
        labelElement.innerText = "Pág. " + listaHojasBase64.length + " ✔";

        thumbContainer.appendChild(imgElement);
        thumbContainer.appendChild(labelElement);
        previewZone.appendChild(thumbContainer);

        // Limpiar el editor y preparar para la siguiente hoja
        cropper.destroy();
        cropper = null;
        editorWrapper.style.display = "none";
        btnRecortar.style.display = "none";
        lblSeleccionar.innerText = "📸 Agregar Siguiente Hoja";
        btnGuardar.style.display = "block";
        
        // Resetear el input para que permita subir la misma foto si se desea
        inputFoto.value = "";
    }
</script>

</body>
</html>
