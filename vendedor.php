<?php
require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/anuncio_lib.php";
require_once __DIR__ . "/foto_lib.php";

function h($v) { return e($v); }

// Perfil PÚBLICO de um vendedor: vendedor.php?cnpj=12345678000190
$cnpj   = preg_replace('/\D/', '', (string)($_GET["cnpj"] ?? ""));
$logado = isset($_SESSION["id"]);
$ehEu   = $logado && (string)$_SESSION["id"] === $cnpj;

try {
    $v = false;
    if (strlen($cnpj) === 14) {
        $stmt = $pdo->prepare(
            "SELECT e.cnpj, e.razao_social, e.nome_fantasia, e.nome_responsavel, e.email, e.telefone,
                    e.data_cadastro, e.verificada, en.cidade, en.uf
               FROM EMPRESA e
               JOIN ENDERECO en ON en.id_endereco = e.id_endereco_fk
              WHERE e.cnpj = ?"
        );
        $stmt->execute([$cnpj]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$v) {                                   // CNPJ inválido ou inexistente
        header("Location: anuncios.php");
        exit;
    }

    // Anúncios ativos do vendedor (mesmos cards do catálogo)
    $anuncios = buscarAnuncios($pdo, "AND a.cnpj_fk = ?", [$cnpj]);

    // Avaliações recebidas
    $stmt = $pdo->prepare(
        "SELECT AVG(nota) AS media, COUNT(*) AS total FROM AVALIACAO WHERE cnpj_avaliado_fk = ?"
    );
    $stmt->execute([$cnpj]);
    $aval = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT av.nota, av.comentario, av.data_avaliacao, emp.nome_fantasia AS avaliador
           FROM AVALIACAO av
           JOIN EMPRESA emp ON emp.cnpj = av.cnpj_avaliador_fk
          WHERE av.cnpj_avaliado_fk = ?
          ORDER BY av.data_avaliacao DESC, av.id_avaliacao DESC
          LIMIT 50"
    );
    $stmt->execute([$cnpj]);
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vendas realizadas (itens de pedidos já finalizados/andamento, não carrinho nem cancelado)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
           FROM ITEM_PEDIDO ip
           JOIN ANUNCIO a ON a.id_anuncio = ip.id_anuncio_pk_fk
           JOIN PEDIDO p  ON p.id_pedido  = ip.id_pedido_pk_fk
          WHERE a.cnpj_fk = ? AND p.condicao_atual NOT IN ('CARRINHO','CANCELADO')"
    );
    $stmt->execute([$cnpj]);
    $vendas = (int)$stmt->fetchColumn();

    // Compras do usuário logado com ESTE vendedor que ainda não foram avaliadas.
    // Só quem comprou pode avaliar, e cada pedido recebe uma única avaliação.
    $pendentes = [];
    if ($logado && !$ehEu) {
        $stmt = $pdo->prepare(
            "SELECT p.id_pedido, p.data_criacao, a.nome
               FROM PEDIDO p
               JOIN ITEM_PEDIDO ip ON ip.id_pedido_pk_fk = p.id_pedido
               JOIN ANUNCIO a      ON a.id_anuncio = ip.id_anuncio_pk_fk
              WHERE p.cnpj_comprador_fk = ? AND a.cnpj_fk = ?
                AND p.condicao_atual NOT IN ('CARRINHO','CANCELADO')
                AND NOT EXISTS (SELECT 1 FROM AVALIACAO av
                                 WHERE av.id_pedido_fk = p.id_pedido AND av.cnpj_avaliador_fk = p.cnpj_comprador_fk)
              ORDER BY p.data_criacao DESC, p.id_pedido DESC"
        );
        $stmt->execute([$_SESSION["id"], $cnpj]);
        $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $ex) {
    die("Erro ao carregar o perfil do vendedor.");
}

$cnpjFmt    = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $v["cnpj"]);
$local      = $v["cidade"] . "/" . $v["uf"];
$totalAval  = (int)$aval["total"];
$mediaAval  = $totalAval ? round(((float)$aval["media"]) * 2) / 2 : 0;
$abaInicial = in_array($_GET["aba"] ?? "", ["anuncios", "avaliacoes"], true) ? $_GET["aba"] : "sobre";

