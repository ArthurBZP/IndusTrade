<?php
// Exclui um anúncio da empresa logada.
// - Sem pedidos/mensagens ligados: apaga de vez (imagens, detalhes e o anúncio).
// - Com pedidos/mensagens: não dá para apagar (histórico), então vira REMOVIDO e some do perfil.
require_once __DIR__ . "/protect.php";
require_once __DIR__ . "/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: perfil.php");
    exit;
}
if (!csrf_valido()) {
    header("Location: perfil.php?aba=produtos&msg=csrf");
    exit;
}

$cnpj = $_SESSION["id"];
$id   = (int)($_POST["id_anuncio"] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT id_anuncio FROM ANUNCIO WHERE id_anuncio = ? AND cnpj_fk = ?");
    $stmt->execute([$id, $cnpj]);
    if (!$id || !$stmt->fetchColumn()) {
        header("Location: perfil.php?aba=produtos&msg=nao_encontrado");
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT (SELECT COUNT(*) FROM ITEM_PEDIDO WHERE id_anuncio_pk_fk = ?)
              + (SELECT COUNT(*) FROM MENSAGEM    WHERE id_anuncio_fk    = ?)"
    );
    $stmt->execute([$id, $id]);
    $temHistorico = (int)$stmt->fetchColumn() > 0;

    if ($temHistorico) {
        $pdo->prepare("UPDATE ANUNCIO SET condicao = 'REMOVIDO' WHERE id_anuncio = ? AND cnpj_fk = ?")
            ->execute([$id, $cnpj]);
    } else {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM IMAGEM     WHERE id_anuncio_fk = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM MAQUINARIO WHERE id_anuncio_fk = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM RECURSO    WHERE id_anuncio_fk = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM ANUNCIO    WHERE id_anuncio = ? AND cnpj_fk = ?")->execute([$id, $cnpj]);
        $pdo->commit();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: perfil.php?aba=produtos&msg=erro");
    exit;
}

header("Location: perfil.php?aba=produtos&msg=excluido");
exit;
