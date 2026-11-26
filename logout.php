<?php
require_once __DIR__ . "/sessao.php";

// Encerra a sessão de verdade: limpa os dados e apaga o cookie
$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires"  => time() - 3600,
    "path"     => $p["path"],
    "domain"   => $p["domain"],
    "secure"   => $p["secure"],
    "httponly" => $p["httponly"],
    "samesite" => $p["samesite"] ?? "Lax",
]);
session_destroy();

header("Location: login.php");
exit;
