<?php
// Script para converter imagens da pasta .temp para WebP
header('Content-Type: application/json');

// Aumenta o tempo limite para 5 minutos (300 segundos)
set_time_limit(300);
ini_set('max_execution_time', 300);

// Aumenta o limite de memória
ini_set('memory_limit', '256M');

// Ativa logs detalhados mas não exibe erros na saída (para manter JSON válido)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Função para enviar resposta JSON em caso de erro fatal
function sendErrorResponse($message) {
    echo json_encode([
        'success' => false,
        'message' => $message,
        'error' => true
    ]);
    exit;
}

// Registra handler para erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        // Limpa qualquer saída anterior
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        sendErrorResponse('Erro fatal durante o processamento: ' . $error['message']);
    }
});

error_log("CONVERT_TO_WEBP: Iniciando script de conversão");

// Recebe a qualidade via POST (padrão 80)
$quality = isset($_POST['quality']) ? intval($_POST['quality']) : 80;
$step = isset($_POST['step']) ? $_POST['step'] : 'convert'; // 'convert' ou 'thumbnail'
$batchSize = isset($_POST['batchSize']) ? intval($_POST['batchSize']) : 10; // Processa 10 arquivos por vez
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0; // Offset para paginação

// Valida a qualidade (entre 1 e 100)
if ($quality < 1 || $quality > 100) {
    $quality = 80;
}

// Valida o batch size (entre 1 e 50)
if ($batchSize < 1 || $batchSize > 50) {
    $batchSize = 10;
}

error_log("CONVERT_TO_WEBP: Qualidade: $quality, Step: $step, BatchSize: $batchSize, Offset: $offset");

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
    try {
        // Dimensões do thumbnail: 236x200 (proporcional a 768x650)
        $thumbWidth = 236;
        $thumbHeight = 200;
        
        // Carrega a imagem WebP original
        $sourceImage = imagecreatefromwebp($sourcePath);
        if (!$sourceImage) {
            error_log("THUMBNAIL: Erro ao carregar imagem: $sourcePath");
            return false;
        }
        
        // Cria uma nova imagem para o thumbnail
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        if (!$thumbImage) {
            imagedestroy($sourceImage);
            error_log("THUMBNAIL: Erro ao criar imagem thumbnail");
            return false;
        }
        
        // Redimensiona a imagem
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        
        $resizeResult = imagecopyresampled(
            $thumbImage, $sourceImage,
            0, 0, 0, 0,
            $thumbWidth, $thumbHeight,
            $sourceWidth, $sourceHeight
        );
        
        if (!$resizeResult) {
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);
            error_log("THUMBNAIL: Erro ao redimensionar imagem: $sourcePath");
            return false;
        }
        
        // Salva como WebP
        $result = imagewebp($thumbImage, $destinationPath, $quality);
        
        // Libera memória imediatamente
        imagedestroy($sourceImage);
        imagedestroy($thumbImage);
        
        // Força limpeza de memória
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("THUMBNAIL: Exception ao gerar thumbnail: " . $e->getMessage());
        return false;
    } catch (Error $e) {
        error_log("THUMBNAIL: Error fatal ao gerar thumbnail: " . $e->getMessage());
        return false;
    }
}

