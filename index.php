<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escáner de Documentos - InnovaSoft</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); max-width: 500px; width: 100%; text-align: center; }
        h2 { color: #333; margin-bottom: 10px; }
        p { color: #666; font-size: 14px; margin-bottom: 25px; }
        .btn { background-color: #007bff; color: white; padding: 14px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; display: inline-block; transition: background 0.3s; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
        .btn:hover { background-color: #0056b3; }
        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        
        /* Contenedor de miniaturas */
        #gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 20px; max-height: 250px; overflow-y: auto; padding: 10px; border: 1px dashed #ccc; border-radius: 8px; background: #fafafa; display: none; }
        .thumb-wrapper { position: relative; border: 2px solid #ddd; border-radius: 6px; padding: 3px; background: white; }
        .thumb-wrapper img { width: 100%; height: 120px; object-fit: cover; border-radius: 4px; }
        .page-badge { position: absolute; top: 5px; left: 5px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; font-size: 11px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <h2>Escáner Digital Web</h2>
    <p>Captura las páginas de tu documento una por una. Al finalizar, guárdalas en un solo PDF.</p>
    
    <label for="camera-input" class="btn" id="btn-capturar">📸 Escanear Hoja</label>
    <input type="file" id="camera-input" accept="image/*" capture="environment" style="display: none;">

    <div id="gallery"></div>
    
    <form id="upload-form" action="procesar.php" method="POST">
        <input type="hidden" name="images_package" id="images_package">
        <button type="submit" id="btn-finalizar" class="btn btn-success" style="display: none; margin-top: 20px;">📄 Convertir y Guardar Documento (0 Hojas)</button>
    </form>
</div>

<script>
    const cameraInput = document.getElementById('camera-input');
    const gallery = document.getElementById('gallery');
    const imagesPackageInput = document.getElementById('images_package');
    const btnFinalizar = document.getElementById('btn-finalizar');
    const btnCapturar = document.getElementById('btn-capturar');

    // Array global de JavaScript para retener la lista de hojas en memoria
    let coleccionHojas = [];

    cameraInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(event) {
                const base64Data = event.target.result;
                
                // 1. Agregar la imagen al array en memoria
                coleccionHojas.push(base64Data);
                
                // 2. Actualizar la interfaz visual (Galería de miniaturas)
                renderizarGaleria();
                
                // 3. Empaquetar el array completo en formato JSON para mandarlo a PHP
                imagesPackageInput.value = JSON.stringify(coleccionHojas);
                
                // 4. Cambiar el texto del botón de captura y mostrar el de guardar
                btnCapturar.innerHTML = "📸 Agregar Otra Hoja";
                btnFinalizar.style.display = 'block';
                btnFinalizar.innerHTML = `📄 Convertir y Guardar Documento (${coleccionHojas.length} Hojas)`;
                
                // Limpiar el input para permitir tomar otra foto seguida con el mismo disparador
                cameraInput.value = "";
            }
            
            reader.readAsDataURL(file);
        }
    });

    function renderizarGaleria() {
        gallery.innerHTML = "";
        gallery.style.display = "grid";
        
        coleccionHojas.forEach((imgSrc, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'thumb-wrapper';
            
            wrapper.innerHTML = `
                <span class="page-badge">Pág. ${index + 1}</span>
                <img src="${imgSrc}">
            `;
            gallery.appendChild(wrapper);
        });
    }
</script>

</body>
</html>