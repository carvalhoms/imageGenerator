import data from "../data/data.js";

let isProcessing = false;

window.onload = function() {
    // Carrega preview da primeira imagem encontrada
    loadPreviewImage();
};

// Função global para ser chamada pelo botão
window.startGeneration = function() {
    if (isProcessing) return;
    
    isProcessing = true;
    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.textContent = 'Processando...';
    
    // Inicia o processamento das imagens
    processImagesSequentially(0);
};

function loadPreviewImage() {
    // Pega o primeiro item dos dados para preview
    if (data.length === 0) {
        console.error('Nenhum dado encontrado para preview');
        return;
    }
    
    let firstCode = data[0].code;
    console.log(`Carregando preview: ${firstCode}`);
    
    function findPreviewImageWithExtension() {
        let imageElement = document.getElementById('image');
        let extensions = ['jpeg', 'png', 'jpg', 'webp'];
        let extensionIndex = 0;
        
        function tryNextExtension() {
            if (extensionIndex >= extensions.length) {
                console.error(`Nenhuma imagem encontrada para preview: ${firstCode}`);
                // Se não encontrar, tenta o próximo item
                if (data.length > 1) {
                    data.shift(); // Remove o primeiro item
                    loadPreviewImage(); // Tenta novamente
                }
                return;
            }
            
            let extension = extensions[extensionIndex];
            let imageUrl = '/imgOld/' + firstCode + '.' + extension;
            
            let img = new Image();
            
            img.onload = function() {
                console.log(`Preview carregado: ${firstCode}.${extension}`);
                imageElement.style = `background: url('${imageUrl}') no-repeat; background-size: contain; margin: 0 auto; background-position: center;`;
            };
            
            img.onerror = function() {
                console.log(`Extensão ${extension} não encontrada para preview ${firstCode}, tentando próxima...`);
                extensionIndex++;
                tryNextExtension();
            };
            
            img.src = imageUrl;
        }
        
        tryNextExtension();
    }
    
    findPreviewImageWithExtension();
}

function processImagesSequentially(index) {
    // Verifica se ainda há imagens para processar
    if (index >= data.length) {
        console.log("Todas as imagens foram processadas!");
        const btn = document.getElementById('generateBtn');
        btn.disabled = false;
        btn.textContent = 'Gerar Imagens';
        isProcessing = false;
        return;
    }

    let currentData = data[index];
    let code = currentData.code;
    let newCode = currentData.newCode || code;
    
    console.log(`Processando imagem ${index + 1}/${data.length}: ${code}`);

    function findImageWithExtension() {
        let imageElement = document.getElementById('image');
        let extensions = ['jpeg', 'png', 'jpg', 'webp'];
        let extensionIndex = 0;
        
        function tryNextExtension() {
            if (extensionIndex >= extensions.length) {
                console.error(`Nenhuma imagem encontrada para o código: ${code}`);
                // Se não encontrou nenhuma imagem, passa para o próximo item
                processImagesSequentially(index + 1);
                return;
            }
            
            let extension = extensions[extensionIndex];
            let imageUrl = '/imgOld/' + code + '.' + extension;
            
            let img = new Image();
            
            img.onload = function() {
                console.log(`Imagem encontrada: ${code}.${extension}`);
                imageElement.style = `background: url('${imageUrl}') no-repeat; background-size: contain; margin: 0 auto; background-position: center;`;
                
                // Agora que encontramos a imagem, salvamos
                saveImage(extension);
            };
            
            img.onerror = function() {
                console.log(`Extensão ${extension} não encontrada para ${code}, tentando próxima...`);
                extensionIndex++;
                tryNextExtension();
            };
            
            img.src = imageUrl;
        }
        
        // Inicia a verificação com a primeira extensão
        tryNextExtension();
    }

    function saveImage(extension) {
        console.log(`Preparando para salvar ${code} como ${newCode}.${extension}`);
        
        html2canvas(document.getElementById("grid")).then(function(canvas) {
            console.log(`Canvas gerado para ${code}`);
            
            let ajax = new XMLHttpRequest();
            ajax.open("POST", "saveImage.php", true); // Mudando para assíncrono (true)
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            
            let params = "image=" + encodeURIComponent(canvas.toDataURL("image/jpeg", 0.9)) + 
                        "&code=" + encodeURIComponent(code) + 
                        "&newCode=" + encodeURIComponent(newCode) +
                        "&extension=" + encodeURIComponent(extension);
            
            ajax.onreadystatechange = function() {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        console.log(`Imagem ${code} salva como ${newCode}.${extension}`);
                    } else {
                        console.error(`Erro ao salvar ${code}: ${this.status} ${this.statusText}`);
                    }
                    // Independentemente do resultado, continua para o próximo item
                    processImagesSequentially(index + 1);
                }
            };
            
            ajax.onerror = function() {
                console.error(`Erro de rede ao salvar ${code}`);
                // Em caso de erro, continua para o próximo item
                processImagesSequentially(index + 1);
            };
            
            ajax.send(params);
        }).catch(function(error) {
            console.error(`Erro ao gerar canvas para ${code}:`, error);
            // Em caso de erro no html2canvas, continua para o próximo item
            processImagesSequentially(index + 1);
        });
    }

    // Inicia o processo de encontrar a imagem com a extensão correta
    findImageWithExtension();
}