// Pedido a avaliar: o que veio no link (?avaliar=ID, logo depois da compra) ou o mais recente pendente
$pedidoAvaliar = null;
$idAvaliar = (int)($_GET["avaliar"] ?? 0);
foreach ($pendentes as $p) {
    if ((int)$p["id_pedido"] === $idAvaliar) { $pedidoAvaliar = $p; break; }
}
if (!$pedidoAvaliar && $pendentes) $pedidoAvaliar = $pendentes[0];

$mensagens = [
    "avaliado"    => ["ok",   "Avaliação enviada. Obrigado por avaliar o vendedor!"],
    "ja_avaliado" => ["erro", "Você já avaliou esta compra."],
    "nota"        => ["erro", "Escolha uma nota de 1 a 5 estrelas."],
    "csrf"        => ["erro", "Sessão expirada. Tente novamente."],
];
$flash = $mensagens[$_GET["msg"] ?? ""] ?? null;
if ($flash || $idAvaliar) $abaInicial = "avaliacoes";   // chegou para avaliar: abre direto na aba

// Engrenagens (inteiras/meias/vazias) para uma nota
function engrenagens($nota) {
    $html = "";
    for ($g = 1; $g <= 5; $g++) {
        $arq = $nota >= $g ? "Engrenagem no logo.png" : ($nota >= $g - 0.5 ? "Engrenagem no logo metade.png" : "Engrenagem no logo vazia.png");
        $html .= '<span class="engrenagem-item"><img src="img/' . e($arq) . '" alt="" width="18" height="18"></span>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($v["nome_fantasia"]); ?> — Vendedor | Industrade</title>
  <meta name="description" content="<?php echo h("Perfil do vendedor " . $v["nome_fantasia"] . " na Industrade: anúncios e avaliações."); ?>">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <link rel="stylesheet" href="css/forms.css">
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

  <main id="conteudo-principal" class="pagina-interna">
    <div class="section-inner">

      <nav class="trilha-nav" aria-label="Trilha de navegação" style="margin-bottom:var(--space-sm)">
        <a href="index.php">Início</a> /
        <a href="anuncios.php">Anúncios</a> /
        <span class="trilha-atual"><?php echo h($v["nome_fantasia"]); ?></span>
      </nav>

      <div class="card-form">
        <div class="perfil-topo">
          <div class="perfil-foto-bloco">
            <div class="perfil-foto" aria-hidden="true"><?php echo avatarConteudo($v["cnpj"], iniciais($v["nome_fantasia"])); ?></div>
            <div>
              <h1 class="perfil-nome"><?php echo h($v["nome_fantasia"]); ?></h1>
              <p class="perfil-cnpj"><?php echo h($local); ?> · Membro desde <?php echo h(date("Y", strtotime($v["data_cadastro"]))); ?></p>
              <p style="margin-top:0.25rem">
                <?php if ($v["verificada"]): ?><span class="tag tag-venda">Vendedor verificado</span><?php endif; ?>
              </p>
              <div class="vendedor-avaliacao" style="margin-top:0.25rem">
                <?php if ($totalAval): ?>
                <span class="engrenagens" role="img" aria-label="Avaliação: <?php echo h(number_format($mediaAval, 1, ",", "")); ?> de 5 engrenagens"><?php echo engrenagens($mediaAval); ?></span>
                <span class="avaliacao-numero"><?php echo h(number_format((float)$aval["media"], 1, ",", "")); ?> de 5 · <?php echo $totalAval; ?> <?php echo $totalAval === 1 ? "avaliação" : "avaliações"; ?></span>
                <?php else: ?>
                <span class="avaliacao-numero">Ainda sem avaliações</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="perfil-acoes">
            <?php if ($ehEu): ?>
              <a class="btn btn-primary" href="perfil.php">Ir para meu perfil</a>
            <?php else: ?>
              <a class="btn btn-primary" href="#painel-anuncios" onclick="document.getElementById('aba-anuncios').click();">Ver anúncios</a>
              <?php if ($pedidoAvaliar): ?>
              <a class="btn btn-outline-preto" href="#avaliar" onclick="document.getElementById('aba-avaliacoes').click();">Avaliar vendedor</a>
              <?php endif; ?>
              <a class="btn btn-outline-preto" href="anuncios.php">Voltar ao catálogo</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card-form">
        <div class="abas" role="tablist" aria-label="Seções do perfil do vendedor">
          <button class="aba" role="tab" id="aba-sobre" aria-controls="painel-sobre" aria-selected="<?php echo $abaInicial === "sobre" ? "true" : "false"; ?>">Sobre</button>
          <button class="aba" role="tab" id="aba-anuncios" aria-controls="painel-anuncios" aria-selected="<?php echo $abaInicial === "anuncios" ? "true" : "false"; ?>">Anúncios (<?php echo count($anuncios); ?>)</button>
          <button class="aba" role="tab" id="aba-avaliacoes" aria-controls="painel-avaliacoes" aria-selected="<?php echo $abaInicial === "avaliacoes" ? "true" : "false"; ?>">Avaliações (<?php echo $totalAval; ?>)</button>
        </div>

        <div id="painel-sobre" role="tabpanel" aria-labelledby="aba-sobre"<?php echo $abaInicial === "sobre" ? "" : " hidden"; ?>>
          <dl class="dados-lista">
            <div><dt>Nome Fantasia</dt><dd><?php echo h($v["nome_fantasia"]); ?></dd></div>
            <div><dt>Razão Social</dt><dd><?php echo h($v["razao_social"]); ?></dd></div>
            <div><dt>CNPJ</dt><dd><?php echo h($cnpjFmt); ?></dd></div>
            <?php if (trim((string)$v["nome_responsavel"]) !== ""): ?>
            <div><dt>Responsável</dt><dd><?php echo h($v["nome_responsavel"]); ?></dd></div>
            <?php endif; ?>
            <div><dt>Localização</dt><dd><?php echo h($local); ?></dd></div>
            <div><dt>Membro desde</dt><dd><?php echo h(date("d/m/Y", strtotime($v["data_cadastro"]))); ?></dd></div>
            <div><dt>Anúncios ativos</dt><dd><?php echo count($anuncios); ?></dd></div>
            <div><dt>Vendas realizadas</dt><dd><?php echo $vendas; ?></dd></div>
            <?php if ($logado): ?>
            <div><dt>E-mail</dt><dd><a href="mailto:<?php echo h($v["email"]); ?>"><?php echo h($v["email"]); ?></a></dd></div>
            <div><dt>Telefone</dt><dd><?php echo h($v["telefone"]); ?></dd></div>
            <?php endif; ?>
          </dl>
          <?php if (!$logado): ?>
          <p class="texto-apoio" style="margin-top:var(--space-sm)"><a href="login.php">Entre na sua conta</a> para ver o e-mail e o telefone do vendedor.</p>
          <?php endif; ?>
        </div>

        <div id="painel-anuncios" role="tabpanel" aria-labelledby="aba-anuncios"<?php echo $abaInicial === "anuncios" ? "" : " hidden"; ?>>
          <?php if ($anuncios): ?>
          <div class="cards-grid">
            <?php foreach ($anuncios as $a) { echo cardAnuncio($a); } ?>
          </div>
          <?php else: ?>
          <p class="vazio">Este vendedor não tem anúncios ativos no momento.</p>
          <?php endif; ?>
        </div>

        <div id="painel-avaliacoes" role="tabpanel" aria-labelledby="aba-avaliacoes"<?php echo $abaInicial === "avaliacoes" ? "" : " hidden"; ?>>
          <?php if ($flash): ?>
          <p role="<?php echo $flash[0] === "ok" ? "status" : "alert"; ?>" style="color:<?php echo $flash[0] === "ok" ? "#1e8449" : "#c0392b"; ?>;margin-bottom:var(--space-sm)"><?php echo h($flash[1]); ?></p>
          <?php endif; ?>

          <?php if ($pedidoAvaliar): ?>
          <!-- Avaliação do vendedor: aparece só para quem comprou dele e ainda não avaliou o pedido -->
          <form class="avaliar-caixa" id="avaliar" method="POST" action="avaliacao_salvar.php">
            <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
            <input type="hidden" name="id_pedido" value="<?php echo (int)$pedidoAvaliar["id_pedido"]; ?>">
            <h2>Avalie <?php echo h($v["nome_fantasia"]); ?></h2>
            <p class="card-meta">Sua compra: <?php echo h($pedidoAvaliar["nome"]); ?> · <?php echo h(date("d/m/Y", strtotime($pedidoAvaliar["data_criacao"]))); ?></p>

            <div class="campo">
              <span class="rotulo" id="nota-rotulo">Nota do vendedor</span>
              <fieldset class="estrelas" aria-labelledby="nota-rotulo">
                <?php $legendas = [5 => "Excelente", 4 => "Bom", 3 => "Regular", 2 => "Ruim", 1 => "Péssimo"];
                      foreach ($legendas as $n => $txt): ?>
                <input type="radio" name="nota" id="nota-<?php echo $n; ?>" value="<?php echo $n; ?>" required data-legenda="<?php echo $n . ($n === 1 ? " estrela" : " estrelas") . " — " . h($txt); ?>">
                <label for="nota-<?php echo $n; ?>" title="<?php echo $n . ($n === 1 ? " estrela" : " estrelas"); ?>"><span aria-hidden="true">★</span><span class="visually-hidden"><?php echo $n . ($n === 1 ? " estrela" : " estrelas") . " — " . h($txt); ?></span></label>
                <?php endforeach; ?>
              </fieldset>
              <span class="estrelas-legenda card-meta" id="nota-legenda" aria-live="polite">Escolha de 1 a 5 estrelas</span>
            </div>

            <div class="campo">
              <label for="comentario">Comentário <span class="opcional">(opcional)</span></label>
              <textarea id="comentario" name="comentario" maxlength="1000" placeholder="Conte como foi a negociação com este vendedor."></textarea>
            </div>

            <div class="acoes-form">
              <button type="submit" class="btn btn-primary">Enviar avaliação</button>
            </div>
          </form>
          <?php endif; ?>

          <?php if ($avaliacoes): ?>
          <ul class="lista-produtos">
            <?php foreach ($avaliacoes as $av): ?>
            <li class="item-produto">
              <div>
                <h3><?php echo h($av["avaliador"]); ?></h3>
                <p class="vendedor-avaliacao">
                  <span class="engrenagens" role="img" aria-label="Nota <?php echo h(number_format((float)$av["nota"], 1, ",", "")); ?> de 5"><?php echo engrenagens(round((float)$av["nota"] * 2) / 2); ?></span>
                  <span class="avaliacao-numero"><?php echo h(number_format((float)$av["nota"], 1, ",", "")); ?> · <?php echo h(date("d/m/Y", strtotime($av["data_avaliacao"]))); ?></span>
                </p>
                <?php if (trim((string)$av["comentario"]) !== ""): ?>
                <p class="card-meta"><?php echo nl2br(h($av["comentario"])); ?></p>
                <?php endif; ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php else: ?>
          <p class="vazio">Este vendedor ainda não recebeu avaliações.</p>
          <?php endif; ?>
        </div>
      </div>

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
    // Abas
    var abas = document.querySelectorAll('.aba');
    abas.forEach(function (aba) {
      aba.addEventListener('click', function () {
        abas.forEach(function (a) {
          var ativa = a === aba;
          a.setAttribute('aria-selected', ativa);
          document.getElementById(a.getAttribute('aria-controls')).hidden = !ativa;
        });
      });
    });

    // Avaliação: mostra por extenso a nota escolhida ("4 estrelas — Bom")
    var legendaNota = document.getElementById('nota-legenda');
    document.querySelectorAll('.estrelas input').forEach(function (r) {
      r.addEventListener('change', function () { legendaNota.textContent = r.getAttribute('data-legenda'); });
    });
  </script>
</body>
</html>
