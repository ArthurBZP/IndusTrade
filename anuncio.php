<?php
require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/anuncio_lib.php";
require_once __DIR__ . "/foto_lib.php";

$id = (int)($_GET["id"] ?? 0);

try {
    $a = false;
    if ($id > 0) {
        $stmt = $pdo->prepare(
            "SELECT a.*, c.tipo AS categoria, e.nome_fantasia, e.nome_responsavel, e.verificada, e.telefone,
                    e.data_cadastro, en.cidade, en.uf
               FROM ANUNCIO a
               JOIN CATEGORIA c ON c.id_categoria = a.id_categoria_fk
               JOIN EMPRESA e   ON e.cnpj = a.cnpj_fk
               JOIN ENDERECO en ON en.id_endereco = e.id_endereco_fk
              WHERE a.id_anuncio = ?"
        );
        $stmt->execute([$id]);
        $a = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $dono = $a && isset($_SESSION["id"]) && (string)$_SESSION["id"] === (string)$a["cnpj_fk"];

    // Público vê só anúncios ativos; o dono também vê os pausados
    if (!$a || !($a["condicao"] === "ATIVO" || ($dono && $a["condicao"] === "PAUSADO"))) {
        header("Location: anuncios.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM MAQUINARIO WHERE id_anuncio_fk = ? LIMIT 1");
    $stmt->execute([$id]);
    $maq = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM RECURSO WHERE id_anuncio_fk = ? LIMIT 1");
    $stmt->execute([$id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT link, legenda FROM IMAGEM WHERE id_anuncio_fk = ? ORDER BY id_imagem LIMIT 8");
    $stmt->execute([$id]);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT AVG(nota) AS media, COUNT(*) AS total FROM AVALIACAO WHERE cnpj_avaliado_fk = ?");
    $stmt->execute([$a["cnpj_fk"]]);
    $aval = $stmt->fetch(PDO::FETCH_ASSOC);

    // Similares: mesmo setor primeiro; se faltar, completa com outros anúncios
    $similares = buscarAnuncios($pdo, "AND a.id_anuncio <> ? AND a.id_categoria_fk = ?", [$id, $a["id_categoria_fk"]], 4);
    if (count($similares) < 4) {
        $ids = array_merge([$id], array_column($similares, "id_anuncio"));
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $similares = array_merge($similares, buscarAnuncios($pdo, "AND a.id_anuncio NOT IN ($marcas)", $ids, 4 - count($similares)));
    }
} catch (PDOException $e) {
    die("Erro ao carregar o anúncio.");
}

// ---- Valores para exibição ----
list($condItem, $conservacao) = decodificarEstado($a["estado_conservacao"]);
if ($condItem === "") { $condItem = (string)$a["estado_conservacao"]; }
$local      = $a["cidade"] . "/" . $a["uf"];
$temPreco   = (float)$a["valor"] > 0;
$negociavel = $a["tipo_preco"] === "NEGOCIAVEL";
$slugSetor  = slugSetor($a["categoria"]);
$urlVendedor = "vendedor.php?cnpj=" . urlencode($a["cnpj_fk"]);
$vendedor   = trim((string)$a["nome_responsavel"]) !== "" ? $a["nome_responsavel"] : $a["nome_fantasia"];

// Telefone do vendedor para o pop-up "Conversar com o Vendedor".
// Assim como no perfil do vendedor, o número só aparece para quem está logado.
$logado   = isset($_SESSION["id"]);
$telefone = trim((string)$a["telefone"]);
$telDigitos = preg_replace('/\D/', '', $telefone);
if (strlen($telDigitos) === 10 || strlen($telDigitos) === 11) $telDigitos = "55" . $telDigitos;   // DDI do Brasil

$dias = (int)floor((strtotime(date("Y-m-d")) - strtotime($a["data_publicacao"])) / 86400);
if ($dias < 0 || $dias >= 30) { $publicado = "em " . date("d/m/Y", strtotime($a["data_publicacao"])); }
elseif ($dias === 0)          { $publicado = "hoje"; }
else                          { $publicado = "há " . $dias . ($dias === 1 ? " dia" : " dias"); }

$metaPartes = [];
if ($maq && $maq["ano_fabricacao"]) $metaPartes[] = "Ano " . $maq["ano_fabricacao"];
if ($condItem !== "") $metaPartes[] = $condItem;
$metaPartes[] = $local;
$metaPartes[] = "Publicado " . $publicado;

// Especificações (só entram as que têm valor)
$num = function ($v) { return rtrim(rtrim(number_format((float)$v, 2, ",", "."), "0"), ","); };
$specs = [];
if ($maq) {
    $specs = [["Marca", $maq["marca"]], ["Modelo", $maq["modelo"]], ["Ano de fabricação", $maq["ano_fabricacao"]],
              ["Condição", $condItem], ["Estado de conservação", $conservacao],
              ["Peso", $maq["peso"] !== null ? $num($maq["peso"]) . " kg" : ""],
              ["Dimensões", $maq["dimensao"]], ["Alimentação", $maq["voltagem"]]];
} elseif ($rec) {
    $specs = [["Recurso", $rec["nome"]], ["Quantidade", $num($rec["quantidade"]) . " " . $rec["unidade_medida"]],
              ["Peso", $rec["peso"] !== null ? $num($rec["peso"]) . " kg" : ""],
              ["Condição", $condItem], ["Estado de conservação", $conservacao]];
}
$specs[] = ["Categoria", $a["categoria"]];
$specs[] = ["Localização", $local];

// Avaliação do vendedor (engrenagens inteiras/meias)
$totalAval = (int)$aval["total"];
$mediaAval = $totalAval ? round(((float)$aval["media"]) * 2) / 2 : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($a["nome"]); ?> | Industrade</title>
  <meta name="description" content="<?php echo e($a["nome"] . " — " . implode(", ", array_slice($metaPartes, 0, 2)) . ". Anúncio na Industrade."); ?>">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <link rel="stylesheet" href="css/modal.css">
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

  <main id="conteudo-principal">

    <div class="section-inner">
      <nav class="trilha-nav" aria-label="Trilha de navegação">
        <a href="index.php">Início</a> /
        <?php if ($slugSetor): ?>
        <a href="anuncios.php?categoria=<?php echo e($slugSetor); ?>"><?php echo e($a["categoria"]); ?></a> /
        <?php else: ?>
        <a href="anuncios.php">Anúncios</a> /
        <?php endif; ?>
        <span class="trilha-atual"><?php echo e($a["nome"]); ?></span>
      </nav>
    </div>

    <section class="anuncio" aria-labelledby="anuncio-titulo">
      <div class="section-inner anuncio-grid">

        <div class="anuncio-principal">

          <?php $nFotos = max(1, count($fotos)); ?>
          <style>
            <?php for ($i = 4; $i <= $nFotos; $i++): ?>
            #galeria-<?php echo $i; ?>:checked ~ .galeria-principal #foto-<?php echo $i; ?> { display: block; }
            #galeria-<?php echo $i; ?>:checked ~ .galeria-miniaturas label[for="galeria-<?php echo $i; ?>"] { border-color: var(--cor-primaria); box-shadow: 0 0 0 3px var(--cor-primaria); opacity: 1; }
            <?php endfor; ?>
          </style>
          <div class="anuncio-galeria">
            <?php for ($i = 1; $i <= $nFotos; $i++): ?>
            <input type="radio" name="galeria" id="galeria-<?php echo $i; ?>" class="galeria-radio"<?php echo $i === 1 ? " checked" : ""; ?>>
            <?php endfor; ?>

            <div class="galeria-principal">
              <?php if ($fotos): foreach ($fotos as $i => $f): ?>
              <img id="foto-<?php echo $i + 1; ?>" class="galeria-foto" src="<?php echo e($f["link"]); ?>" alt="<?php echo e($f["legenda"] ?: $a["nome"] . " — foto " . ($i + 1)); ?>">
              <?php endforeach; else: ?>
              <img id="foto-1" class="galeria-foto" src="img/Engrenagem v4.png" alt="Anúncio sem fotos" style="object-fit:contain;padding:3rem;box-sizing:border-box;background:#f1f1f1">
              <?php endif; ?>
            </div>

            <?php if (count($fotos) > 1): ?>
            <div class="galeria-miniaturas">
              <?php foreach ($fotos as $i => $f): ?>
              <label for="galeria-<?php echo $i + 1; ?>" class="miniatura">
                <span class="visually-hidden">Ver foto <?php echo $i + 1; ?> de <?php echo count($fotos); ?></span>
                <img src="<?php echo e($f["link"]); ?>" alt="" loading="lazy">
              </label>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="anuncio-tags">
            <?php if ($a["condicao"] === "PAUSADO"): ?><span class="tag">Pausado</span><?php endif; ?>
            <span class="tag tag-venda">Venda</span>
            <?php if ($negociavel): ?><span class="tag tag-negociavel">Valor negociável</span><?php endif; ?>
          </div>

          <h1 id="anuncio-titulo" class="anuncio-titulo"><?php echo e($a["nome"]); ?></h1>
          <p class="anuncio-meta"><?php echo e(implode(" · ", $metaPartes)); ?></p>

          <div class="anuncio-descricao">
            <h2>Descrição</h2>
            <p><?php echo nl2br(e($a["descricao"])); ?></p>
          </div>

          <div class="anuncio-specs">
            <h2>Especificações</h2>
            <dl class="specs-lista">
              <?php foreach ($specs as $sp): if (trim((string)$sp[1]) === "") continue; ?>
              <div>
                <dt><?php echo e($sp[0]); ?></dt>
                <dd><?php echo e($sp[1]); ?></dd>
              </div>
              <?php endforeach; ?>
            </dl>
          </div>

        </div>

        <aside class="anuncio-lateral">

          <div class="anuncio-card-preco">
            <p class="anuncio-preco"><?php echo e(precoFmt($a["valor"])); ?></p>
            <p class="anuncio-preco-nota"><?php echo $negociavel ? "Aceita negociação de valores" : "Preço fixo"; ?></p>
            <div class="anuncio-badges">
              <span class="badge">Frete a combinar</span>
            </div>
            <?php if ($dono): ?>
              <a class="btn btn-primary anuncio-cta" href="cadastro-anuncio.php?id=<?php echo (int)$a["id_anuncio"]; ?>">Editar anúncio</a>
              <p class="anuncio-preco-nota">Este anúncio é da sua empresa.</p>
            <?php else: ?>
              <?php if ($temPreco && $a["condicao"] === "ATIVO"): ?>
              <a class="btn btn-primary anuncio-cta" href="pagamento.php?id=<?php echo (int)$a["id_anuncio"]; ?>">Comprar</a>
              <?php else: ?>
              <p class="anuncio-preco-nota">Valor sob consulta: negocie direto com o vendedor.</p>
              <?php endif; ?>
              <a class="btn btn-outline-preto anuncio-cta" href="#vendedor" role="button" data-abre-modal="modal-telefone" aria-haspopup="dialog">Conversar com o Vendedor</a>
            <?php endif; ?>
          </div>

          <div class="vendedor-card" id="vendedor">
            <div class="vendedor-cabecalho">
              <a class="vendedor-avatar" href="<?php echo e($urlVendedor); ?>" aria-hidden="true" tabindex="-1" style="text-decoration:none;overflow:hidden"><?php echo avatarConteudo($a["cnpj_fk"], iniciais($vendedor)); ?></a>
              <div>
                <p class="vendedor-nome"><a href="<?php echo e($urlVendedor); ?>" style="color:inherit"><?php echo e($vendedor); ?></a></p>
                <p class="vendedor-local"><?php echo e($a["nome_fantasia"] . " · " . $local); ?></p>
              </div>
            </div>

            <div class="vendedor-avaliacao">
              <?php if ($totalAval): ?>
              <span class="engrenagens" role="img" aria-label="Avaliação: <?php echo e(number_format($mediaAval, 1, ",", "")); ?> de 5 engrenagens">
                <?php for ($g = 1; $g <= 5; $g++):
                    $arq = $mediaAval >= $g ? "Engrenagem no logo.png" : ($mediaAval >= $g - 0.5 ? "Engrenagem no logo metade.png" : "Engrenagem no logo vazia.png"); ?>
                <span class="engrenagem-item"><img src="img/<?php echo e($arq); ?>" alt="" width="18" height="18"></span>
                <?php endfor; ?>
              </span>
              <span class="avaliacao-numero"><?php echo e(number_format((float)$aval["media"], 1, ",", "")); ?> de 5 · <?php echo $totalAval; ?> <?php echo $totalAval === 1 ? "avaliação" : "avaliações"; ?></span>
              <?php else: ?>
              <span class="avaliacao-numero">Ainda sem avaliações</span>
              <?php endif; ?>
            </div>

            <ul class="vendedor-info-lista">
              <?php if ($a["verificada"]): ?><li>Vendedor verificado</li><?php endif; ?>
              <li>Membro desde <?php echo e(date("Y", strtotime($a["data_cadastro"]))); ?></li>
            </ul>
            <a class="btn btn-outline-preto anuncio-cta" href="<?php echo e($urlVendedor); ?>" style="margin-top:var(--space-sm)">Ver perfil do vendedor</a>
          </div>

        </aside>

      </div>
    </section>

    <?php if ($similares): ?>
    <section class="similares" aria-labelledby="similares-titulo">
      <div class="section-inner">
        <h2 id="similares-titulo">Anúncios similares</h2>
        <div class="cards-grid">
          <?php foreach ($similares as $sim) { echo cardAnuncio($sim); } ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

  </main>

  <?php if (!$dono): ?>
  <!-- Pop-up "Conversar com o Vendedor": telefone cadastrado da empresa vendedora -->
  <dialog class="modal" id="modal-telefone" aria-labelledby="modal-telefone-titulo">
    <button type="button" class="modal-fechar" data-fecha-modal aria-label="Fechar">×</button>
    <span class="modal-icone" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="26" height="26" focusable="false"><path d="M6.6 3h3.2l1.6 4.6-2.2 1.6a12.5 12.5 0 0 0 5.6 5.6l1.6-2.2 4.6 1.6v3.2a2 2 0 0 1-2 2C10.7 19.4 4.6 13.3 4.6 5a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path></svg>
    </span>
    <h2 id="modal-telefone-titulo">Fale com o vendedor</h2>
    <?php if ($logado && $telefone !== ""): ?>
    <p><?php echo e($vendedor); ?> · <?php echo e($a["nome_fantasia"]); ?></p>
    <a class="modal-telefone" href="tel:+<?php echo e($telDigitos); ?>"><?php echo e($telefone); ?></a>
    <p>Informe que viu o anúncio “<?php echo e($a["nome"]); ?>” na Industrade.</p>
    <div class="modal-acoes">
      <a class="btn btn-primary" href="tel:+<?php echo e($telDigitos); ?>">Ligar agora</a>
      <button type="button" class="btn btn-ghost" data-fecha-modal style="border-color:var(--cor-preto)">Fechar</button>
    </div>
    <?php elseif ($logado): ?>
    <p>Este vendedor não tem telefone cadastrado.</p>
    <div class="modal-acoes">
      <a class="btn btn-primary" href="<?php echo e($urlVendedor); ?>">Ver perfil do vendedor</a>
    </div>
    <?php else: ?>
    <p>Entre na sua conta para ver o telefone do vendedor.</p>
    <div class="modal-acoes">
      <a class="btn btn-primary" href="login.php">Entrar</a>
      <a class="btn btn-ghost" href="cadastro.php" style="border-color:var(--cor-preto)">Criar conta</a>
    </div>
    <?php endif; ?>
  </dialog>
  <?php endif; ?>

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
  <script src="js/modal.js"></script>

</body>
</html>
