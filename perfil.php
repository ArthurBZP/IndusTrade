<?php
include("protect.php");
require_once "foto_lib.php";          // exige login (usa $_SESSION["id"] = CNPJ só com números)
require_once "conexao.php";

function h($v) { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }

$cnpjLogado = $_SESSION["id"];

// Mensagem exibida depois de criar/editar/excluir um anúncio
$mensagens = [
    "criado"         => ["ok",   "Anúncio publicado com sucesso!"],
    "atualizado"     => ["ok",   "Anúncio atualizado com sucesso!"],
    "excluido"       => ["ok",   "Anúncio excluído."],
    "nao_encontrado" => ["erro", "Anúncio não encontrado."],
    "csrf"           => ["erro", "Sessão expirada. Tente novamente."],
    "foto_ok"        => ["ok",   "Foto de perfil atualizada!"],
    "foto_removida"  => ["ok",   "Foto de perfil removida."],
    "foto_invalida"  => ["erro", "Envie uma imagem JPG, PNG ou WEBP."],
    "foto_grande"    => ["erro", "A foto deve ter no máximo 2 MB."],
    "foto_erro"      => ["erro", "Não foi possível salvar a foto. Tente novamente."],
    "erro"           => ["erro", "Não foi possível concluir a operação. Tente novamente."],
];
$flash = $mensagens[$_GET["msg"] ?? ""] ?? null;
$abaInicial = ($_GET["aba"] ?? "") === "produtos" ? "produtos" : "dados";

try {
    // Dados da empresa logada + endereço
    $stmt = $pdo->prepare(
        "SELECT e.cnpj, e.razao_social, e.nome_fantasia, e.email, e.telefone,
                en.cep, en.logradouro, en.numero, en.complemento, en.bairro, en.cidade, en.uf
           FROM EMPRESA e
           JOIN ENDERECO en ON en.id_endereco = e.id_endereco_fk
          WHERE e.cnpj = ?"
    );
    $stmt->execute([$cnpjLogado]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {                       // sessão aponta para uma empresa que não existe mais
        header("Location: logout.php");
        exit;
    }

    // Anúncios da empresa (ativos/pausados)
    $stmt = $pdo->prepare(
        "SELECT a.id_anuncio, a.nome, a.valor, a.tipo_preco, a.condicao, a.estado_conservacao,
                c.tipo AS categoria,
                m.ano_fabricacao,
                r.quantidade, r.unidade_medida
           FROM ANUNCIO a
           JOIN CATEGORIA c ON c.id_categoria = a.id_categoria_fk
           LEFT JOIN MAQUINARIO m ON m.id_anuncio_fk = a.id_anuncio
           LEFT JOIN RECURSO r    ON r.id_anuncio_fk = a.id_anuncio
          WHERE a.cnpj_fk = ? AND a.condicao IN ('ATIVO','PAUSADO')
          ORDER BY a.data_publicacao DESC, a.id_anuncio DESC"
    );
    $stmt->execute([$cnpjLogado]);
    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Produtos vendidos (itens de pedidos de outros compradores)
    $stmt = $pdo->prepare(
        "SELECT a.nome, ip.valor_unitario, ip.quantidade, p.data_criacao,
                comp.nome_fantasia AS comprador
           FROM ANUNCIO a
           JOIN ITEM_PEDIDO ip ON ip.id_anuncio_pk_fk = a.id_anuncio
           JOIN PEDIDO p       ON p.id_pedido = ip.id_pedido_pk_fk
           JOIN EMPRESA comp   ON comp.cnpj = p.cnpj_comprador_fk
          WHERE a.cnpj_fk = ? AND p.condicao_atual <> 'CARRINHO' AND p.condicao_atual <> 'CANCELADO'
          ORDER BY p.data_criacao DESC, p.id_pedido DESC"
    );
    $stmt->execute([$cnpjLogado]);
    $vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar o perfil.");
}

// Formatações para exibição
$fotoUrl = fotoPerfilUrl($empresa["cnpj"]);
$cnpjFmt = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $empresa["cnpj"]);

// Iniciais para o avatar (até 2 letras, ignorando "de/da/do...")
$iniciais = "";
foreach (preg_split('/\s+/', trim($empresa["nome_fantasia"])) as $palavra) {
    if (mb_strlen($palavra) > 2 && mb_strlen($iniciais) < 2) {
        $iniciais .= mb_strtoupper(mb_substr($palavra, 0, 1));
    }
}
if ($iniciais === "") $iniciais = mb_strtoupper(mb_substr($empresa["nome_fantasia"], 0, 2));

