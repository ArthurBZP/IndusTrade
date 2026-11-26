<?php
// Auxiliar dos endpoints JSON (api_compras.php, api_vendas.php, vendedor_contato.php).
// Arquivo NOVO: não altera nenhuma página existente.
require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";

function api_responder($dados, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Exige login (a sessão é a mesma do restante do site). Devolve o CNPJ logado.
function api_exigir_login() {
    if (!isset($_SESSION["id"])) {
        api_responder(["ok" => false, "erro" => "login"], 401);
    }
    return (string)$_SESSION["id"];
}
