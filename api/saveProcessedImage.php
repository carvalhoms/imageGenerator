<?php
// Log início do processamento
error_log("=== SAVE_PROCESSED_IMAGE: Iniciando processamento ===");

// Recebe os dados via POST
$imageData = $_POST['image'] ?? '';
$originalFilename = $_POST['originalFilename'] ?? '';
$code = $_POST['code'] ?? '';
$extension = $_POST['extension'] ?? '';

error_log("SAVE_PROCESSED_IMAGE: Dados recebidos - Filename: $originalFilename, Code: $code, Extension: $extension");
error_log("SAVE_PROCESSED_IMAGE: Tamanho dos dados da imagem: " . strlen($imageData) . " caracteres");

if (empty($imageData) || empty($originalFilename)) {
    error_log("SAVE_PROCESSED_IMAGE: ERRO - Dados insuficientes fornecidos");
    http_response_code(400);
    echo "Dados insuficientes fornecidos";
    exit;
}

// Remove o prefixo data:image da string base64
$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
error_log("SAVE_PROCESSED_IMAGE: Dados preparados, tamanho após limpeza: " . strlen($imageData) . " caracteres");

$data = base64_decode($imageData);

if ($data === false) {
    error_log("SAVE_PROCESSED_IMAGE: ERRO - Falha ao decodificar base64");
    http_response_code(400);
    echo "Erro ao decodificar imagem";
    exit;
}

error_log("SAVE_PROCESSED_IMAGE: Base64 decodificado com sucesso, tamanho binário: " . strlen($data) . " bytes");

// Define o diretório de destino (pasta temporária)
$targetDir = '../imgResult/.temp/';
error_log("SAVE_PROCESSED_IMAGE: Diretório de destino: $targetDir");

// Verifica se a pasta existe, se não, cria
if (!is_dir($targetDir)) {
    error_log("SAVE_PROCESSED_IMAGE: Pasta .temp não existe, criando...");
    if (!mkdir($targetDir, 0755, true)) {
        error_log("SAVE_PROCESSED_IMAGE: ERRO - Falha ao criar diretório imgResult/.temp");
        http_response_code(500);
        echo "Erro ao criar diretório imgResult/.temp";
        exit;
    }
    error_log("SAVE_PROCESSED_IMAGE: Pasta .temp criada com sucesso");
} else {
    error_log("SAVE_PROCESSED_IMAGE: Pasta .temp já existe");
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

error_log("SAVE_PROCESSED_IMAGE: UUID gerado: $newUUID");
error_log("SAVE_PROCESSED_IMAGE: Novo filename: $newFilename");
error_log("SAVE_PROCESSED_IMAGE: Caminho completo: $targetPath");

// Salva a imagem com nome UUID em imgResult/.temp
$bytesWritten = file_put_contents($targetPath, $data);

if ($bytesWritten !== false) {
    error_log("SAVE_PROCESSED_IMAGE: Arquivo salvo com sucesso - $bytesWritten bytes escritos");

    // Registra a conversão no arquivo dePara.txt (na pasta temp)
    $logFile = $targetDir . 'dePara.txt';

    // Extrai o código base do nome original (remove extensão)
    $baseCode = pathinfo($originalFilename, PATHINFO_FILENAME);
    $finalWebpName = str_replace('.jpg', '.webp', $newFilename);

    // Formato: codigo;originalFilename;uuid;finalWebpName
    $logEntry = $baseCode . ';' . $originalFilename . ';' . $newUUID . ';' . $finalWebpName . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    error_log("SAVE_PROCESSED_IMAGE: Entrada adicionada ao dePara.txt: $logEntry");

    echo "Imagem processada e salva: " . $originalFilename . " -> " . $newFilename;

    // Log da operação
    error_log("SAVE_PROCESSED_IMAGE: SUCESSO - {$originalFilename} -> {$newFilename} (UUID em imgResult/.temp)");
} else {
    error_log("SAVE_PROCESSED_IMAGE: ERRO - Falha ao escrever arquivo: $targetPath");
    error_log("SAVE_PROCESSED_IMAGE: Permissões do diretório: " . substr(sprintf('%o', fileperms($targetDir)), -4));
    http_response_code(500);
    echo "Erro ao salvar imagem processada";
}
?>
