let isProcessing = false;
let imagesList = [];

window.onload = function () {
  // Carrega a lista de imagens e o preview
  loadImagesList();

  // Configura o controle de qualidade WebP
  setupQualityControl();
};

// Função para configurar o controle de qualidade
function setupQualityControl() {
  const qualitySlider = document.getElementById('webpQuality');
  const qualityValue = document.getElementById('qualityValue');

  if (qualitySlider && qualityValue) {
    qualitySlider.addEventListener('input', function () {
      qualityValue.textContent = this.value + '%';
    });
  }
}

// Função para carregar lista de imagens da pasta
async function loadImagesList() {
  try {
    const response = await fetch('api/listImages.php');
    const data = await response.json();

    if (data.success && data.images.length > 0) {
      imagesList = data.images;
      console.log(`${data.count} imagens encontradas na pasta imgBase`);

      // Carrega preview da primeira imagem
      loadPreviewImage();
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

  isProcessing = true;
  const btn = document.getElementById('generateBtn');
  btn.disabled = true;
  btn.textContent = 'Processando...';

  // Mostra a barra de progresso
  showProgress();

  // Inicia o processamento das imagens
  processImagesSequentially(0);
};

function loadPreviewImage() {
  // Pega a primeira imagem da lista para preview
  if (imagesList.length === 0) {
    console.error('Nenhuma imagem encontrada para preview');
    return;
  }

  let firstImage = imagesList[0];
  console.log(`Carregando preview: ${firstImage.filename}`);

  let imageElement = document.getElementById('image');
  let imageUrl = '/imgBase/' + firstImage.filename;

  let img = new Image();

  img.onload = function () {
    console.log(`Preview carregado: ${firstImage.filename}`);
    imageElement.style = `background: url('${imageUrl}') no-repeat; background-size: contain; margin: 0 auto; background-position: center;`;
  };

  img.onerror = function () {
    console.error(`Erro ao carregar preview: ${firstImage.filename}`);
    // Se falhar, tenta a próxima imagem
    if (imagesList.length > 1) {
      imagesList.shift(); // Remove a primeira imagem
      loadPreviewImage(); // Tenta novamente
    }
  };

  img.src = imageUrl;
}

function processImagesSequentially(index) {
  // Verifica se ainda há imagens para processar
  if (index >= imagesList.length) {
    console.log("Todas as imagens foram processadas!");
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
  updateProgress(percentage, `Processando ${index + 1} de ${imagesList.length}`);

  console.log(`Processando imagem ${index + 1}/${imagesList.length}: ${filename}`);

  let imageElement = document.getElementById('image');
  let imageUrl = '/imgBase/' + filename;

  let img = new Image();

  img.onload = function () {
    console.log(`Imagem carregada: ${filename}`);
    imageElement.style = `background: url('${imageUrl}') no-repeat; background-size: contain; margin: 0 auto; background-position: center;`;

    // Agora que carregamos a imagem, processamos
    saveProcessedImage(filename, code, extension);
  };

  img.onerror = function () {
    console.error(`Erro ao carregar imagem: ${filename}`);
    // Se falhar, passa para a próxima imagem
    processImagesSequentially(index + 1);
  };

  img.src = imageUrl;

  function saveProcessedImage(originalFilename, code, extension) {
    console.log(`Preparando para processar ${originalFilename}`);

    html2canvas(document.getElementById("grid")).then(function (canvas) {
      console.log(`Canvas gerado para ${originalFilename}`);

      let ajax = new XMLHttpRequest();
      ajax.open("POST", "api/saveProcessedImage.php", true);
      ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      let params = "image=" + encodeURIComponent(canvas.toDataURL("image/jpeg", 0.9)) +
        "&originalFilename=" + encodeURIComponent(originalFilename) +
        "&code=" + encodeURIComponent(code) +
        "&extension=" + encodeURIComponent(extension);

      ajax.onreadystatechange = function () {
        if (this.readyState == 4) {
          if (this.status == 200) {
            console.log(`Imagem processada: ${originalFilename}`);
          } else {
            console.error(`Erro ao salvar ${originalFilename}: ${this.status} ${this.statusText}`);
          }
          // Independentemente do resultado, continua para o próximo item
          processImagesSequentially(index + 1);
        }
      };

      ajax.onerror = function () {
        console.error(`Erro de rede ao salvar ${originalFilename}`);
        // Em caso de erro, continua para o próximo item
        processImagesSequentially(index + 1);
      };

      ajax.send(params);
    }).catch(function (error) {
      console.error(`Erro ao gerar canvas para ${originalFilename}:`, error);
      // Em caso de erro no html2canvas, continua para o próximo item
      processImagesSequentially(index + 1);
    });
  }
}

// Função para converter as imagens da pasta .temp para WebP
function convertToWebp() {
  const qualitySlider = document.getElementById('webpQuality');
  const quality = qualitySlider ? qualitySlider.value : 80;

  console.log(`Iniciando conversão WebP com qualidade ${quality}%`);

  // Primeiro step: conversão
  callWebpConversion(quality, 'convert');
}

// Função para chamar a conversão WebP com step específico
function callWebpConversion(quality, step) {
  const ajax = new XMLHttpRequest();
  ajax.open("POST", "api/convertToWebp.php", true);
  ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

  const params = "quality=" + encodeURIComponent(quality) + "&step=" + encodeURIComponent(step);

  ajax.onreadystatechange = function () {
    if (this.readyState == 4) {
      if (this.status == 200) {
        try {
          const response = JSON.parse(this.responseText);

          if (response.success) {
            if (response.step === 'convert') {
              console.log(`Conversão WebP concluída! ${response.converted} arquivos convertidos`);
              updateProgress(96, 'Gerando thumbnails...');

              // Inicia a geração de thumbnails
              callWebpConversion(quality, 'thumbnail');
            } else if (response.step === 'thumbnail') {
              console.log(`Thumbnails gerados! ${response.generated} arquivos`);
              updateProgress(100, 'Processo concluído!');

              // Finaliza o processo
              finishProcess();
            }

            if (response.errors && response.errors.length > 0) {
              console.warn('Alguns arquivos apresentaram erro:', response.errors);
            }
          } else {
            console.error(`Erro no step ${step}:`, response.message);
            updateProgress(100, `Erro no ${step === 'convert' ? 'conversão' : 'thumbnails'}`);
            finishProcess();
          }
        } catch (e) {
          console.error(`Erro ao processar resposta do step ${step}:`, e);
          updateProgress(100, `Erro no ${step === 'convert' ? 'conversão' : 'thumbnails'}`);
          finishProcess();
        }
      } else {
        console.error(`Erro HTTP no step ${step}: ${this.status} ${this.statusText}`);
        updateProgress(100, `Erro no ${step === 'convert' ? 'conversão' : 'thumbnails'}`);
        finishProcess();
      }
    }
  };

  ajax.onerror = function () {
    console.error(`Erro de rede no step ${step}`);
    updateProgress(100, `Erro no ${step === 'convert' ? 'conversão' : 'thumbnails'}`);
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
  }, 2000);
}