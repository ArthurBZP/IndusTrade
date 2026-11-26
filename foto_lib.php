<?php
// Foto de perfil das empresas. Fica salva em disco: uploads/perfil/<CNPJ>.<jpg|png|webp>
// (não precisa de coluna nova no banco; o nome do arquivo é o próprio CNPJ).

const FOTO_DIR  = __DIR__ . "/uploads/perfil";
const FOTO_EXTS = ["jpg", "png", "webp"];
const FOTO_MAX  = 2 * 1024 * 1024;   // 2 MB

// Cria a pasta (uma vez) e bloqueia a execução de scripts dentro dela
function fotoPrepararPasta() {
    if (!is_dir(FOTO_DIR)) @mkdir(FOTO_DIR, 0755, true);
    $ht = __DIR__ . "/uploads/.htaccess";
    if (is_dir(dirname($ht)) && !file_exists($ht)) {
        @file_put_contents($ht, "# Uploads: nunca executar scripts\n"
            . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
            . "<IfModule mod_php7.c>\n  php_flag engine off\n</IfModule>\n"
            . "<IfModule mod_php8.c>\n  php_flag engine off\n</IfModule>\n"
            . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\n"
            . "Options -ExecCGI\n");
    }
    return is_dir(FOTO_DIR) && is_writable(FOTO_DIR);
}

// URL da foto da empresa (com ?v=data para não ficar em cache após trocar) ou null se não tiver
function fotoPerfilUrl($cnpj) {
    $cnpj = preg_replace('/\D/', '', (string)$cnpj);
    if ($cnpj === "") return null;
    foreach (FOTO_EXTS as $ext) {
        $arq = FOTO_DIR . "/" . $cnpj . "." . $ext;
        if (is_file($arq)) return "uploads/perfil/" . $cnpj . "." . $ext . "?v=" . filemtime($arq);
    }
    return null;
}

function fotoRemover($cnpj) {
    foreach (FOTO_EXTS as $ext) {
        $arq = FOTO_DIR . "/" . $cnpj . "." . $ext;
        if (is_file($arq)) @unlink($arq);
    }
}

// Conteúdo do círculo do avatar: <img> da foto ou, sem foto, as iniciais
function avatarConteudo($cnpj, $iniciais) {
    $url = fotoPerfilUrl($cnpj);
    if ($url) {
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, "UTF-8") . '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    }
    return htmlspecialchars($iniciais, ENT_QUOTES, "UTF-8");
}
