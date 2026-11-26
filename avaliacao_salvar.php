<?php
// Grava a avaliação que o comprador faz do vendedor (tabela AVALIACAO).
// Regras: só avalia quem comprou (o pedido tem que ser da empresa logada), nota de
// 1 a 5 estrelas, comentário opcional e uma única avaliação por pedido.
include("protect.php");
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pedidos.php");
    exit;
}

$idPedido   = (int)($_POST["id_pedido"] ?? 0);
$notaTxt    = (string)($_POST["nota"] ?? "");
$comentario = trim((string)($_POST["comentario"] ?? ""));
$comentario = $comentario === "" ? null : mb_substr($comentario, 0, 1000);
$avaliador  = (string)$_SESSION["id"];

try {
    // O pedido precisa ser do usuário logado; o vendedor avaliado é o dono do anúncio comprado
    $stmt = $pdo->prepare(
        "SELECT a.cnpj_fk
           FROM PEDIDO p
           JOIN ITEM_PEDIDO ip ON ip.id_pedido_pk_fk = p.id_pedido
           JOIN ANUNCIO a      ON a.id_anuncio = ip.id_anuncio_pk_fk
          WHERE p.id_pedido = ? AND p.cnpj_comprador_fk = ?
            AND p.condicao_atual NOT IN ('CARRINHO', 'CANCELADO')
          LIMIT 1"
    );
    $stmt->execute([$idPedido, $avaliador]);
    $vendedor = $stmt->fetchColumn();

    if (!$vendedor) {                       // pedido inexistente ou de outra empresa
        header("Location: pedidos.php");
        exit;
    }
    $destino = "vendedor.php?cnpj=" . urlencode($vendedor) . "&aba=avaliacoes";

    if (!csrf_valido()) {
        header("Location: $destino&msg=csrf&avaliar=$idPedido#avaliar");
        exit;
    }
    if (!in_array($notaTxt, ["1", "2", "3", "4", "5"], true)) {
        header("Location: $destino&msg=nota&avaliar=$idPedido#avaliar");
        exit;
    }

    $stmt = $pdo->prepare("SELECT 1 FROM AVALIACAO WHERE id_pedido_fk = ? AND cnpj_avaliador_fk = ? LIMIT 1");
    $stmt->execute([$idPedido, $avaliador]);
    if ($stmt->fetchColumn()) {
        header("Location: $destino&msg=ja_avaliado");
        exit;
    }

    $pdo->prepare(
        "INSERT INTO AVALIACAO (nota, comentario, data_avaliacao, cnpj_avaliador_fk, cnpj_avaliado_fk, id_pedido_fk)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([(int)$notaTxt, $comentario, date("Y-m-d"), $avaliador, $vendedor, $idPedido]);

    header("Location: $destino&msg=avaliado");
    exit;
} catch (PDOException $e) {
    header("Location: pedidos.php?msg=erro");
    exit;
}
