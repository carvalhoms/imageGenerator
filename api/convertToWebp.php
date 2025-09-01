<?php
// Script para converter imagens da pasta .temp para WebP
header('Content-Type: application/json');

// Ativa logs detalhados
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("CONVERT_TO_WEBP: Iniciando script de conversão");

// Recebe a qualidade via POST (padrão 80)
$quality = isset($_POST['quality']) ? intval($_POST['quality']) : 80;
$step = isset($_POST['step']) ? $_POST['step'] : 'convert'; // 'convert' ou 'thumbnail'

// Valida a qualidade (entre 1 e 100)
if ($quality < 1 || $quality > 100) {
    $quality = 80;
}

error_log("CONVERT_TO_WEBP: Qualidade definida: $quality, Step: $step");

// Define os diretórios
$baseDir = dirname(__DIR__) . '/';
$tempDir = $baseDir . 'imgResult/.temp/';
$resultDir = $baseDir . 'imgResult/';
$largeDir = $baseDir . 'imgResult/imgLarge/';
$thumbnailDir = $baseDir . 'imgResult/imgThumbnail/';

error_log("CONVERT_TO_WEBP: Diretórios - temp: $tempDir, result: $resultDir, large: $largeDir, thumbnail: $thumbnailDir");

// Verifica se a pasta result existe, se não, cria
if (!is_dir($resultDir)) {
    if (!mkdir($resultDir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar diretório imgResult'
        ]);
        exit;
    }
}

// Verifica se a pasta imgLarge existe, se não, cria
if (!is_dir($largeDir)) {
    if (!mkdir($largeDir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar diretório imgResult/imgLarge'
        ]);
        exit;
    }
}

// Verifica se a pasta imgThumbnail existe, se não, cria
if (!is_dir($thumbnailDir)) {
    if (!mkdir($thumbnailDir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar diretório imgResult/imgThumbnail'
        ]);
        exit;
    }
}

// Função para gerar thumbnail
function generateThumbnail($sourcePath, $destinationPath, $quality) {
    // Dimensões do thumbnail: 236x200 (proporcional a 768x650)
    $thumbWidth = 236;
    $thumbHeight = 200;
    
    // Carrega a imagem WebP original
    $sourceImage = imagecreatefromwebp($sourcePath);
    if (!$sourceImage) {
        return false;
    }
    
    // Cria uma nova imagem para o thumbnail
    $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // Redimensiona a imagem
    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    
    imagecopyresampled(
        $thumbImage, $sourceImage,
        0, 0, 0, 0,
        $thumbWidth, $thumbHeight,
        $sourceWidth, $sourceHeight
    );
    
    // Salva como WebP
    $result = imagewebp($thumbImage, $destinationPath, $quality);
    
    // Libera memória
    imagedestroy($sourceImage);
    imagedestroy($thumbImage);
    
    return $result;
}

// Verifica qual step executar
if ($step === 'thumbnail') {
    // Gera thumbnails das imagens em imgLarge
    $largeFiles = glob($largeDir . '*.webp');
    $thumbnailFiles = [];
    $errors = [];
    
    error_log("CONVERT_TO_WEBP: Gerando thumbnails para " . count($largeFiles) . " arquivos");
    
    foreach ($largeFiles as $largeFilePath) {
        $fileName = basename($largeFilePath);
        $thumbnailPath = $thumbnailDir . $fileName;
        
        error_log("CONVERT_TO_WEBP: Gerando thumbnail para $fileName");
        
        if (generateThumbnail($largeFilePath, $thumbnailPath, $quality)) {
            $thumbnailFiles[] = $fileName;
            error_log("CONVERT_TO_WEBP: Thumbnail gerado: $fileName");
        } else {
            $errors[] = "Erro ao gerar thumbnail: $fileName";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Thumbnails gerados!',
        'step' => 'thumbnail',
        'generated' => count($thumbnailFiles),
        'files' => $thumbnailFiles,
        'errors' => $errors,
        'quality' => $quality
    ]);
    exit;
}

