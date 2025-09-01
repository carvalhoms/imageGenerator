<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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
        <button id="generateBtn" onclick="startGeneration()">Gerar Imagens</button>
    </main>

    <script src="js/html2canvas.js"></script>
    <script type="module" src="js/main.js"></script>
</body>

</html>