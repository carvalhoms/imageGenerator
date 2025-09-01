<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$imagesDir = '../imgBase/';

// Verifica se a pasta existe
if (!is_dir($imagesDir)) {
    echo json_encode(['error' => 'Pasta imgBase não encontrada']);
    exit;
}

// Extensões de imagem permitidas
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

// Lista todos os arquivos da pasta
$files = scandir($imagesDir);
$imageFiles = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }
    
    $filePath = $imagesDir . $file;
    
    // Verifica se é um arquivo (não diretório)
    if (is_file($filePath)) {
        $pathInfo = pathinfo($file);
        $extension = strtolower($pathInfo['extension'] ?? '');
        
        // Verifica se tem extensão válida
        if (in_array($extension, $allowedExtensions)) {
            $imageFiles[] = [
                'filename' => $file,
                'code' => $pathInfo['filename'], // Nome sem extensão
                'extension' => $extension,
                'fullPath' => $file
            ];
        }
    }
}

// Ordena por nome do arquivo
usort($imageFiles, function($a, $b) {
    return strcmp($a['filename'], $b['filename']);
});

echo json_encode([
    'success' => true,
    'count' => count($imageFiles),
    'images' => $imageFiles
]);
?>
