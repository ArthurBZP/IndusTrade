<?php
// Cria (id_anuncio vazio) ou atualiza (id_anuncio preenchido) um anúncio da empresa logada.
require_once __DIR__ . "/protect.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/anuncio_lib.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: perfil.php");
    exit;
}
if (!csrf_valido()) {
    header("Location: perfil.php?aba=produtos&msg=csrf");
    exit;
}

$cnpj      = $_SESSION["id"];
$idAnuncio = (int)($_POST["id_anuncio"] ?? 0);   // 0 = anúncio novo
$edicao    = $idAnuncio > 0;

// Volta ao formulário com o erro, mantendo o que foi digitado
function voltar($erro) {
    global $idAnuncio;
    $dados = $_POST;
    unset($dados["csrf"]);
    $_SESSION["anuncio_dados"] = $dados;
    header("Location: cadastro-anuncio.php?" . ($idAnuncio > 0 ? "id=$idAnuncio&" : "") . "erro=" . $erro);
    exit;
}
function numero($v) {                            // aceita "12,5" ou "12.5"
    $v = str_replace(",", ".", trim((string)$v));
    return is_numeric($v) ? (float)$v : null;
}
function texto($campo) { return trim((string)($_POST[$campo] ?? "")); }

// ---- Informações gerais ----
$nome      = texto("nome");
$descricao = texto("descricao");
$condItem  = texto("condicao");
$conserv   = texto("conservacao");
$dataPub   = texto("data");
$tipoPreco = texto("preco");
$categoria = texto("categoria");
$setor     = texto("setor");

if ($nome === "" || $descricao === "" || $dataPub === "") voltar("campos_obrigatorios");
if (mb_strlen($nome) > 150)       voltar("nome_longo");
if (mb_strlen($descricao) > 2000) voltar("descricao_longa");
if (!isset(SETORES[$setor])) voltar("campos_obrigatorios");
if (!in_array($condItem, CONDICOES_ITEM, true) || !in_array($conserv, CONSERVACOES, true)) voltar("campos_obrigatorios");
$d = DateTime::createFromFormat("Y-m-d", $dataPub);
if (!$d || $d->format("Y-m-d") !== $dataPub) voltar("data_invalida");

// ---- Preço ----
if (!in_array($tipoPreco, ["fixo", "negociavel"], true)) voltar("campos_obrigatorios");
if ($tipoPreco === "fixo") {
    $valor = numero($_POST["valor"] ?? "");
    if ($valor === null || $valor <= 0 || $valor > 9999999999.99) voltar("valor_invalido");
} else {
    $valor = 0.0;                                // negociável: "Sob consulta"
}
$tipoPrecoBanco = $tipoPreco === "fixo" ? "FIXO" : "NEGOCIAVEL";

// ---- Detalhes de Maquinário ou Recurso ----
if ($categoria === "maquinario") {
    $marca    = texto("marca");
    $modelo   = texto("modelo");
    $ano      = texto("ano");
    $pesoMaq  = numero($_POST["peso_maq"] ?? "");
    $dimensao = texto("dimensoes");
    $voltagem = texto("voltagem");
    if ($marca === "" || $modelo === "" || $dimensao === "" || $voltagem === "") voltar("campos_obrigatorios");
    if (mb_strlen($marca) > 80 || mb_strlen($modelo) > 80 || mb_strlen($dimensao) > 60 || mb_strlen($voltagem) > 20) voltar("texto_longo");
    if (!ctype_digit($ano) || (int)$ano < 1900 || (int)$ano > 2100) voltar("ano_invalido");
    if ($pesoMaq === null || $pesoMaq < 0 || $pesoMaq > 99999999.99) voltar("numero_invalido");
} elseif ($categoria === "recurso") {
    $nomeRec = texto("nome_recurso");
    $pesoRec = numero($_POST["peso_rec"] ?? "");
    $qtd     = numero($_POST["quantidade"] ?? "");
    $unidade = texto("unidade");
    if ($nomeRec === "") voltar("campos_obrigatorios");
    if (mb_strlen($nomeRec) > 120) voltar("texto_longo");
    if (!in_array($unidade, UNIDADES, true)) voltar("campos_obrigatorios");
    if ($pesoRec === null || $pesoRec < 0 || $pesoRec > 99999999.99) voltar("numero_invalido");
    if ($qtd === null || $qtd <= 0 || $qtd > 99999999.99) voltar("numero_invalido");
} else {
    voltar("campos_obrigatorios");
}

