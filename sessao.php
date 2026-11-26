<?php
// ============================================================================
// Sessão única do site. Inclua este arquivo NO TOPO de toda página PHP
// (antes de qualquer HTML/espaço), assim o login vale em qualquer página.
//
// - O login dura 30 dias e é renovado a cada página visitada (não "cai" sozinho
//   ao fechar o navegador nem depois de alguns minutos parado).
// - Só termina quando o usuário clica em "Sair".
// ============================================================================
if (session_status() === PHP_SESSION_NONE) {
    $duracao = 60 * 60 * 24 * 30;   // 30 dias, em segundos

    // Guarda os arquivos de sessão numa pasta do projeto. Sem isso, o PHP usa a
    // pasta temporária do sistema, onde o limpador automático pode apagar a
    // sessão em ~24 min (e o login "cairia sozinho").
    $pasta = __DIR__ . DIRECTORY_SEPARATOR . "sessions";
    if (!is_dir($pasta)) {
        @mkdir($pasta, 0700, true);
    }
    if (is_dir($pasta) && is_writable($pasta)) {
        if (!file_exists($pasta . DIRECTORY_SEPARATOR . ".htaccess")) {
            @file_put_contents($pasta . DIRECTORY_SEPARATOR . ".htaccess", "Require all denied\n");
        }
        session_save_path($pasta);
    }

    $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    $cookie = [
        "lifetime" => $duracao,
        "path"     => "/",
        "secure"   => $https,
        "httponly" => true,
        "samesite" => "Lax",
    ];

    ini_set("session.gc_maxlifetime", (string)$duracao);
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    session_set_cookie_params($cookie);
    session_start();

    // Renova o prazo do cookie a cada acesso de quem está logado
    if (isset($_SESSION["id"]) && !headers_sent()) {
        setcookie(session_name(), session_id(), [
            "expires"  => time() + $duracao,
            "path"     => $cookie["path"],
            "secure"   => $cookie["secure"],
            "httponly" => $cookie["httponly"],
            "samesite" => $cookie["samesite"],
        ]);
    }
}

// ---- Proteção CSRF para os formulários que alteram dados ----
function csrf_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}
function csrf_valido() {
    return !empty($_SESSION["csrf"]) && isset($_POST["csrf"])
        && hash_equals($_SESSION["csrf"], (string)$_POST["csrf"]);
}
