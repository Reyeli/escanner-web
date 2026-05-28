<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner Inteligente Nítido - InnovaSoft</title>
    
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
        
        .editor-wrapper {
            max-width: 100%;
            max-height: 400px;
            margin-bottom: 20px;
            display: none;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
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
        #estadoOpenCV {
            font-size: 12px;
            color: #ff9800;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Escáner Inteligente Pro</h2>
    <p>Detección automática de bordes con OpenCV y binarización adaptativa para máxima legibilidad.</p>
    
    <div id="estadoOpenCV">🔄 Cargando Inteligencia Artificial...</div>

    <label for="inputFoto" class="btn" id="lblSeleccionar" style="display:none;">📸 Tomar / Seleccionar Foto</label>
    <input type="file" id="inputFoto" accept="image/*">

    <div class="editor-wrapper" id="editorWrapper">
        <img id="imageToCrop" src="">
    </div>

    <button class="btn btn-warning" id="btnRecortar" onclick="procesarRecorteInteligente()">✂️ Confirmar y Optimizar Hoja</button>
    
    <form action="procesar.php" method="POST" id="formScanner">
        <input type="hidden" name="images_package" id="images_package">
        <button type="submit" class="btn btn-success" id="btnGuardar">📦 Generar PDF de Alta Nitidez</button>
    </form>

    <div class="preview-zone" id="previewZone"></div>
</div>

<canvas id="canvasMat" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script async src="https://docs.opencv.org/4.x/opencv.js" onload="openCvListo()"></script>

<script>
    let cropper = null;
    let listaHojasBase64 = [];
    let openCvCargado = false;
    
    const inputFoto = document.getElementById('inputFoto');
    const imageToCrop = document.getElementById('imageToCrop');
    const editorWrapper = document.getElementById('editorWrapper');
    const btnRecortar = document.getElementById('btnRecortar');
    const btnGuardar = document.getElementById('btnGuardar');
    const lblSeleccionar = document.getElementById('lblSeleccionar');
    const previewZone = document.getElementById('previewZone');
    const estadoOpenCV = document.getElementById('estadoOpenCV');

    function openCvListo() {
        openCvCargado = true;
        estadoOpenCV.style.display = "none";
        lblSeleccionar.style.display = "inline-block";
    }

    inputFoto.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function(event) {
                if (cropper) { cropper.destroy(); }
                
                imageToCrop.src = event.target.result;
                editorWrapper.style.display = "block";
                btnRecortar.style.display = "block";
                lblSeleccionar.innerText = "🔄 Cambiar Foto";
                
                // Inicializar Cropper en modo libre
                cropper = new Cropper(imageToCrop, {
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.95,
                    restore: false,
                    guides: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    ready: function () {
                        if (openCvCargado) {
                            detectarBordesYAutoAjustar();
                        }
                    }
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    // Algoritmo de Visión Artificial para buscar el contorno de la hoja
    function detectarBordesYAutoAjustar() {
        try {
            let src = cv.imread(imageToCrop);
            let gray = new cv.Mat();
            let blurred = new cv.Mat();
            let thresh = new cv.Mat();
            let contours = new cv.MatVector();
            let hierarchy = new cv.Mat();

            // 1. Pasar a escala de grises y desenfocar para quitar ruido
            cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY, 0);
            cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
            
            // 2. Umbral de Canny para detectar bordes lineales contrastados
            cv.Canny(blurred, thresh, 75, 200, 3, false);
            
            // 3. Encontrar contornos en la imagen binaria
            cv.findContours(thresh, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

            let maxArea = 0;
            let maxContourIndex = -1;

            for (let i = 0; i < contours.size(); ++i) {
                let contour = contours.get(i);
                let area = cv.contourArea(contour);
                if (area > maxArea) {
                    maxArea = area;
                    maxContourIndex = i;
                }
            }

            // Si se encontró un área representativa, calculamos su caja delimitadora
            if (maxContourIndex !== -1) {
                let idealContour = contours.get(maxContourIndex);
                let rect = cv.boundingRect(idealContour);
                
                // Obtener las relaciones de escala entre el elemento original y la vista de Cropper
                const imageData = cropper.getImageData();
                const scale = imageData.width / imageData.naturalWidth;

                // Mover el cuadro de Cropper automáticamente a las coordenadas detectadas
                cropper.setCropBoxData({
                    left: imageData.left + (rect.x * scale),
                    top: imageData.top + (rect.y * scale),
                    width: rect.width * scale,
                    height: rect.height * scale
                });
            }

            // Liberar memoria de los objetos nativos de OpenCV C++
            src.delete(); gray.delete(); blurred.delete(); thresh.delete();
            contours.delete(); hierarchy.delete();
        } catch (err) {
            console.log("No se pudo auto-detectar, se mantiene ajuste manual estándar.");
        }
    }

    function procesarRecorteInteligente() {
        if (!cropper) return;

        // Extraer el canvas con la resolución nativa Full de la foto original
        const canvas = cropper.getCroppedCanvas({
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        const ctx = canvas.getContext('2d');
        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imgData.data;

        // --- FILTRO ADAPTATIVO AVANZADO DE NITIDEZ Y TEXTO ---
        // Convierte el fondo en blanco perfecto y realza el contraste de la tinta negra
        for (let i = 0; i < data.length; i += 4) {
            let r = data[i];
            let g = data[i+1];
            let b = data[i+2];

            // Luminosidad del píxel
            let gris = 0.299 * r + 0.587 * g + 0.114 * b;

            // Filtro dinámico: Limpiamos los grises claros (sombras de la mano o el celular)
            // Si el píxel tiende a claro, se fuerza a blanco puro; si es texto oscuro, se acentúa.
            if (gris > 140) {
                data[i] = 255;     // R
                data[i+1] = 255;   // G
                data[i+2] = 255;   // B
            } else {
                // Aumentar la densidad del color oscuro para máxima legibilidad
                let factorContraste = 0.6; 
                data[i] = Math.max(0, r * factorContraste);
                data[i+1] = Math.max(0, g * factorContraste);
                data[i+2] = Math.max(0, b * factorContraste);
            }
        }
        ctx.putImageData(imgData, 0, 0);
        // -----------------------------------------------------

        const fotoFinalBase64 = canvas.toDataURL('image/jpeg', 0.98);
        listaHojasBase64.push(fotoFinalBase64);

        document.getElementById('images_package').value = JSON.stringify(listaHojasBase64);

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

        cropper.destroy();
        cropper = null;
        editorWrapper.style.display = "none";
        btnRecortar.style.display = "none";
        lblSeleccionar.innerText = "📸 Agregar Siguiente Hoja";
        btnGuardar.style.display = "block";
        inputFoto.value = "";
    }
</script>

</body>
</html>