// ---- Imagens (opcionais; só http/https) ----
$imagens = [];
$urls = $_POST["img_url"] ?? [];
$legs = $_POST["img_legenda"] ?? [];
if (is_array($urls)) {
    foreach ($urls as $i => $url) {
        $url = trim((string)$url);
        if ($url === "") continue;
        $leg = trim((string)($legs[$i] ?? ""));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url) || strlen($url) > 500) voltar("imagem_invalida");
        if (mb_strlen($leg) > 120) voltar("texto_longo");
        $imagens[] = [$url, $leg === "" ? null : $leg];
    }
}

try {
    // Em edição, só mexe em anúncio que é desta empresa e ainda está ativo/pausado
    if ($edicao) {
        $stmt = $pdo->prepare("SELECT id_anuncio FROM ANUNCIO WHERE id_anuncio = ? AND cnpj_fk = ? AND condicao IN ('ATIVO','PAUSADO')");
        $stmt->execute([$idAnuncio, $cnpj]);
        if (!$stmt->fetchColumn()) {
            header("Location: perfil.php?aba=produtos&msg=nao_encontrado");
            exit;
        }
    }

    $pdo->beginTransaction();

    $idCategoria = obterCategoria($pdo, SETORES[$setor]);
    $estado      = codificarEstado($condItem, $conserv);

    if ($edicao) {
        $pdo->prepare(
            "UPDATE ANUNCIO
                SET nome = ?, descricao = ?, estado_conservacao = ?, data_publicacao = ?,
                    valor = ?, tipo_preco = ?, id_categoria_fk = ?
              WHERE id_anuncio = ? AND cnpj_fk = ?"
        )->execute([$nome, $descricao, $estado, $dataPub, $valor, $tipoPrecoBanco, $idCategoria, $idAnuncio, $cnpj]);

        // Detalhes e imagens são regravados (permite trocar Maquinário <-> Recurso)
        $pdo->prepare("DELETE FROM MAQUINARIO WHERE id_anuncio_fk = ?")->execute([$idAnuncio]);
        $pdo->prepare("DELETE FROM RECURSO    WHERE id_anuncio_fk = ?")->execute([$idAnuncio]);
        $pdo->prepare("DELETE FROM IMAGEM     WHERE id_anuncio_fk = ?")->execute([$idAnuncio]);
    } else {
        $pdo->prepare(
            "INSERT INTO ANUNCIO (nome, descricao, estado_conservacao, data_publicacao,
                                  condicao, valor, tipo_preco, cnpj_fk, id_categoria_fk)
             VALUES (?, ?, ?, ?, 'ATIVO', ?, ?, ?, ?)"
        )->execute([$nome, $descricao, $estado, $dataPub, $valor, $tipoPrecoBanco, $cnpj, $idCategoria]);
        $idAnuncio = (int)$pdo->lastInsertId();
    }

    if ($categoria === "maquinario") {
        $pdo->prepare(
            "INSERT INTO MAQUINARIO (marca, modelo, ano_fabricacao, peso, dimensao, voltagem, id_anuncio_fk)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$marca, $modelo, (int)$ano, $pesoMaq, $dimensao, $voltagem, $idAnuncio]);
    } else {
        $pdo->prepare(
            "INSERT INTO RECURSO (nome, peso, quantidade, unidade_medida, id_anuncio_fk)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$nomeRec, $pesoRec, $qtd, $unidade, $idAnuncio]);
    }

    $stmtImg = $pdo->prepare("INSERT INTO IMAGEM (link, legenda, id_anuncio_fk) VALUES (?, ?, ?)");
    foreach ($imagens as $img) {
        $stmtImg->execute([$img[0], $img[1], $idAnuncio]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    voltar("server_error");
}

unset($_SESSION["anuncio_dados"]);
header("Location: perfil.php?aba=produtos&msg=" . ($edicao ? "atualizado" : "criado"));
exit;
