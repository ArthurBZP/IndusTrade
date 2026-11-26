<?php
// "Minhas compras": lista SOMENTE as compras feitas pela empresa logada, lidas do banco
// (PEDIDO + ITEM_PEDIDO + ANUNCIO). Nada aqui é fixo/de exemplo: sem compras, a lista fica vazia.
include("protect.php");
require_once "conexao.php";
require_once "anuncio_lib.php";

try {
    $stmt = $pdo->prepare(
        "SELECT p.id_pedido, p.data_criacao, p.condicao_atual,
                ip.quantidade, ip.valor_unitario,
                a.nome, a.data_publicacao, a.cnpj_fk AS cnpj_vendedor,
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
    $stmt->execute([$_SESSION["id"]]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Erro ao carregar suas compras.");
}

const STATUS_PEDIDO = [
    "REALIZADO" => "Pedido realizado",
    "ENVIADO"   => "Enviado",
    "ENTREGUE"  => "Entregue",
    "CANCELADO" => "Cancelado",
];
$dataBr = function ($d) { return date("d/m/Y", strtotime($d)); };
$erro   = ($_GET["msg"] ?? "") === "erro";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minhas compras | Industrade</title>
  <meta name="description" content="Compras que sua empresa realizou na Industrade.">
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
      <h1 class="pagina-titulo">Minhas compras</h1>
      <p class="pagina-sub">Compras que sua empresa realizou na Industrade.</p>

      <?php if ($erro): ?>
      <p role="alert" style="color:#c0392b;margin-bottom:var(--space-sm)">Não foi possível concluir a operação. Tente novamente.</p>
      <?php endif; ?>

      <?php if ($compras): ?>
      <ul class="lista-pedidos">
        <?php foreach ($compras as $c):
            $urlVend   = "vendedor.php?cnpj=" . urlencode($c["cnpj_vendedor"]);
            $cancelado = $c["condicao_atual"] === "CANCELADO";
            $nota      = $c["nota"] !== null ? (int)round((float)$c["nota"]) : null;
        ?>
        <li class="item-pedido">
          <div class="pedido-topo">
            <h3><?php echo e($c["nome"]); ?></h3>
            <span class="tag tag-negociavel"><?php echo e(STATUS_PEDIDO[$c["condicao_atual"]] ?? $c["condicao_atual"]); ?></span>
          </div>
          <dl class="pedido-dados">
            <div><dt>Vendedor</dt><dd><a href="<?php echo e($urlVend); ?>" style="text-decoration:underline"><?php echo e($c["vendedor"]); ?></a></dd></div>
            <div><dt>Valor</dt><dd><?php echo e(precoFmt((float)$c["valor_unitario"] * (float)$c["quantidade"])); ?></dd></div>
            <div><dt>Data da compra</dt><dd><?php echo e($dataBr($c["data_criacao"])); ?></dd></div>
            <div><dt>Pedido</dt><dd>Nº <?php echo (int)$c["id_pedido"]; ?></dd></div>
          </dl>
          <?php if (!$cancelado): ?>
          <div class="pedido-rodape">
            <?php if ($nota !== null): ?>
            <span class="card-meta">Sua avaliação do vendedor:</span>
            <span class="estrelas-nota" role="img" aria-label="<?php echo $nota; ?> de 5 estrelas"><?php echo str_repeat("★", $nota); ?><span class="vazia"><?php echo str_repeat("★", 5 - $nota); ?></span></span>
            <?php else: ?>
            <a class="btn btn-primary btn-sm" href="<?php echo e($urlVend . "&avaliar=" . (int)$c["id_pedido"]); ?>#avaliar">Avaliar vendedor</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <p class="vazio">Você ainda não realizou nenhuma compra. <a href="anuncios.php" style="text-decoration:underline;font-weight:var(--peso-semibold)">Ver anúncios</a></p>
      <?php endif; ?>
      <p style="margin-top:var(--space-md)"><a class="btn btn-ghost" href="perfil.php" style="border-color:var(--cor-preto)">← Voltar ao perfil</a></p>
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

</body>
</html>
