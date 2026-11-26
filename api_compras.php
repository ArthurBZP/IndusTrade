<?php
// Compras da empresa logada (aba "Produtos Comprados" do perfil).
// Lê do banco as mesmas tabelas usadas por pedidos.php: PEDIDO + ITEM_PEDIDO + ANUNCIO.
require_once __DIR__ . "/api_lib.php";
$cnpj = api_exigir_login();

try {
    $stmt = $pdo->prepare(
        "SELECT p.id_pedido, p.data_criacao, p.condicao_atual,
                ip.quantidade, ip.valor_unitario, ip.id_anuncio_pk_fk AS id_anuncio,
                a.nome, a.cnpj_fk AS cnpj_vendedor,
                v.nome_fantasia AS vendedor,
                av.nota
           FROM PEDIDO p
           JOIN ITEM_PEDIDO ip ON ip.id_pedido_pk_fk = p.id_pedido
           JOIN ANUNCIO a      ON a.id_anuncio = ip.id_anuncio_pk_fk
           JOIN EMPRESA v      ON v.cnpj = a.cnpj_fk
           LEFT JOIN AVALIACAO av ON av.id_pedido_fk = p.id_pedido
                                 AND av.cnpj_avaliador_fk = p.cnpj_comprador_fk
          WHERE p.cnpj_comprador_fk = ? AND p.condicao_atual <> 'CARRINHO'
          ORDER BY p.data_criacao DESC, p.id_pedido DESC"
    );
    $stmt->execute([$cnpj]);
    $compras = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $compras[] = [
            "id_pedido"     => (int)$r["id_pedido"],
            "produto"       => $r["nome"],
            "valor"         => (float)$r["valor_unitario"] * (float)$r["quantidade"],
            "data"          => $r["data_criacao"],
            "status"        => $r["condicao_atual"],
            "vendedor"      => $r["vendedor"],
            "cnpj_vendedor" => $r["cnpj_vendedor"],
            "nota"          => $r["nota"] !== null ? (int)round((float)$r["nota"]) : null,
        ];
    }
    api_responder(["ok" => true, "compras" => $compras]);
} catch (PDOException $e) {
    api_responder(["ok" => false, "erro" => "banco"], 500);
}
