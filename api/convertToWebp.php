<?php
// Script para converter imagens da pasta .temp para WebP
header('Content-Type: application/json');

// Aumenta o tempo limite para 10 minutos
set_time_limit(600);
ini_set('max_execution_time', 600);
ini_set('memory_limit', '512M');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

error_log("CONVERT_TO_WEBP: Iniciando conversão simplificada");

// Recebe a qualidade via POST (padrão 80)
$quality = isset($_POST['quality']) ? intval($_POST['quality']) : 80;

// Valida a qualidade (entre 1 e 100)
if ($quality < 1 || $quality > 100) {
    $quality = 80;
}

error_log("CONVERT_TO_WEBP: Qualidade: $quality");

// Define os diretórios
$baseDir = dirname(__DIR__) . '/';
$tempDir = $baseDir . 'imgResult/.temp/';
$resultDir = $baseDir . 'imgResult/';
$largeDir = $baseDir . 'imgResult/imgLarge/';
$thumbnailDir = $baseDir . 'imgResult/imgThumbnail/';

// Verifica se a pasta temp existe
if (!is_dir($tempDir)) {
    echo json_encode([
        'success' => false,
        'message' => 'Pasta .temp não encontrada'
    ]);
    exit;
}

// Cria diretórios necessários
if (!is_dir($resultDir) && !mkdir($resultDir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório imgResult']);
    exit;
}

if (!is_dir($largeDir) && !mkdir($largeDir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório imgLarge']);
    exit;
}

if (!is_dir($thumbnailDir) && !mkdir($thumbnailDir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório imgThumbnail']);
    exit;
}

// Lista TODOS os arquivos .jpg na pasta temp
$files = glob($tempDir . '*.jpg');
$totalFiles = count($files);
$convertedFiles = [];
$thumbnailFiles = [];
$errors = [];

error_log("CONVERT_TO_WEBP: Total de arquivos para processar: $totalFiles");

if ($totalFiles == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo .jpg encontrado na pasta .temp'
    ]);
    exit;
}

// PROCESSA TODOS OS ARQUIVOS DE UMA VEZ
foreach ($files as $index => $filePath) {
    $fileName = basename($filePath);
    $pathInfo = pathinfo($fileName);
    $baseName = $pathInfo['filename'];

    error_log("CONVERT_TO_WEBP: Processando ($index/$totalFiles): $fileName");

    try {
        // Carrega a imagem
        $image = imagecreatefromjpeg($filePath);

        if (!$image) {
            $errors[] = "Erro ao carregar: $fileName";
            continue;
        }

        // Define o nome do arquivo WebP
        $webpFileName = $baseName . '.webp';
        $webpPath = $largeDir . $webpFileName;

        // Converte para WebP
        if (imagewebp($image, $webpPath, $quality)) {
            $convertedFiles[] = $webpFileName;
            error_log("CONVERT_TO_WEBP: ✅ Convertido: $fileName → $webpFileName");

            // Gera thumbnail imediatamente
            $thumbPath = $thumbnailDir . $webpFileName;
            if (generateThumbnail($webpPath, $thumbPath, $quality)) {
                $thumbnailFiles[] = $webpFileName;
                error_log("CONVERT_TO_WEBP: ✅ Thumbnail: $webpFileName");
            } else {
                $errors[] = "Erro ao gerar thumbnail: $webpFileName";
            }
        } else {
            $errors[] = "Erro ao converter: $fileName";
        }

        // Libera memória
        imagedestroy($image);

        // Força limpeza de memória
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

    } catch (Exception $e) {
        $errors[] = "Exception em $fileName: " . $e->getMessage();
        error_log("CONVERT_TO_WEBP: Exception: $fileName - " . $e->getMessage());
    }
}

// Processa arquivo dePara.txt
$deParaTemp = $tempDir . 'dePara.txt';
$deParaResult = $resultDir . 'dePara.txt';

if (file_exists($deParaTemp)) {
    error_log("CONVERT_TO_WEBP: Processando dePara.txt");

    $content = file_get_contents($deParaTemp);
    $lines = explode("\n", $content);
    $updatedLines = [];

    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $parts = explode(';', $line);
            if (count($parts) === 4) {
                $codigo = $parts[0];
                $originalFilename = $parts[1];
                $uuid = $parts[2];

                // Atualiza para incluir o caminho imgLarge
                $webpPath = 'imgLarge/' . $uuid . '.webp';
                $updatedLines[] = $codigo . ';' . $originalFilename . ';' . $uuid . ';' . $webpPath;
            }
        }
    }

    // Salva o arquivo atualizado
    file_put_contents($deParaResult, implode("\n", $updatedLines) . "\n");
    error_log("CONVERT_TO_WEBP: dePara.txt salvo com " . count($updatedLines) . " entradas");
}

// AGORA SIM - REMOVE TUDO DA PASTA .TEMP
error_log("CONVERT_TO_WEBP: Removendo pasta .temp");
$tempFiles = glob($tempDir . '*');
foreach ($tempFiles as $tempFile) {
    if (is_file($tempFile)) {
        unlink($tempFile);
    }
}
rmdir($tempDir);
error_log("CONVERT_TO_WEBP: Pasta .temp removida completamente");

// Função para gerar thumbnail
function generateThumbnail($sourcePath, $destinationPath, $quality) {
    try {
        $thumbWidth = 236;
        $thumbHeight = 200;

        $sourceImage = imagecreatefromwebp($sourcePath);
        if (!$sourceImage) return false;

        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        if (!$thumbImage) {
            imagedestroy($sourceImage);
            return false;
        }

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
            return false;
        }

        $result = imagewebp($thumbImage, $destinationPath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        return $result;

    } catch (Exception $e) {
        error_log("THUMBNAIL: Exception: " . $e->getMessage());
        return false;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Conversão concluída!',
    'totalFiles' => $totalFiles,
    'converted' => count($convertedFiles),
    'thumbnails' => count($thumbnailFiles),
    'errors' => $errors,
    'quality' => $quality
]);
?>