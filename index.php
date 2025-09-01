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
        <!-- Navegação de imagens -->
        <div class="image-navigation">
            <button id="prevBtn" class="nav-btn" onclick="navigateImage(-1)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </button>
            <div id="grid">
                <div id="image"></div>
                <div id="brand">
                    <img id="imageBrand" src="brand/brand.png" alt="Brand">
                </div>
            </div>
            <button id="nextBtn" class="nav-btn" onclick="navigateImage(1)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                </svg>
            </button>
        </div>
        
        <!-- Informações da imagem atual -->
        <div class="image-info">
            <div class="info-left">
                <span>Preview: </span>
                <span id="imageName"></span>
            </div>
            <div class="info-right">
                <span id="imageCounter">Carregando...</span>
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