function preco($valor, $tipo) {
    if ((float)$valor <= 0) return "Sob consulta";
    return "R$ " . number_format((float)$valor, 2, ",", ".") . ($tipo === "NEGOCIAVEL" ? " (negociável)" : "");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu perfil | Industrade</title>
  <meta name="description" content="Perfil da empresa, produtos anunciados e pedidos na Industrade.">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notificacoes.css">
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
      <div class="card-form">
        <div class="perfil-topo">
          <div class="perfil-foto-bloco">
            <div class="perfil-foto" id="perfil-foto" aria-label="Foto de perfil"><?php if ($fotoUrl): ?><img src="<?php echo h($fotoUrl); ?>" alt="Foto de perfil de <?php echo h($empresa["nome_fantasia"]); ?>"><?php else: echo h($iniciais); endif; ?></div>
            <div>
              <h1 class="perfil-nome"><?php echo h($empresa["nome_fantasia"]); ?></h1>
              <p class="perfil-cnpj">CNPJ <?php echo h($cnpjFmt); ?></p>
              <form method="POST" action="foto_salvar.php" enctype="multipart/form-data" style="margin-top:0.25rem">
                <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                <label class="link-discreto" for="foto-input" style="display:inline-block;cursor:pointer"><?php echo $fotoUrl ? "Alterar foto" : "Adicionar foto"; ?></label>
                <input type="file" id="foto-input" name="foto" accept="image/jpeg,image/png,image/webp" class="visually-hidden" onchange="if (this.files[0] && this.files[0].size > 2*1024*1024) { alert('A foto deve ter no máximo 2 MB.'); this.value=''; return; } this.form.submit();">
                <noscript><button type="submit" class="link-discreto">Enviar</button></noscript>
                <?php if ($fotoUrl): ?>
                <button type="submit" name="acao" value="remover" class="link-discreto" style="background:none;border:0;cursor:pointer;margin-left:0.75rem" onclick="return confirm('Remover a foto de perfil?');">Remover</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
          <div class="perfil-acoes">
            <!-- NOVO: sino de notificações -->
            <div class="notif-wrap">
              <button type="button" class="notif-sino" id="sino-notificacoes" aria-label="Notificações: nenhuma nova" aria-haspopup="true" aria-expanded="false" aria-controls="notif-painel">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                  <path d="M12 3a6 6 0 0 0-6 6v3.5L4 16v1h16v-1l-2-3.5V9a6 6 0 0 0-6-6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path>
                  <path d="M9.5 20a2.5 2.5 0 0 0 5 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                </svg>
                <span class="notif-badge" id="notif-badge" hidden>0</span>
              </button>
              <div class="notif-painel" id="notif-painel" role="region" aria-label="Notificações" hidden>
                <div class="notif-cabecalho">
                  <h2>Notificações</h2>
                  <button type="button" class="notif-link" id="notif-marcar-lidas" hidden>Marcar todas como lidas</button>
                </div>
                <ul class="notif-lista" id="notif-lista"></ul>
                <p class="notif-vazio" id="notif-vazio">Você não tem notificações.</p>
                <div class="notif-rodape">
                  <button type="button" class="notif-link" id="notif-ver-vendidos">Ver produtos vendidos</button>
                  <button type="button" class="notif-link" id="notif-simular" hidden>Simular venda (demo)</button>
                </div>
              </div>
              <div class="visually-hidden" id="notif-live" role="status" aria-live="polite"></div>
            </div>
            <a class="btn btn-primary" href="cadastro-anuncio.php">Anunciar Produto</a>
            <a class="btn btn-outline-preto" href="pedidos.php">Minhas Compras</a>
            <a class="btn btn-ghost" href="logout.php" style="border-color:var(--cor-preto)">Sair da conta</a>
          </div>
        </div>
      </div>

      <?php if ($flash): ?>
      <p role="<?php echo $flash[0] === "ok" ? "status" : "alert"; ?>" style="color:<?php echo $flash[0] === "ok" ? "#1e8449" : "#c0392b"; ?>;margin-bottom:var(--space-sm)"><?php echo h($flash[1]); ?></p>
      <?php endif; ?>

      <div class="card-form">
        <div class="abas" role="tablist" aria-label="Seções do perfil">
          <button class="aba" role="tab" id="aba-dados" aria-controls="painel-dados" aria-selected="<?php echo $abaInicial === "dados" ? "true" : "false"; ?>">Dados cadastrais</button>
          <button class="aba" role="tab" id="aba-produtos" aria-controls="painel-produtos" aria-selected="<?php echo $abaInicial === "produtos" ? "true" : "false"; ?>">Produtos Anunciados</button>
          <button class="aba" role="tab" id="aba-vendidos" aria-controls="painel-vendidos" aria-selected="false">Produtos Vendidos</button>
        </div>

        <div id="painel-dados" role="tabpanel" aria-labelledby="aba-dados"<?php echo $abaInicial === "dados" ? "" : " hidden"; ?>>
          <dl class="dados-lista">
            <div><dt>CNPJ</dt><dd><?php echo h($cnpjFmt); ?></dd></div>
            <div><dt>Razão Social</dt><dd><?php echo h($empresa["razao_social"]); ?></dd></div>
            <div><dt>Nome Fantasia</dt><dd><?php echo h($empresa["nome_fantasia"]); ?></dd></div>
            <div><dt>E-mail</dt><dd><?php echo h($empresa["email"]); ?></dd></div>
            <div><dt>Telefone</dt><dd><?php echo h($empresa["telefone"]); ?></dd></div>
            <div><dt>CEP</dt><dd><?php echo h($empresa["cep"]); ?></dd></div>
            <div><dt>Logradouro</dt><dd><?php echo h($empresa["logradouro"]); ?></dd></div>
            <div><dt>Número</dt><dd><?php echo h($empresa["numero"]); ?></dd></div>
            <div><dt>Complemento</dt><dd><?php echo h($empresa["complemento"] ?: "—"); ?></dd></div>
            <div><dt>Bairro</dt><dd><?php echo h($empresa["bairro"]); ?></dd></div>
            <div><dt>Cidade</dt><dd><?php echo h($empresa["cidade"]); ?></dd></div>
            <div><dt>UF</dt><dd><?php echo h($empresa["uf"]); ?></dd></div>
          </dl>
        </div>

        <div id="painel-produtos" role="tabpanel" aria-labelledby="aba-produtos"<?php echo $abaInicial === "produtos" ? "" : " hidden"; ?>>
          <ul class="lista-produtos" id="lista-produtos">
          <?php foreach ($anuncios as $a):
              $meta = $a["ano_fabricacao"] ? "Maquinário" : ($a["quantidade"] !== null ? "Recurso" : $a["categoria"]);
              if ($a["ano_fabricacao"]) $meta .= " · Ano " . $a["ano_fabricacao"];
              if ($a["quantidade"] !== null) $meta .= " · " . rtrim(rtrim(number_format((float)$a["quantidade"], 2, ",", "."), "0"), ",") . " " . $a["unidade_medida"];
              if ($a["estado_conservacao"]) $meta .= " · " . $a["estado_conservacao"];
              if ($a["condicao"] === "PAUSADO") $meta .= " · Pausado";
          ?>
          <li class="item-produto">
            <div>
              <h3><?php echo h($a["nome"]); ?></h3>
              <p class="card-meta"><?php echo h($meta); ?></p>
              <p class="card-preco"><?php echo h(preco($a["valor"], $a["tipo_preco"])); ?></p>
            </div>
            <div class="item-acoes">
              <a class="btn btn-primary btn-sm" href="cadastro-anuncio.php?id=<?php echo (int)$a["id_anuncio"]; ?>">Editar</a>
              <form method="POST" action="anuncio_excluir.php" style="display:inline" onsubmit="return confirm('Excluir este anúncio? Essa ação não pode ser desfeita.');">
                <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                <input type="hidden" name="id_anuncio" value="<?php echo (int)$a["id_anuncio"]; ?>">
                <button type="submit" class="btn btn-perigo btn-sm">Excluir</button>
              </form>
            </div>
          </li>
          <?php endforeach; ?>
          </ul>
          <p class="vazio" id="produtos-vazio"<?php echo $anuncios ? " hidden" : ""; ?>>Você ainda não tem produtos anunciados.</p>
        </div>

        <!-- NOVO: painel Produtos Vendidos -->
        <div id="painel-vendidos" role="tabpanel" aria-labelledby="aba-vendidos" hidden>
          <ul class="lista-produtos" id="lista-vendidos">
          <?php foreach ($vendidos as $v): ?>
          <li class="item-produto item-vendido">
            <div>
              <h3><?php echo h($v["nome"]); ?> <span class="tag-vendido">Vendido</span></h3>
              <p class="card-preco"><?php echo h(preco($v["valor_unitario"], "FIXO")); ?></p>
              <dl class="vendido-dados">
                <div><dt>Comprador</dt><dd><?php echo h($v["comprador"]); ?></dd></div>
                <div><dt>Data da venda</dt><dd><?php echo h(date("d/m/Y", strtotime($v["data_criacao"]))); ?></dd></div>
              </dl>
            </div>
          </li>
          <?php endforeach; ?>
          </ul>
          <p class="vazio" id="vendidos-vazio"<?php echo $vendidos ? " hidden" : ""; ?>>Você ainda não vendeu nenhum produto.</p>
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
  </script>
  <script src="js/notificacoes.js"></script>
</body>
</html>