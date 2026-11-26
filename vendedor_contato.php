<?php
// Dados de contato + reputação de um vendedor, para o pop-up "Conversar com o Vendedor".
// Mesma regra de privacidade de vendedor.php: e-mail e telefone só para quem está logado.
require_once __DIR__ . "/api_lib.php";
$logado = api_exigir_login();

$cnpj = preg_replace('/\D/', '', (string)($_GET["cnpj"] ?? ""));
if (strlen($cnpj) !== 14) api_responder(["ok" => false, "erro" => "nao_encontrado"], 404);

try {
    $stmt = $pdo->prepare(
        "SELECT e.cnpj, e.nome_fantasia, e.nome_responsavel, e.email, e.telefone,
                e.data_cadastro, e.verificada, en.cidade, en.uf
           FROM EMPRESA e JOIN ENDERECO en ON en.id_endereco = e.id_endereco_fk
          WHERE e.cnpj = ?"
    );
    $stmt->execute([$cnpj]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$v) api_responder(["ok" => false, "erro" => "nao_encontrado"], 404);

    $stmt = $pdo->prepare("SELECT AVG(nota) AS media, COUNT(*) AS total FROM AVALIACAO WHERE cnpj_avaliado_fk = ?");
    $stmt->execute([$cnpj]);
    $aval = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT av.nota, av.comentario, av.data_avaliacao, emp.nome_fantasia AS avaliador
           FROM AVALIACAO av JOIN EMPRESA emp ON emp.cnpj = av.cnpj_avaliador_fk
          WHERE av.cnpj_avaliado_fk = ?
          ORDER BY av.data_avaliacao DESC, av.id_avaliacao DESC LIMIT 3"
    );
    $stmt->execute([$cnpj]);
    $recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM ITEM_PEDIDO ip
           JOIN ANUNCIO a ON a.id_anuncio = ip.id_anuncio_pk_fk
           JOIN PEDIDO p  ON p.id_pedido = ip.id_pedido_pk_fk
          WHERE a.cnpj_fk = ? AND p.condicao_atual NOT IN ('CARRINHO','CANCELADO')"
    );
    $stmt->execute([$cnpj]);
    $vendas = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    api_responder(["ok" => false, "erro" => "banco"], 500);
}

$tel = preg_replace('/\D/', '', (string)$v["telefone"]);
if (strlen($tel) === 10 || strlen($tel) === 11) $tel = "55" . $tel;   // DDI do Brasil
$total = (int)$aval["total"];

api_responder([
    "ok"          => true,
    "cnpj"        => $v["cnpj"],
    "responsavel" => trim((string)$v["nome_responsavel"]),
    "empresa"     => $v["nome_fantasia"],
    "local"       => $v["cidade"] . "/" . $v["uf"],
    "verificada"  => (bool)$v["verificada"],
    "desde"       => date("Y", strtotime($v["data_cadastro"])),
    "email"       => $v["email"],
    "telefone"    => $v["telefone"],
    "tel_digitos" => $tel,
    "eh_voce"     => $logado === $cnpj,
    "media"       => $total ? round((float)$aval["media"], 1) : null,
    "total"       => $total,
    "vendas"      => $vendas,
    "recentes"    => array_map(function ($r) {
        return [
            "nota"      => (float)$r["nota"],
            "comentario"=> (string)$r["comentario"],
            "data"      => $r["data_avaliacao"],
            "avaliador" => $r["avaliador"],
        ];
    }, $recentes),
]);
