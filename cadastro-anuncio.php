<?php
include("protect.php");
require_once "conexao.php";
require_once "anuncio_lib.php";

$idAnuncio = (int)($_GET["id"] ?? 0);        // com id = editar; sem id = novo anúncio
$edicao    = $idAnuncio > 0;
$cnpj      = $_SESSION["id"];

// Valores iniciais do formulário
$f = [
    "nome" => "", "descricao" => "", "condicao" => "", "conservacao" => "",
    "data" => date("Y-m-d"), "setor" => "", "preco" => "fixo", "valor" => "", "categoria" => "",
    "marca" => "", "modelo" => "", "ano" => "", "peso_maq" => "", "dimensoes" => "", "voltagem" => "",
    "nome_recurso" => "", "peso_rec" => "", "quantidade" => "", "unidade" => "",
];
$imagens = [];                                // [[url, legenda], ...]

$erros = [
    "campos_obrigatorios" => "Preencha todos os campos obrigatórios.",
    "nome_longo"          => "O nome pode ter no máximo 150 caracteres.",
    "descricao_longa"     => "A descrição pode ter no máximo 2000 caracteres.",
    "data_invalida"       => "Informe uma data válida.",
    "valor_invalido"      => "Informe um valor maior que zero para o preço fixo.",
    "ano_invalido"        => "Informe um ano de fabricação entre 1900 e 2100.",
    "numero_invalido"     => "Peso e quantidade devem ser números válidos (quantidade maior que zero).",
    "texto_longo"         => "Algum campo de texto ultrapassou o tamanho máximo permitido.",
    "imagem_invalida"     => "Use links de imagem completos, começando com http:// ou https://.",
    "server_error"        => "Erro no servidor ao salvar o anúncio. Tente novamente.",
];

try {
    $postado = $_SESSION["anuncio_dados"] ?? null;   // o que foi digitado antes de um erro
    unset($_SESSION["anuncio_dados"]);

    if ($postado && (int)($postado["id_anuncio"] ?? 0) === $idAnuncio) {
        foreach ($f as $k => $_) {
            if (isset($postado[$k]) && is_string($postado[$k])) $f[$k] = $postado[$k];
        }
        $urls = is_array($postado["img_url"] ?? null) ? $postado["img_url"] : [];
        $legs = is_array($postado["img_legenda"] ?? null) ? $postado["img_legenda"] : [];
        foreach ($urls as $i => $u) {
            if (trim((string)$u) !== "") $imagens[] = [(string)$u, (string)($legs[$i] ?? "")];
        }
    } elseif ($edicao) {
        $stmt = $pdo->prepare(
            "SELECT a.*, c.tipo AS setor_tipo FROM ANUNCIO a JOIN CATEGORIA c ON c.id_categoria = a.id_categoria_fk
              WHERE a.id_anuncio = ? AND a.cnpj_fk = ? AND a.condicao IN ('ATIVO','PAUSADO')"
        );
        $stmt->execute([$idAnuncio, $cnpj]);
        $a = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$a) {                                    // não existe ou não é desta empresa
            header("Location: perfil.php?aba=produtos&msg=nao_encontrado");
            exit;
        }
        list($f["condicao"], $f["conservacao"]) = decodificarEstado($a["estado_conservacao"]);
        $f["nome"]      = $a["nome"];
        $f["setor"]     = slugSetor($a["setor_tipo"]);
        $f["descricao"] = (string)$a["descricao"];
        $f["data"]      = $a["data_publicacao"];
        $f["preco"]     = $a["tipo_preco"] === "NEGOCIAVEL" ? "negociavel" : "fixo";
        $f["valor"]     = (float)$a["valor"] > 0 ? $a["valor"] : "";

        $stmt = $pdo->prepare("SELECT * FROM MAQUINARIO WHERE id_anuncio_fk = ? LIMIT 1");
        $stmt->execute([$idAnuncio]);
        if ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f["categoria"] = "maquinario";
            $f["marca"] = (string)$m["marca"]; $f["modelo"] = (string)$m["modelo"];
            $f["ano"] = (string)$m["ano_fabricacao"]; $f["peso_maq"] = (string)$m["peso"];
            $f["dimensoes"] = (string)$m["dimensao"]; $f["voltagem"] = (string)$m["voltagem"];
        } else {
            $stmt = $pdo->prepare("SELECT * FROM RECURSO WHERE id_anuncio_fk = ? LIMIT 1");
            $stmt->execute([$idAnuncio]);
            if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $f["categoria"] = "recurso";
                $f["nome_recurso"] = $r["nome"]; $f["peso_rec"] = (string)$r["peso"];
                $f["quantidade"] = (string)(float)$r["quantidade"]; $f["unidade"] = $r["unidade_medida"];
            }
        }

        $stmt = $pdo->prepare("SELECT link, legenda FROM IMAGEM WHERE id_anuncio_fk = ? ORDER BY id_imagem");
        $stmt->execute([$idAnuncio]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $im) {
            $imagens[] = [$im["link"], (string)$im["legenda"]];
        }
    }
} catch (PDOException $e) {
    die("Erro ao carregar o anúncio.");
}
if (!$imagens) $imagens[] = ["", ""];            // sempre mostra ao menos uma linha de imagem

