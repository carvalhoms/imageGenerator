let isProcessing = false;
let imagesList = [];
let currentImageIndex = 0;

window.onload = function () {
  // Carrega a lista de imagens e o preview
  loadImagesList();

  // Configura os controles
  setupControls();

  // Inicializa a UI (barra oculta, botão visível)
  toggleProcessingUI(false);
};

// Função para configurar os controles
function setupControls() {
  // Configura o controle de qualidade WebP
  const qualitySlider = document.getElementById('webpQuality');
  const qualityValue = document.getElementById('qualityValue');

  if (qualitySlider && qualityValue) {
    qualitySlider.addEventListener('input', function () {
      qualityValue.textContent = this.value + '%';
    });
  }

  // Configura o controle de tamanho da imagem
  const sizeSlider = document.getElementById('imageSize');

  if (sizeSlider) {
    sizeSlider.addEventListener('input', function () {
      updateImageSize(this.value);
    });
  }

  // Configura os botões de navegação
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      navigateImage(-1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      navigateImage(1);
    });
  }
}

// Variável para controlar o tamanho da imagem
let currentImageSize = 90; // 90% como padrão

// Função para atualizar o tamanho da imagem
function updateImageSize(sizePercent) {
  currentImageSize = sizePercent;
  const imageElement = document.getElementById('image');
  if (imageElement) {
    // Remove estilos inline de tamanho para evitar conflito com html2canvas
    imageElement.style.width = '';
    imageElement.style.height = '';

    // Calcula o tamanho baseado na porcentagem (40% a 140%)
    const widthPx = Math.round(768 * (sizePercent / 100));
    const heightPx = Math.round(650 * (sizePercent / 100));

    // Cria ou atualiza uma regra CSS dinâmica
    let styleElement = document.getElementById('dynamic-image-size');
    if (!styleElement) {
      styleElement = document.createElement('style');
      styleElement.id = 'dynamic-image-size';
      document.head.appendChild(styleElement);
    }

    styleElement.textContent = `
      #grid #image {
        width: ${widthPx}px !important;
        height: ${heightPx}px !important;
      }
    `;
  }
}

// Função para controlar a visibilidade da barra de progresso e botão
function toggleProcessingUI(processing) {
  const progressContainer = document.getElementById('progressContainer');
  const generateBtn = document.getElementById('generateBtn');

  if (processing) {
    // Mostra barra de progresso e oculta botão
    if (progressContainer) progressContainer.style.display = 'block';
    if (generateBtn) generateBtn.style.display = 'none';
  } else {
    // Oculta barra de progresso e mostra botão
    if (progressContainer) progressContainer.style.display = 'none';
    if (generateBtn) generateBtn.style.display = 'block';
  }
}

// Função para carregar lista de imagens da pasta
async function loadImagesList() {
  try {
    const response = await fetch('api/listImages.php');
    const data = await response.json();

    if (data.success && data.images.length > 0) {
      // Ordena as imagens por nome para manter consistência
      imagesList = data.images.sort((a, b) => a.filename.localeCompare(b.filename));
      currentImageIndex = 0;

      console.log(`${data.count} imagens encontradas na pasta imgBase`);

      // Carrega preview da primeira imagem
      loadPreviewImage();
      updateNavigationButtons();
      updateImageInfo();
    } else {
      console.error('Nenhuma imagem encontrada na pasta imgBase');
      alert('Nenhuma imagem encontrada na pasta imgBase. Adicione algumas imagens para processar.');
    }
  } catch (error) {
    console.error('Erro ao carregar lista de imagens:', error);
    alert('Erro ao carregar imagens. Verifique se a pasta imgBase existe.');
  }
}

// Funções para controle da barra de progresso
function showProgress() {
  const progressContainer = document.getElementById('progressContainer');
  if (progressContainer) {
    progressContainer.style.display = 'block';
    updateProgress(0, 'Preparando...');
  }
}

function hideProgress() {
  const progressContainer = document.getElementById('progressContainer');
  if (progressContainer) {
    progressContainer.style.display = 'none';
  }
}

function updateProgress(percentage, text = '') {
  const progressFill = document.getElementById('progressFill');
  const progressText = document.getElementById('progressText');

  if (progressFill) {
    progressFill.style.width = percentage + '%';
  }

  if (progressText && text) {
    progressText.textContent = text;
  }
}// Função global para ser chamada pelo botão
window.startGeneration = function () {
  if (isProcessing) return;

  if (imagesList.length === 0) {
    alert('Nenhuma imagem disponível para processar. Atualize a página.');
    return;
  }

  console.log('=== INICIANDO PROCESSAMENTO ===');
  console.log(`Total de imagens a processar: ${imagesList.length}`);
  console.log('Lista de imagens:', imagesList);

  isProcessing = true;
  toggleProcessingUI(true); // Mostra barra e oculta botão
  const btn = document.getElementById('generateBtn');
  btn.disabled = true;
  btn.textContent = 'Processando...';

  // Desabilita os botões de navegação durante o processamento
  updateNavigationButtons();

  // Mostra a barra de progresso
  showProgress();

  // Inicia o processamento das imagens
  processImagesSequentially(0);
};