// Step de conversão - verifica se a pasta temp existe
if (!is_dir($tempDir)) {
    echo json_encode([
        'success' => false,
        'message' => 'Pasta .temp não encontrada'
    ]);
    exit;
}

// Lista arquivos na pasta temp (step de conversão)
$files = glob($tempDir . '*');
$convertedFiles = [];
$errors = [];

error_log("CONVERT_TO_WEBP: Arquivos encontrados na pasta temp: " . count($files));

if (empty($files)) {
    error_log("CONVERT_TO_WEBP: Nenhum arquivo encontrado na pasta temp");
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo encontrado na pasta .temp'
    ]);
    exit;
}

foreach ($files as $filePath) {
    // Pula diretórios e o arquivo dePara.txt
    if (is_dir($filePath) || basename($filePath) === 'dePara.txt') {
        continue;
    }
    
    $fileName = basename($filePath);
    $pathInfo = pathinfo($fileName);
    $baseName = $pathInfo['filename'];
    $extension = strtolower($pathInfo['extension']);
    
    error_log("CONVERT_TO_WEBP: Processando $fileName (qualidade: $quality%)");
    
    // Verifica se é uma imagem suportada
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
        $errors[] = "Formato não suportado: $fileName";
        continue;
    }
    
    try {
        // Carrega a imagem baseado na extensão
        $image = false;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                // Tenta PNG primeiro, se falhar tenta JPEG (HTML2Canvas pode salvar JPEG com extensão PNG)
                $image = @imagecreatefrompng($filePath);
                if (!$image) {
                    $image = @imagecreatefromjpeg($filePath);
                }
                break;
            case 'webp':
                $image = imagecreatefromwebp($filePath);
                break;
        }
        
        if (!$image) {
            $errors[] = "Erro ao carregar imagem: $fileName";
            continue;
        }
        
        // Define o nome do arquivo WebP (salva em imgLarge)
        $webpFileName = $baseName . '.webp';
        $webpPath = $largeDir . $webpFileName;
        
        // Converte para WebP
        if (imagewebp($image, $webpPath, $quality)) {
            $convertedFiles[] = $webpFileName;
            error_log("CONVERT_TO_WEBP: Convertido $fileName -> imgLarge/$webpFileName");
            
            // Remove o arquivo original da pasta temp
            unlink($filePath);
        } else {
            $errors[] = "Erro ao converter para WebP: $fileName";
        }
        
        // Libera a memória
        imagedestroy($image);
        
    } catch (Exception $e) {
        $errors[] = "Erro ao processar $fileName: " . $e->getMessage();
    }
}

// Move o arquivo dePara.txt e atualiza para imgLarge
$deParaTemp = $tempDir . 'dePara.txt';
$deParaResult = $resultDir . 'dePara.txt';

if (file_exists($deParaTemp)) {
    // Lê o conteúdo e atualiza as extensões para .webp em imgLarge
    $content = file_get_contents($deParaTemp);
    $lines = explode("\n", $content);
    $updatedLines = [];
    
    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $parts = explode(';', $line);
            if (count($parts) === 2) {
                $originalName = $parts[0];
                $processedName = $parts[1];
                
                // Muda a extensão para .webp e adiciona o caminho imgLarge
                $pathInfo = pathinfo($processedName);
                $webpName = 'imgLarge/' . $pathInfo['filename'] . '.webp';
                
                $updatedLines[] = $originalName . ';' . $webpName;
            }
        }
    }
    
    // Salva o arquivo atualizado na pasta result
    file_put_contents($deParaResult, implode("\n", $updatedLines) . "\n");
    
    // Remove da pasta temp
    unlink($deParaTemp);
}

// Remove a pasta .temp se estiver vazia
$tempFiles = glob($tempDir . '*');
if (empty($tempFiles)) {
    rmdir($tempDir);
}

echo json_encode([
    'success' => true,
    'message' => 'Conversão concluída!',
    'step' => 'convert',
    'converted' => count($convertedFiles),
    'files' => $convertedFiles,
    'errors' => $errors,
    'quality' => $quality
]);
?>