function chk($campo, $valor) { global $f; return $f[$campo] === $valor ? " checked" : ""; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $edicao ? "Editar anúncio" : "Anunciar produto"; ?> | Industrade</title>
  <meta name="description" content="Cadastre um novo anúncio de maquinário ou recurso na Industrade.">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <link rel="stylesheet" href="css/forms.css">
</head>
<body>

  <a class="skip-link" href="#conteudo-principal">Pular para o conteúdo principal</a>

  <header class="site-header">
    <div class="header-inner">

      <a class="logo" href="index.php" aria-label="Industrade — página inicial">
        <img class="logo-mark" src="img/Engrenagem no logo.png" width="36" height="36" alt="" aria-hidden="true">
        <span class="logo-text">Industrade</span>
      </a>

      <!--form class="search-form" role="search" action="/busca" method="get">
        <label class="visually-hidden" for="busca-maquinario">Buscar por palavra-chave, marca ou modelo</label>
        <input
          type="search"
          id="busca-maquinario"
          name="q"
          placeholder="Buscar por palavra-chave, marca ou modelo…"
          autocomplete="off"
        >
        <button type="submit" aria-label="Buscar">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
            <circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"></circle>
            <line x1="15.5" y1="15.5" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
          </svg>
        </button>
      </form-->

      <input type="checkbox" id="menu-toggle" class="menu-toggle-input">
      <label for="menu-toggle" class="menu-toggle-label" aria-hidden="false">
        <span class="visually-hidden">Abrir menu de navegação</span>
        <span class="menu-icon"></span>
      </label>

      <nav class="primary-nav" aria-label="Navegação principal">
        <!--ul>
          <li><a href="index.php#comprar">Comprar</a></li>
          <li><a href="index.php#vender">Vender</a></li>
          <li><a href="index.php#trocar">Trocar</a></li>
          <li><a href="index.php#categorias">Categorias</a></li>
        </ul-->
        <?php include __DIR__ . "/nav_cta.php"; ?>
      </nav>

    </div>
  </header>

  <main id="conteudo-principal" class="pagina-interna">
    <div class="section-inner">
      <h1 class="pagina-titulo"><?php echo $edicao ? "Editar anúncio" : "Anunciar produto"; ?></h1>
      <p class="pagina-sub"><?php echo $edicao
          ? "Atualize as informações do seu anúncio e salve as alterações."
          : "Preencha os dados do equipamento ou recurso que você quer vender ou trocar."; ?></p>

      <form class="card-form card-medio" action="anuncio_salvar.php" method="POST">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id_anuncio" value="<?php echo $edicao ? $idAnuncio : ""; ?>">
        <?php if (isset($_GET["erro"])): ?>
          <p role="alert" style="color:#c0392b;margin-bottom:var(--space-sm)">
            <?php echo e($erros[$_GET["erro"]] ?? $erros["server_error"]); ?>
          </p>
        <?php endif; ?>

        <fieldset>
          <legend>Informações gerais</legend>
          <div class="grade-campos">
          <div class="campo col-total">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required maxlength="150" value="<?php echo e($f["nome"]); ?>">
          </div>
          <div class="campo col-total">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" required maxlength="2000"><?php echo e($f["descricao"]); ?></textarea>
          </div>
          <div class="campo ">
            <label for="setor">Categoria (setor)</label>
            <select id="setor" name="setor" required><option value="">Selecione</option><?php foreach (SETORES as $slug => $nomeSetor): ?><option value="<?php echo e($slug); ?>"<?php echo $f["setor"] === $slug ? " selected" : ""; ?>><?php echo e($nomeSetor); ?></option><?php endforeach; ?></select>
          </div>
          <div class="campo ">
            <label for="condicao">Condição do Item</label>
            <select id="condicao" name="condicao" required><?php echo opcoes(CONDICOES_ITEM, $f["condicao"]); ?></select>
          </div>
          <div class="campo ">
            <label for="conservacao">Estado de Conservação</label>
            <select id="conservacao" name="conservacao" required><?php echo opcoes(CONSERVACOES, $f["conservacao"]); ?></select>
          </div>
          <div class="campo ">
            <label for="data">Data</label>
            <input type="date" id="data" name="data" required value="<?php echo e($f["data"]); ?>">
          </div>
          </div>
        </fieldset>

        <fieldset style="margin-top:var(--space-md)">
          <legend>Preço</legend>
          <div class="opcoes" style="max-width:20rem">
            <label class="opcao"><input type="radio" name="preco" value="fixo" data-alterna="preco"<?php echo chk("preco", "fixo"); ?> required><span>Fixo</span></label>
            <label class="opcao"><input type="radio" name="preco" value="negociavel" data-alterna="preco"<?php echo chk("preco", "negociavel"); ?> required><span>Negociável</span></label>
          </div>
          <div class="grade-campos" style="margin-top:var(--space-sm)" data-painel-de="preco" data-painel="fixo">
          <div class="campo ">
            <label for="valor">Valor (R$)</label>
            <input type="number" id="valor" name="valor" required placeholder="0,00" min="0.01" step="0.01" value="<?php echo e($f["valor"]); ?>">
          </div>
          </div>
        </fieldset>

        <fieldset style="margin-top:var(--space-md)">
          <legend>O anúncio é de maquinário ou de recurso?</legend>
          <div class="opcoes" style="max-width:20rem">
            <label class="opcao"><input type="radio" name="categoria" value="maquinario" data-alterna="categoria"<?php echo chk("categoria", "maquinario"); ?> required><span>Maquinário</span></label>
            <label class="opcao"><input type="radio" name="categoria" value="recurso" data-alterna="categoria"<?php echo chk("categoria", "recurso"); ?> required><span>Recurso</span></label>
          </div>

          <div class="grade-campos" style="margin-top:var(--space-sm)" data-painel-de="categoria" data-painel="maquinario" hidden>
          <div class="campo ">
            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" required maxlength="80" value="<?php echo e($f["marca"]); ?>">
          </div>
          <div class="campo ">
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo" required maxlength="80" value="<?php echo e($f["modelo"]); ?>">
          </div>
          <div class="campo ">
            <label for="ano">Ano de Fabricação</label>
            <input type="number" id="ano" name="ano" required min="1900" max="2100" value="<?php echo e($f["ano"]); ?>">
          </div>
          <div class="campo ">
            <label for="peso_maq">Peso (kg)</label>
            <input type="number" id="peso_maq" name="peso_maq" required min="0" step="0.01" value="<?php echo e($f["peso_maq"]); ?>">
          </div>
          <div class="campo ">
            <label for="dimensoes">Dimensões</label>
            <input type="text" id="dimensoes" name="dimensoes" required maxlength="60" placeholder="C x L x A (cm)" value="<?php echo e($f["dimensoes"]); ?>">
          </div>
          <div class="campo ">
            <label for="voltagem">Voltagem</label>
            <input type="text" id="voltagem" name="voltagem" required maxlength="20" placeholder="Ex.: 220V trifásico" value="<?php echo e($f["voltagem"]); ?>">
          </div>
          </div>

          <div class="grade-campos" style="margin-top:var(--space-sm)" data-painel-de="categoria" data-painel="recurso" hidden>
          <div class="campo ">
            <label for="nome_recurso">Nome do recurso</label>
            <input type="text" id="nome_recurso" name="nome_recurso" required maxlength="120" value="<?php echo e($f["nome_recurso"]); ?>">
          </div>
          <div class="campo ">
            <label for="peso_rec">Peso (kg)</label>
            <input type="number" id="peso_rec" name="peso_rec" required min="0" step="0.01" value="<?php echo e($f["peso_rec"]); ?>">
          </div>
          <div class="campo ">
            <label for="quantidade">Quantidade</label>
            <input type="number" id="quantidade" name="quantidade" required min="0.01" step="0.01" value="<?php echo e($f["quantidade"]); ?>">
          </div>
          <div class="campo ">
            <label for="unidade">Unidade de Medida</label>
            <select id="unidade" name="unidade" required><?php echo opcoes(UNIDADES, $f["unidade"]); ?></select>
          </div>
          </div>
        </fieldset>

        <fieldset style="margin-top:var(--space-md)">
          <legend>Imagens</legend>
          <div id="imagens">
            <?php foreach ($imagens as $i => $img): $n = $i + 1; ?>
            <div class="imagem-linha">
              <div class="campo"><label for="img-url-<?php echo $n; ?>">Link (URL) da Imagem</label><input type="url" id="img-url-<?php echo $n; ?>" name="img_url[]" maxlength="500" placeholder="https://..." value="<?php echo e($img[0]); ?>"></div>
              <div class="campo"><label for="img-leg-<?php echo $n; ?>">Legenda</label><input type="text" id="img-leg-<?php echo $n; ?>" name="img_legenda[]" maxlength="120" value="<?php echo e($img[1]); ?>"></div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-ghost" id="add-imagem" style="border-color:var(--cor-preto)">+ Adicionar outra imagem</button>
        </fieldset>

        <div class="acoes-form">
          <a class="btn btn-ghost" href="perfil.php?aba=produtos" style="border-color:var(--cor-preto)">Cancelar</a>
          <button type="submit" class="btn btn-primary btn-lg"><?php echo $edicao ? "Salvar alterações" : "Publicar anúncio"; ?></button>
        </div>
      </form>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">

      <div class="footer-coluna">
        <p class="footer-logo">Industrade</p>
        <p>Marketplace B2B para maquinário industrial usado.</p>
      </div>

      <nav class="footer-coluna" aria-label="Links úteis">
        <h3>Links úteis</h3>
        <ul>
          <li><a href="/sobre">Sobre nós</a></li>
          <li><a href="/ajuda">Central de ajuda</a></li>
          <li><a href="/frete">Políticas de frete e logística</a></li>
        </ul>
      </nav>

      <nav class="footer-coluna" aria-label="Termos legais">
        <h3>Termos legais</h3>
        <ul>
          <li><a href="/termos">Termos de uso</a></li>
          <li><a href="/privacidade">Política de privacidade</a></li>
          <li><a href="/regras-troca">Regras de troca</a></li>
        </ul>
      </nav>

      <div class="footer-coluna">
        <h3>Contato</h3>
        <address>
          <a href="mailto:contato@industrade.com.br">contato@industrade.com.br</a><br>
          <a href="tel:+551140028922">(11) 4002-8922</a>
        </address>
        <ul class="redes-sociais">
          <li><a href="https://instagram.com" aria-label="Industrade no Instagram">Instagram</a></li>
          <li><a href="https://linkedin.com" aria-label="Industrade no LinkedIn">LinkedIn</a></li>
        </ul>
      </div>

    </div>
    <p class="copyright">© 2026 Industrade. Todos os direitos reservados.</p>
  </footer>

  <script>
    // Fecha o menu mobile ao clicar em um link de navegação
    document.querySelectorAll('.primary-nav a').forEach(function (link) {
      link.addEventListener('click', function () {
        document.getElementById('menu-toggle').checked = false;
      });
    });
  </script>
  <script src="js/forms.js"></script>
  <script>
    var n = <?php echo count($imagens); ?>;
    document.getElementById('add-imagem').addEventListener('click', function () {
      n++;
      var d = document.createElement('div');
      d.className = 'imagem-linha';
      d.innerHTML = '<div class="campo"><label for="img-url-' + n + '">Link (URL) da Imagem</label><input type="url" id="img-url-' + n + '" name="img_url[]" placeholder="https://..."></div>' +
                    '<div class="campo"><label for="img-leg-' + n + '">Legenda</label><input type="text" id="img-leg-' + n + '" name="img_legenda[]"></div>';
      document.getElementById('imagens').appendChild(d);
    });
  </script>
</body>
</html>
