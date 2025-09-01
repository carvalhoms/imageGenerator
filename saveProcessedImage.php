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

// Define o diretório de destino
$targetDir = './imgEdit/';

// Verifica se a pasta existe, se não, cria
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        http_response_code(500);
        echo "Erro ao criar diretório";
        exit;
    }
}

// Gera o nome do arquivo processado
// Mantém o nome original (sobrescreve o arquivo)
$processedFilename = $originalFilename;

// Caminho completo do arquivo
$targetPath = $targetDir . $processedFilename;

// Salva a imagem (sobrescrevendo o original)
if (file_put_contents($targetPath, $data) !== false) {
    echo "Imagem processada e salva: " . $processedFilename;
    
    // Log da operação
    error_log("Imagem processada (sobrescrita): {$originalFilename}");
} else {
    http_response_code(500);
    echo "Erro ao salvar imagem processada";
}
?>
