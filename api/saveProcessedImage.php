<?php
// Recebe os dados via POST
$imageData = $_POST['image'] ?? '';
$originalFilename = $_POST['originalFilename'] ?? '';
$code = $_POST['code'] ?? '';
$extension = $_POST['extension'] ?? '';

if (empty($imageData) || empty($originalFilename)) {
    http_response_code(400);
    echo "Dados insuficientes fornecidos";
    exit;
}

// Remove o prefixo data:image da string base64
$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$data = base64_decode($imageData);

if ($data === false) {
    http_response_code(400);
    echo "Erro ao decodificar imagem";
    exit;
}

// Define o diretório de destino (pasta temporária)
$targetDir = '../imgResult/.temp/';

// Verifica se a pasta existe, se não, cria
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        http_response_code(500);
        echo "Erro ao criar diretório imgResult/.temp";
        exit;
    }
}

// Função para gerar UUID v4
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Gera novo UUID para o arquivo processado
$newUUID = generateUUID();
// HTML2Canvas sempre gera JPEG, então sempre usar extensão .jpg
$newFilename = $newUUID . '.jpg';

// Caminho completo do arquivo
$targetPath = $targetDir . $newFilename;

// Salva a imagem com nome UUID em imgResult/.temp
if (file_put_contents($targetPath, $data) !== false) {
    
    // Registra a conversão no arquivo dePara.txt (na pasta temp)
    $logFile = $targetDir . 'dePara.txt';
    
    // Extrai o código base do nome original (remove extensão)
    $baseCode = pathinfo($originalFilename, PATHINFO_FILENAME);
    $finalWebpName = str_replace('.jpg', '.webp', $newFilename);
    
    // Formato: codigo;originalFilename;uuid;finalWebpName
    $logEntry = $baseCode . ';' . $originalFilename . ';' . $newUUID . ';' . $finalWebpName . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    echo "Imagem processada e salva: " . $originalFilename . " -> " . $newFilename;
    
    // Log da operação
    error_log("SAVE_PROCESSED_IMAGE: {$originalFilename} -> {$newFilename} (UUID em imgResult/.temp)");
} else {
    http_response_code(500);
    echo "Erro ao salvar imagem processada";
}
?>
