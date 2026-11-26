<?php
require_once __DIR__ . "/sessao.php";
require_once "conexao.php";

// Só aceita envio pelo formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// O campo vem com máscara (12.345.678/0001-90): guarda só os números
$cnpj  = preg_replace('/\D/', '', $_POST["cnpj"] ?? "");
$senha = $_POST["senha"] ?? "";

try {
    $stmt = $pdo->prepare("SELECT cnpj, nome_fantasia, senha FROM EMPRESA WHERE cnpj = ?");
    $stmt->execute([$cnpj]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: login.php?error=server_error");
    exit;
}

if ($usuario && password_verify($senha, $usuario["senha"])) {
    session_regenerate_id(true);
    $_SESSION["id"]   = $usuario["cnpj"];          // CNPJ é a chave da EMPRESA
    $_SESSION["nome"] = $usuario["nome_fantasia"];
    // formata o CNPJ para exibir no perfil
    $_SESSION["cnpj"] = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $usuario["cnpj"]);

    header("Location: perfil.php");
    exit;
}

header("Location: login.php?error=invalid_credentials");
exit;