function loadPreviewImage() {
  // Verifica se há imagens disponíveis
  if (imagesList.length === 0) {
    console.error('Nenhuma imagem encontrada para preview');
    return;
  }

  // Garante que o índice está dentro dos limites
  if (currentImageIndex < 0) currentImageIndex = 0;
  if (currentImageIndex >= imagesList.length) currentImageIndex = imagesList.length - 1;

  let currentImage = imagesList[currentImageIndex];
  console.log(`Carregando preview: ${currentImage.filename} (${currentImageIndex + 1}/${imagesList.length})`);

  let imageElement = document.getElementById('image');
  let imageUrl = '/imgBase/' + currentImage.filename;

  let img = new Image();

  img.onload = function () {
    console.log(`Preview carregado: ${currentImage.filename}`);

    // Aplica o background da imagem
    imageElement.style.background = `url('${imageUrl}') no-repeat`;
    imageElement.style.backgroundSize = 'contain';
    imageElement.style.backgroundPosition = 'center';
    imageElement.style.margin = '0 auto';

    updateImageInfo();

    // Aplica o tamanho atual da imagem
    updateImageSize(currentImageSize);
  };

  img.onerror = function () {
    console.error(`Erro ao carregar preview: ${currentImage.filename}`);
    // Se falhar, tenta a próxima imagem
    if (currentImageIndex < imagesList.length - 1) {
      currentImageIndex++;
      loadPreviewImage(); // Tenta novamente
    } else {
      console.error('Não foi possível carregar nenhuma imagem válida');
    }
  };

  img.src = imageUrl;
}

function processImagesSequentially(index) {
  // Verifica se ainda há imagens para processar
  if (index >= imagesList.length) {
    console.log("=== TODAS AS IMAGENS PROCESSADAS ===");
    console.log(`Total processado: ${index}/${imagesList.length} imagens`);
    updateProgress(95, 'Convertendo para WebP...');

    // Inicia a conversão para WebP
    convertToWebp();
    return;
  }

  let currentImage = imagesList[index];
  let filename = currentImage.filename;
  let code = currentImage.code;
  let extension = currentImage.extension;

  // Calcula e atualiza o progresso
  const percentage = (index / imagesList.length) * 100;
  updateProgress(percentage, `Gerando Imagem ${index + 1} de ${imagesList.length}`);

  console.log(`=== PROCESSANDO IMAGEM ${index + 1}/${imagesList.length} ===`);
  console.log(`Arquivo: ${filename}`);
  console.log(`Code: ${code}`);
  console.log(`Extension: ${extension}`);

  let imageElement = document.getElementById('image');
  let imageUrl = '/imgBase/' + filename;

  let img = new Image();

  img.onload = function () {
    console.log(`✅ Imagem carregada com sucesso: ${filename}`);
    imageElement.style = `background: url('${imageUrl}') no-repeat; background-size: contain; margin: 0 auto; background-position: center;`;

    // Agora que carregamos a imagem, processamos
    console.log(`Iniciando saveProcessedImage para: ${filename}`);
    saveProcessedImage(filename, code, extension);
  };

  img.onerror = function () {
    console.error(`❌ Erro ao carregar imagem: ${filename}`);
    console.error(`URL testada: ${imageUrl}`);
    // Se falhar, passa para a próxima imagem
    console.log(`Pulando para próxima imagem (${index + 1} -> ${index + 2})`);
    processImagesSequentially(index + 1);
  };

  img.src = imageUrl;

  function saveProcessedImage(originalFilename, code, extension) {
    console.log(`🔄 Preparando para processar ${originalFilename}`);
    console.log(`Dados: code=${code}, extension=${extension}`);

    html2canvas(document.getElementById("grid")).then(function (canvas) {
      console.log(`🖼️ Canvas gerado para ${originalFilename}`);
      console.log(`Canvas dimensions: ${canvas.width}x${canvas.height}`);

      let ajax = new XMLHttpRequest();
      ajax.open("POST", "api/saveProcessedImage.php", true);
      ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      let dataURL = canvas.toDataURL("image/jpeg", 0.9);
      let params = "image=" + encodeURIComponent(dataURL) +
        "&originalFilename=" + encodeURIComponent(originalFilename) +
        "&code=" + encodeURIComponent(code) +
        "&extension=" + encodeURIComponent(extension);

      console.log(`📤 Enviando dados para API - Tamanho: ${Math.round(dataURL.length/1024)}KB`);

      ajax.onreadystatechange = function () {
        if (this.readyState == 4) {
          if (this.status == 200) {
            console.log(`✅ Imagem processada com sucesso: ${originalFilename}`);
            console.log(`Resposta da API: ${this.responseText}`);
          } else {
            console.error(`❌ Erro HTTP ao salvar ${originalFilename}: ${this.status} ${this.statusText}`);
            console.error(`Resposta: ${this.responseText}`);
          }
          // Independentemente do resultado, continua para o próximo item
          console.log(`🔄 Continuando para próxima imagem (${index + 1} -> ${index + 2})`);
          processImagesSequentially(index + 1);
        }
      };

      ajax.onerror = function () {
        console.error(`❌ Erro de rede ao salvar ${originalFilename}`);
        console.error('Network error details:', this);
        // Em caso de erro, continua para o próximo item
        console.log(`🔄 Continuando para próxima imagem devido ao erro de rede`);
        processImagesSequentially(index + 1);
      };

      ajax.send(params);
    }).catch(function (error) {
      console.error(`❌ Erro ao gerar canvas para ${originalFilename}:`, error);
      console.error('Canvas error details:', error.stack);
      // Em caso de erro no html2canvas, continua para o próximo item
      console.log(`🔄 Continuando para próxima imagem devido ao erro de canvas`);
      processImagesSequentially(index + 1);
    });
  }
}

