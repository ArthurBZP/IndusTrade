<?php
require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/anuncio_lib.php";

try {
    $destaques = buscarAnuncios($pdo, "", [], 4);   // os 4 anúncios mais recentes
} catch (PDOException $e) {
    $destaques = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Industrade — Marketplace de Maquinário Industrial Usado</title>
  <meta name="description" content="Compre, venda e troque maquinário industrial usado com segurança na Industrade, o marketplace B2B para equipamentos pesados.">
  <link rel="preconnect" href="https://images.unsplash.com">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <style>
    .card-maquina-link {
      display: block;
      color: inherit;
      text-decoration: none;
    }
    .card-maquina-link:hover,
    .card-maquina-link:focus {
      color: inherit;
      text-decoration: none;
    }
  </style>
</head>
<body>

  <a class="skip-link" href="#conteudo-principal">Pular para o conteúdo principal</a>

  <header class="site-header">
    <div class="header-inner">

      <a class="logo" href="index.php" aria-label="Industrade — página inicial">
        <img class="logo-mark" src="img/Engrenagem no logo.png" width="36" height="36" alt="" aria-hidden="true">
        <span class="logo-text">Industrade</span>
      </a>

      <!--
      BARRA DE BUSCA AQUI
    -->
      
      <input type="checkbox" id="menu-toggle" class="menu-toggle-input">
      <label for="menu-toggle" class="menu-toggle-label" aria-hidden="false">
        <span class="visually-hidden">Abrir menu de navegação</span>
        <span class="menu-icon"></span>
      </label>

      <nav class="primary-nav" aria-label="Navegação principal">
        <!--
        BOTOES COMPRAR/VENDER/TROCAR/CATEGORIAS
        -->
        <?php include __DIR__ . "/nav_cta.php"; ?>
      </nav>

    </div>
  </header>

  <main id="conteudo-principal">

    <section class="hero" aria-labelledby="hero-titulo"> 
        <div class="hero-inner">
          <h1 id="hero-titulo">Renove sua indústria. Compre, venda e troque maquinário usado com segurança.</h1>
          <p class="hero-sub">O maior marketplace B2B para equipamentos industriais pesados. Expanda sua produção gastando menos.</p>
          <div class="hero-actions">
            <a class="btn btn-primary btn-lg" href="anuncios.php">Explorar maquinário</a>
            <a class="btn btn-outline-preto btn-lg" href="<?php echo isset($_SESSION['id']) ? 'cadastro-anuncio.php' : 'login.php'; ?>">Anunciar equipamento</a>
          </div>
        </div>
      <div class="backlight">
      </div>     
    </section>

    <section id="categorias" class="categorias" aria-labelledby="categorias-titulo">
      <div class="section-inner">
        <h2 id="categorias-titulo">Categorias em destaque</h2>
        <ul class="categorias-grid">
          <li class="categoria-card">
            <svg class="categoria-icone" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
              <circle cx="24" cy="24" r="10" fill="none" stroke="var(--cor-primaria)" stroke-width="3"></circle>
              <circle cx="24" cy="24" r="3" fill="var(--cor-primaria)"></circle>
              <path d="M24 4 L24 10 M24 38 L24 44 M4 24 L10 24 M38 24 L44 24" stroke="var(--cor-primaria)" stroke-width="3" stroke-linecap="round"></path>
            </svg>
            <h3>Metalmecânica</h3>
            <p>Tornos, fresadoras e centros de usinagem.</p>
          </li>
          <li class="categoria-card">
            <svg class="categoria-icone" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
              <rect x="6" y="20" width="20" height="14" fill="none" stroke="var(--cor-primaria)" stroke-width="3"></rect>
              <path d="M26 24 L36 24 L42 30 L42 34 L26 34" fill="none" stroke="var(--cor-primaria)" stroke-width="3"></path>
              <circle cx="14" cy="36" r="3" fill="var(--cor-primaria)"></circle>
              <circle cx="34" cy="36" r="3" fill="var(--cor-primaria)"></circle>
            </svg>
            <h3>Movimentação e logística</h3>
            <p>Empilhadeiras, guindastes e transportadores.</p>
          </li>
          <li class="categoria-card">
            <svg class="categoria-icone" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
              <rect x="8" y="10" width="32" height="28" rx="2" fill="none" stroke="var(--cor-primaria)" stroke-width="3"></rect>
              <path d="M8 20 L40 20 M20 10 L20 38" stroke="var(--cor-primaria)" stroke-width="3"></path>
            </svg>
            <h3>Embalagem e plástico</h3>
            <p>Injetoras, envasadoras e seladoras.</p>
          </li>
          <li class="categoria-card">
            <svg class="categoria-icone" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
              <path d="M26 4 L12 26 L22 26 L20 44 L38 20 L27 20 Z" fill="var(--cor-primaria)"></path>
            </svg>
            <h3>Energia</h3>
            <p>Geradores, compressores e transformadores.</p>
          </li>
        </ul>
      </div>
    </section>

    <section id="vitrine" class="vitrine" aria-labelledby="vitrine-titulo">
      <div class="section-inner">
        <h2 id="vitrine-titulo">Maquinário em destaque</h2>
        <?php if ($destaques): ?>
        <div class="cards-grid">
          <?php foreach ($destaques as $a) { echo cardAnuncio($a); } ?>
        </div>
        <p style="margin-top:var(--space-md)"><a class="btn btn-outline-preto" href="anuncios.php">Ver todos os anúncios</a></p>
        <?php else: ?>
        <p>Ainda não há anúncios publicados. <a href="<?php echo isset($_SESSION["id"]) ? "cadastro-anuncio.php" : "cadastro.php"; ?>">Seja o primeiro a anunciar!</a></p>
        <?php endif; ?>
      </div>
    </section>

    <section class="confianca" aria-labelledby="confianca-titulo">
      <div class="section-inner">
        <h2 id="confianca-titulo">Compre com a segurança que sua fábrica merece</h2>

        <ul class="selos-grid">
          <li class="selo">
            <h3>Vendedores verificados</h3>
            <p>Toda conta de vendedor passa por checagem documental antes de anunciar.</p>
          </li>
          <li class="selo">
            <h3>Pagamento seguro</h3>
            <p>Valores retidos em custódia até a confirmação de entrega do equipamento.</p>
          </li>
          <li class="selo">
            <h3>Garantia Industrade</h3>
            <p>Cobertura de até 90 dias contra vícios ocultos em itens elegíveis.</p>
          </li>
        </ul>

        <div class="beneficios">
          <p class="beneficio-numero"><strong>60%</strong> mais barato que um equipamento novo equivalente</p>
          <p class="beneficio-texto">Comprar usado reduz custo de expansão e dá uma segunda vida útil a máquinas que ainda têm anos de produção pela frente.</p>
        </div>

        <div class="depoimentos">
          <blockquote>
            <p>Compramos duas fresadoras pela Industrade e a economia pagou a reforma do nosso segundo galpão.</p>
            <footer>— Marli Andrade, dona de metalúrgica em Sorocaba/SP</footer>
          </blockquote>
          <blockquote>
            <p>Consegui trocar um gerador parado por uma empilhadeira em menos de duas semanas.</p>
            <footer>— Jonas Pereira, gestor de operações em Joinville/SC</footer>
          </blockquote>
        </div>
      </div>
    </section>

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

</body>
</html>
