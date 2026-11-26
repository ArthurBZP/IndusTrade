<?php
// Recebe a foto de perfil da empresa logada (ou o pedido de remoção) e grava em uploads/perfil/.
require_once __DIR__ . "/protect.php";
require_once __DIR__ . "/foto_lib.php";

function voltarPerfil($msg) {
    header("Location: perfil.php?msg=" . $msg);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") voltarPerfil("");
if (!csrf_valido()) voltarPerfil("csrf");

$cnpj = preg_replace('/\D/', '', (string)$_SESSION["id"]);

// Remover foto
if (($_POST["acao"] ?? "") === "remover") {
    fotoRemover($cnpj);
    voltarPerfil("foto_removida");
}

// Enviar/trocar foto
$f = $_FILES["foto"] ?? null;
if (!$f || $f["error"] === UPLOAD_ERR_NO_FILE) voltarPerfil("foto_invalida");
if ($f["error"] === UPLOAD_ERR_INI_SIZE || $f["error"] === UPLOAD_ERR_FORM_SIZE || $f["size"] > FOTO_MAX) voltarPerfil("foto_grande");
if ($f["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($f["tmp_name"])) voltarPerfil("foto_erro");

// Confere o conteúdo de verdade (não confia na extensão enviada pelo navegador)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);
$ext  = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"][$mime] ?? null;
if (!$ext || @getimagesize($f["tmp_name"]) === false) voltarPerfil("foto_invalida");

if (!fotoPrepararPasta()) voltarPerfil("foto_erro");

fotoRemover($cnpj);                                   // apaga a anterior (pode ter outra extensão)
if (!move_uploaded_file($f["tmp_name"], FOTO_DIR . "/" . $cnpj . "." . $ext)) voltarPerfil("foto_erro");
@chmod(FOTO_DIR . "/" . $cnpj . "." . $ext, 0644);

voltarPerfil("foto_ok");