// Função para converter as imagens da pasta .temp para WebP
function convertToWebp() {
  const qualitySlider = document.getElementById('webpQuality');
  const quality = qualitySlider ? qualitySlider.value : 80;

  console.log('=== INICIANDO CONVERSÃO WEBP ===');
  console.log(`Qualidade configurada: ${quality}%`);

  // UMA SÓ CHAMADA - SIMPLES!
  callWebpConversion(quality);
}

// Função para chamar a conversão WebP (SIMPLIFICADA - UMA SÓ CHAMADA)
function callWebpConversion(quality) {
  console.log(`🔄 Chamando API convertToWebp - Quality: ${quality}`);

  const ajax = new XMLHttpRequest();
  ajax.open("POST", "api/convertToWebp.php", true);
  ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  const params = "quality=" + encodeURIComponent(quality);
  console.log(`📤 Parâmetros enviados: ${params}`);

  ajax.onreadystatechange = function () {
    if (this.readyState == 4) {
      console.log(`📥 Resposta recebida - Status: ${this.status}`);

      if (this.status == 200) {
        try {
          let responseText = this.responseText.trim();
          console.log(`📋 Resposta: ${responseText.substring(0, 200)}`);

          if (responseText.startsWith('<') || responseText.startsWith('<!')) {
            console.error(`❌ Resposta HTML:`, responseText.substring(0, 200));
            updateProgress(100, 'Erro de servidor');
            finishProcess();
            return;
          }

          const response = JSON.parse(responseText);
          console.log(`✅ Resposta:`, response);

          if (response.success) {
            updateProgress(90, `Convertidos: ${response.converted} arquivos`);
            setTimeout(() => {
              updateProgress(95, `Thumbnails: ${response.thumbnails} gerados`);
              setTimeout(() => {
                updateProgress(100, 'Processo concluído!');
                finishProcess();
              }, 500);
            }, 500);
          } else {
            console.error('Erro na conversão:', response.message);
            updateProgress(100, 'Erro na conversão');
            finishProcess();
          }
        } catch (e) {
          console.error('Erro ao processar resposta:', e);
          updateProgress(100, 'Erro na conversão');
          finishProcess();
        }
      } else {
        console.error(`Erro HTTP: ${this.status} ${this.statusText}`);
        updateProgress(100, 'Erro HTTP');
        finishProcess();
      }
    }
  };

  ajax.onerror = function () {
    console.error('Erro de rede');
    updateProgress(100, 'Erro de rede');
    finishProcess();
  };

  ajax.send(params);
}

// Função para finalizar o processo
function finishProcess() {
  setTimeout(() => {
    hideProgress();
    const btn = document.getElementById('generateBtn');
    btn.disabled = false;
    btn.textContent = 'Gerar Imagens';
    isProcessing = false;
    toggleProcessingUI(false); // Oculta barra e mostra botão
    updateNavigationButtons(); // Reabilita os botões de navegação
  }, 2000);
}

// Função para navegar entre as imagens
function navigateImage(direction) {
  if (isProcessing) return; // Não permite navegação durante o processamento

  const newIndex = currentImageIndex + direction;

  // Verifica os limites
  if (newIndex >= 0 && newIndex < imagesList.length) {
    currentImageIndex = newIndex;
    loadPreviewImage();
    updateNavigationButtons();
  }
}

// Função para atualizar o estado dos botões de navegação
function updateNavigationButtons() {
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  if (prevBtn) {
    prevBtn.disabled = currentImageIndex <= 0 || isProcessing;
  }

  if (nextBtn) {
    nextBtn.disabled = currentImageIndex >= imagesList.length - 1 || isProcessing;
  }
}

// Função para atualizar as informações da imagem
function updateImageInfo() {
  const imageCounter = document.getElementById('imageCounter');
  const imageName = document.getElementById('imageName');

  if (imageCounter && imagesList.length > 0) {
    imageCounter.textContent = `Imagem ${currentImageIndex + 1} de ${imagesList.length}`;
  }

  if (imageName && imagesList.length > 0) {
    imageName.textContent = imagesList[currentImageIndex].filename;
  }
}

// Adiciona suporte para navegação via teclado
document.addEventListener('keydown', function (event) {
  if (isProcessing) return; // Não permite navegação durante o processamento

  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    navigateImage(-1);
  } else if (event.key === 'ArrowRight') {
    event.preventDefault();
    navigateImage(1);
  }
});