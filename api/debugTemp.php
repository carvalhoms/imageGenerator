<?php
// Script de debug para investigar o estado da pasta .temp
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG PASTA .TEMP ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

$baseDir = dirname(__DIR__) . '/';
$tempDir = $baseDir . 'imgResult/.temp/';
$imgBaseDir = $baseDir . 'imgBase/';
$resultDir = $baseDir . 'imgResult/';
$largeDir = $baseDir . 'imgResult/imgLarge/';

echo "Diretórios:\n";
echo "- Base: $baseDir\n";
echo "- imgBase: $imgBaseDir\n";
echo "- .temp: $tempDir\n";
echo "- Result: $resultDir\n";
echo "- imgLarge: $largeDir\n\n";

// Verifica pasta imgBase
echo "=== PASTA imgBase ===\n";
if (is_dir($imgBaseDir)) {
    $imgBaseFiles = glob($imgBaseDir . '*');
    echo "Arquivos encontrados: " . count($imgBaseFiles) . "\n";
    foreach ($imgBaseFiles as $file) {
        if (is_file($file)) {
            echo "- " . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
    }
} else {
    echo "ERRO: Pasta imgBase não existe\n";
}

echo "\n=== PASTA .temp ===\n";
if (is_dir($tempDir)) {
    $tempFiles = glob($tempDir . '*');
    echo "Arquivos encontrados: " . count($tempFiles) . "\n";
    foreach ($tempFiles as $file) {
        if (is_file($file)) {
            echo "- " . basename($file) . " (" . filesize($file) . " bytes)\n";
        } elseif (is_dir($file)) {
            echo "- [DIR] " . basename($file) . "\n";
        }
    }

    // Verifica arquivo dePara.txt
    $deParaFile = $tempDir . 'dePara.txt';
    if (file_exists($deParaFile)) {
        echo "\n--- Arquivo dePara.txt ---\n";
        $content = file_get_contents($deParaFile);
        $lines = explode("\n", trim($content));
        echo "Total de linhas: " . count($lines) . "\n";
        foreach ($lines as $i => $line) {
            if (trim($line) !== '') {
                echo "[$i] $line\n";
            }
        }
    }
} else {
    echo "Pasta .temp não existe\n";
}

echo "\n=== PASTA imgResult ===\n";
if (is_dir($resultDir)) {
    $resultFiles = glob($resultDir . '*');
    echo "Arquivos encontrados: " . count($resultFiles) . "\n";
    foreach ($resultFiles as $file) {
        if (is_file($file)) {
            echo "- " . basename($file) . " (" . filesize($file) . " bytes)\n";
        } elseif (is_dir($file)) {
            echo "- [DIR] " . basename($file) . "\n";
        }
    }
} else {
    echo "Pasta imgResult não existe\n";
}

echo "\n=== PASTA imgLarge ===\n";
if (is_dir($largeDir)) {
    $largeFiles = glob($largeDir . '*');
    echo "Arquivos encontrados: " . count($largeFiles) . "\n";
    foreach ($largeFiles as $file) {
        if (is_file($file)) {
            echo "- " . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
    }
} else {
    echo "Pasta imgLarge não existe\n";
}

// Verifica permissões
echo "\n=== PERMISSÕES ===\n";
$dirs = [$baseDir, $imgBaseDir, $tempDir, $resultDir, $largeDir];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "- " . basename($dir) . ": $perms\n";
    }
}

echo "\n=== LOG RECENTE ===\n";
$logEntries = [];
$logFiles = ['/var/log/apache2/error.log', '/var/log/httpd/error_log', ini_get('error_log')];

foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile) && is_readable($logFile)) {
        echo "Verificando: $logFile\n";
        $lines = file($logFile);
        $recentLines = array_slice($lines, -50); // Últimas 50 linhas

        foreach ($recentLines as $line) {
            if (strpos($line, 'SAVE_PROCESSED_IMAGE') !== false ||
                strpos($line, 'CONVERT_TO_WEBP') !== false) {
                echo $line;
            }
        }
        break; // Para no primeiro log encontrado
    }
}

echo "\n=== FIM DEBUG ===\n";
?>