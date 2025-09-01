<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <link rel="stylesheet" href="css/main.css">
    <title>Image Generator</title>
</head>

<body>
    <main>
        <div id="grid">
            <div id="image"></div>
            <div id="brand">
                <img id="imageBrand" src="brand/brand.png" alt="Brand">
            </div>
        </div>
        
        <!-- Controle de Qualidade WebP -->
        <div class="quality-control">
            <label for="webpQuality">Qualidade WebP:</label>
            <input type="range" id="webpQuality" min="1" max="100" value="80">
            <span id="qualityValue">80%</span>
        </div>
        
        <!-- Barra de Progresso -->
        <div id="progressContainer" class="progress-container">
            <div class="progress-info">
                <span id="progressText">Aguardando...</span>
            </div>
            <div class="progress-bar">
                <div id="progressFill" class="progress-fill"></div>
            </div>
        </div>
        
        <button id="generateBtn" onclick="startGeneration()">Gerar Imagens</button>
    </main>

    <script src="js/html2canvas.js"></script>
    <script type="module" src="js/main.js"></script>
</body>

</html>