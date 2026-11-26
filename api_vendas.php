<?php
// Vendas da empresa logada (sino de notificações do perfil).
// Lê do banco: cada pedido de outra empresa contendo um anúncio desta empresa é uma venda.
require_once __DIR__ . "/api_lib.php";
$cnpj = api_exigir_login();

try {
    $stmt = $pdo->prepare(
        "SELECT p.id_pedido, p.data_criacao,
                (SELECT MIN(h.data_hora) FROM HISTORICO_STATUS_PEDIDO h WHERE h.id_pedido_fk = p.id_pedido) AS data_hora,
                ip.valor_unitario, ip.quantidade,
                a.nome AS produto,
                comp.nome_fantasia AS comprador
           FROM ANUNCIO a
           JOIN ITEM_PEDIDO ip ON ip.id_anuncio_pk_fk = a.id_anuncio
           JOIN PEDIDO p       ON p.id_pedido = ip.id_pedido_pk_fk
           JOIN EMPRESA comp   ON comp.cnpj = p.cnpj_comprador_fk
          WHERE a.cnpj_fk = ? AND p.condicao_atual NOT IN ('CARRINHO','CANCELADO')
          ORDER BY p.data_criacao DESC, p.id_pedido DESC
          LIMIT 50"
    );
    $stmt->execute([$cnpj]);
    $vendas = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $vendas[] = [
            "id"        => (int)$r["id_pedido"],
            "produto"   => $r["produto"],
            "comprador" => $r["comprador"],
            "valor"     => (float)$r["valor_unitario"] * (float)$r["quantidade"],
            "data"      => $r["data_hora"] ?: $r["data_criacao"],
        ];
    }
    api_responder(["ok" => true, "eu" => $cnpj, "vendas" => $vendas]);
} catch (PDOException $e) {
    api_responder(["ok" => false, "erro" => "banco"], 500);
}