// Verifica qual step executar
if ($step === 'thumbnail') {
    // Gera thumbnails das imagens em imgLarge
    $largeFiles = glob($largeDir . '*.webp');
    $totalFiles = count($largeFiles);
    
    // Aplica paginação
    $filesSlice = array_slice($largeFiles, $offset, $batchSize);
    $thumbnailFiles = [];
    $errors = [];
    
    error_log("CONVERT_TO_WEBP: Gerando thumbnails - Total: $totalFiles, Offset: $offset, Lote: " . count($filesSlice));
    
    foreach ($filesSlice as $largeFilePath) {
        $fileName = basename($largeFilePath);
        $thumbnailPath = $thumbnailDir . $fileName;
        
        error_log("CONVERT_TO_WEBP: Gerando thumbnail para $fileName");
        
        // Força limpeza de memória antes de cada thumbnail
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $memoryBefore = memory_get_usage(true);
        error_log("THUMBNAIL: Memória antes de $fileName: " . round($memoryBefore/1024/1024, 2) . "MB");
        
        if (generateThumbnail($largeFilePath, $thumbnailPath, $quality)) {
            $thumbnailFiles[] = $fileName;
            error_log("CONVERT_TO_WEBP: Thumbnail gerado: $fileName");
        } else {
            $errors[] = "Erro ao gerar thumbnail: $fileName";
        }
        
        $memoryAfter = memory_get_usage(true);
        error_log("THUMBNAIL: Memória após $fileName: " . round($memoryAfter/1024/1024, 2) . "MB");
    }
    
    $processedCount = $offset + count($filesSlice);
    $isComplete = $processedCount >= $totalFiles;
    
    echo json_encode([
        'success' => true,
        'message' => $isComplete ? 'Thumbnails gerados!' : 'Lote de thumbnails processado',
        'step' => 'thumbnail',
        'generated' => count($thumbnailFiles),
        'files' => $thumbnailFiles,
        'errors' => $errors,
        'quality' => $quality,
        'total' => $totalFiles,
        'processed' => $processedCount,
        'isComplete' => $isComplete,
        'nextOffset' => $isComplete ? null : $processedCount
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
$totalFiles = count($files);

// Remove diretórios e arquivo dePara.txt da contagem
$files = array_filter($files, function($filePath) {
    return !is_dir($filePath) && basename($filePath) !== 'dePara.txt';
});
$totalFiles = count($files);

// Aplica paginação
$filesSlice = array_slice($files, $offset, $batchSize);
$convertedFiles = [];
$errors = [];

error_log("CONVERT_TO_WEBP: Conversão - Total: $totalFiles, Offset: $offset, Lote: " . count($filesSlice));

if (empty($files)) {
    error_log("CONVERT_TO_WEBP: Nenhum arquivo encontrado na pasta temp");
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo encontrado na pasta .temp'
    ]);
    exit;
}

foreach ($filesSlice as $filePath) {
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
        // Força limpeza de memória antes de processar cada imagem
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Verifica uso de memória antes do processamento
        $memoryBefore = memory_get_usage(true);
        error_log("CONVERT_TO_WEBP: Memória antes de $fileName: " . round($memoryBefore/1024/1024, 2) . "MB");
        
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
        
        // Libera a memória imediatamente
        imagedestroy($image);
        $image = null;
        
        // Força limpeza de memória após cada imagem
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $memoryAfter = memory_get_usage(true);
        error_log("CONVERT_TO_WEBP: Memória após $fileName: " . round($memoryAfter/1024/1024, 2) . "MB");
        
    } catch (Exception $e) {
        $errors[] = "Erro ao processar $fileName: " . $e->getMessage();
        error_log("CONVERT_TO_WEBP: Exception ao processar $fileName: " . $e->getMessage());
    } catch (Error $e) {
        $errors[] = "Erro fatal ao processar $fileName: " . $e->getMessage();
        error_log("CONVERT_TO_WEBP: Error fatal ao processar $fileName: " . $e->getMessage());
    }
}

$processedCount = $offset + count($filesSlice);
$isComplete = $processedCount >= $totalFiles;

// Move o arquivo dePara.txt apenas no último lote
if ($isComplete) {
    error_log("CONVERT_TO_WEBP: Processando arquivo de índice dePara.txt...");
    
    // Move o arquivo dePara.txt e atualiza para imgLarge
    $deParaTemp = $tempDir . 'dePara.txt';
    $deParaResult = $resultDir . 'dePara.txt';

    if (file_exists($deParaTemp)) {
        error_log("CONVERT_TO_WEBP: Atualizando arquivo dePara.txt com caminhos WebP...");
        
        // Lê o conteúdo e atualiza as extensões para .webp em imgLarge
        $content = file_get_contents($deParaTemp);
        $lines = explode("\n", $content);
        $updatedLines = [];
        
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $parts = explode(';', $line);
                if (count($parts) === 4) {
                    // Novo formato: codigo;originalFilename;uuid;finalWebpName
                    $codigo = $parts[0];
                    $originalFilename = $parts[1];
                    $uuid = $parts[2];
                    $finalWebpName = $parts[3];
                    
                    // Atualiza para incluir o caminho imgLarge
                    $webpPath = 'imgLarge/' . $uuid . '.webp';
                    
                    $updatedLines[] = $codigo . ';' . $originalFilename . ';' . $uuid . ';' . $webpPath;
                }
            }
        }
        
        // Salva o arquivo atualizado na pasta result
        file_put_contents($deParaResult, implode("\n", $updatedLines) . "\n");
        error_log("CONVERT_TO_WEBP: Arquivo dePara.txt salvo com " . count($updatedLines) . " entradas");
        
        // Remove da pasta temp
        unlink($deParaTemp);
    }

    // Remove a pasta .temp se estiver vazia
    $tempFiles = glob($tempDir . '*');
    if (empty($tempFiles)) {
        rmdir($tempDir);
        error_log("CONVERT_TO_WEBP: Pasta .temp removida");
    }
}

echo json_encode([
    'success' => true,
    'message' => $isComplete ? 'Conversão concluída!' : 'Lote de conversão processado',
    'step' => 'convert',
    'converted' => count($convertedFiles),
    'files' => $convertedFiles,
    'errors' => $errors,
    'quality' => $quality,
    'total' => $totalFiles,
    'processed' => $processedCount,
    'isComplete' => $isComplete,
    'nextOffset' => $isComplete ? null : $processedCount,
    'processingIndex' => $isComplete // indica se está processando arquivo de índice
]);
?>
