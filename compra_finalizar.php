<?php
// Finaliza a compra de um anúncio: grava o pedido no banco (PEDIDO + ITEM_PEDIDO +
// HISTORICO_STATUS_PEDIDO) e marca o anúncio como VENDIDO.
// Chamado pelo formulário de pagamento.php. Os dados de pagamento (cartão, PIX,
// boleto) NÃO são enviados nem guardados: o pagamento do site é só demonstrativo.
include("protect.php");
require_once "conexao.php";

$querJson = strpos((string)($_SERVER["HTTP_ACCEPT"] ?? ""), "application/json") !== false;

// Resposta única para os dois jeitos de chamar (fetch do pagamento.php ou envio comum)
function responder($querJson, $ok, $destino, $erro = "") {
    if ($querJson) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => $ok, "destino" => $destino, "erro" => $erro]);
    } else {
        header("Location: " . $destino);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: anuncios.php");
    exit;
}

$id        = (int)($_POST["id_anuncio"] ?? 0);
$comprador = (string)$_SESSION["id"];
$voltar    = $id > 0 ? "pagamento.php?id=" . $id : "anuncios.php";

if (!csrf_valido()) {
    responder($querJson, false, $voltar, "Sessão expirada. Atualize a página e tente novamente.");
}

try {
    $pdo->beginTransaction();

    // Reserva o anúncio: só passa se ele ainda estiver ATIVO, tiver preço e não for da
    // própria empresa. Como é um único UPDATE, duas pessoas não compram o mesmo anúncio.
    $stmt = $pdo->prepare(
        "UPDATE ANUNCIO SET condicao = 'VENDIDO'
          WHERE id_anuncio = ? AND condicao = 'ATIVO' AND valor > 0 AND cnpj_fk <> ?"
    );
    $stmt->execute([$id, $comprador]);
    if ($stmt->rowCount() !== 1) {
        $pdo->rollBack();
        responder($querJson, false, "anuncios.php", "Este anúncio não está mais disponível para compra.");
    }

    $stmt = $pdo->prepare("SELECT valor, cnpj_fk FROM ANUNCIO WHERE id_anuncio = ?");
    $stmt->execute([$id]);
    $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->prepare(
        "INSERT INTO PEDIDO (data_criacao, condicao_atual, valor_total, cnpj_comprador_fk)
         VALUES (?, 'REALIZADO', ?, ?)"
    )->execute([date("Y-m-d"), $anuncio["valor"], $comprador]);
    $idPedido = (int)$pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO ITEM_PEDIDO (id_pedido_pk_fk, id_anuncio_pk_fk, quantidade, valor_unitario)
         VALUES (?, ?, 1, ?)"
    )->execute([$idPedido, $id, $anuncio["valor"]]);

    $pdo->prepare(
        "INSERT INTO HISTORICO_STATUS_PEDIDO (condicao, data_hora, id_pedido_fk)
         VALUES ('REALIZADO', ?, ?)"
    )->execute([date("Y-m-d H:i:s"), $idPedido]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    responder($querJson, false, $voltar, "Não foi possível concluir a compra. Tente novamente.");
}

// Depois da compra o comprador vai para o perfil do vendedor, já na avaliação deste pedido
responder($querJson, true,
    "vendedor.php?cnpj=" . urlencode($anuncio["cnpj_fk"]) . "&avaliar=" . $idPedido . "#avaliar");
