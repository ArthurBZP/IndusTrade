<?php
require_once __DIR__ . "/sessao.php";
require_once __DIR__ . "/conexao.php";
require_once __DIR__ . "/anuncio_lib.php";

try {
    $anuncios = buscarAnuncios($pdo);          // todos os anúncios ativos, mais novos primeiro
} catch (PDOException $e) {
    die("Erro ao carregar os anúncios.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Todos os anúncios | Industrade</title>
  <meta name="description" content="Veja todos os anúncios de maquinário industrial usado na Industrade e filtre por categoria e preço.">
  <link rel="preconnect" href="https://images.unsplash.com">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
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
          <li><a href="anuncios.php" aria-current="page">Comprar</a></li>
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
        <span class="trilha-atual">Todos os anúncios</span>
      </nav>
    </div>

    <section class="catalogo" aria-labelledby="catalogo-titulo">
      <div class="section-inner">
        <h1 id="catalogo-titulo" class="catalogo-titulo">Todos os anúncios</h1>
        <p class="catalogo-sub">Explore o maquinário disponível e refine a busca por categoria e faixa de preço.</p>

        <div class="catalogo-layout">

          <details class="filtros" id="filtros">
            <summary>Filtros</summary>
            <form class="filtros-form" id="form-filtros" novalidate>

              <fieldset>
                <legend>Categoria</legend>
            <label class="filtro-check"><input type="checkbox" name="categoria" value="metalmecanica"><span>Metalmecânica</span></label>
            <label class="filtro-check"><input type="checkbox" name="categoria" value="movimentacao"><span>Movimentação e logística</span></label>
            <label class="filtro-check"><input type="checkbox" name="categoria" value="embalagem"><span>Embalagem e plástico</span></label>
            <label class="filtro-check"><input type="checkbox" name="categoria" value="energia"><span>Energia</span></label>
              </fieldset>

              <fieldset>
                <legend>Preço (R$)</legend>
                <div class="filtro-preco">
                  <div class="campo-filtro">
                    <label for="preco-min">Mínimo</label>
                    <input type="number" id="preco-min" name="min" min="0" step="100" inputmode="numeric" placeholder="0">
                  </div>
                  <div class="campo-filtro">
                    <label for="preco-max">Máximo</label>
                    <input type="number" id="preco-max" name="max" min="0" step="100" inputmode="numeric" placeholder="Sem limite">
                  </div>
                </div>
                <label class="filtro-check">
                  <input type="checkbox" id="incluir-consulta" name="consulta" checked>
                  <span>Incluir anúncios “Sob consulta”</span>
                </label>
              </fieldset>

              <fieldset>
                <legend>Ordenar por</legend>
                <div class="campo-filtro">
                  <label class="visually-hidden" for="ordem">Ordenar anúncios</label>
                  <select id="ordem" name="ordem">
                    <option value="padrao">Relevância</option>
                    <option value="menor">Menor preço</option>
                    <option value="maior">Maior preço</option>
                  </select>
                </div>
              </fieldset>

              <button type="reset" class="btn btn-outline-preto filtros-limpar" id="limpar">Limpar filtros</button>
            </form>
          </details>

          <div class="catalogo-resultados">
            <p class="resultados-contagem" id="contagem" role="status" aria-live="polite"></p>

            <div class="cards-grid" id="lista-anuncios">
          <?php foreach ($anuncios as $i => $a) { echo cardAnuncio($a, $i); } ?>

            </div>

            <div class="sem-resultados" id="sem-resultados" hidden>
              <h2>Nenhum anúncio encontrado</h2>
              <p>Tente ampliar a faixa de preço ou selecionar outras categorias.</p>
              <button type="button" class="btn btn-primary" id="limpar-vazio">Limpar filtros</button>
            </div>
          </div>

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
  <script src="js/anuncios.js"></script>

</body>
</html>
