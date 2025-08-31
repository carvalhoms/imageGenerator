<?php
$image = $_POST["image"];
$code = $_POST["code"];
$newCode = $_POST["newCode"];
$extension = $_POST["extension"];

$image = explode(";", $image)[1];
$image = explode(",", $image)[1];
$image = str_replace(" ", "+", $image);
$image = base64_decode($image);

// Cria o diretório se não existir
if (!file_exists("imgNew")) {
    mkdir("imgNew", 0777, true);
}

$saved = file_put_contents("imgNew/" . $newCode . "." . $extension, $image);

if ($saved !== false) {
    echo "Imagem salva com sucesso: " . $newCode . "." . $extension;
} else {
    echo "Erro ao salvar a imagem: " . $newCode . "." . $extension;
}
?>