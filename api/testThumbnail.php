<?php
// Script de teste direto para thumbnails
header('Content-Type: application/json');

// Simula a chamada como o JavaScript faria
$_POST['quality'] = 80;
$_POST['step'] = 'thumbnail';

include 'convertToWebp.php';
?>
