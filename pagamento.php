<?php
include("protect.php");
require_once "conexao.php";
require_once "anuncio_lib.php";

// A compra é sempre de um anúncio: pagamento.php?id=123
$id = (int)($_GET["id"] ?? 0);
try {
    $stmt = $pdo->prepare(
        "SELECT a.id_anuncio, a.nome, a.valor, a.cnpj_fk, e.nome_fantasia, e.nome_responsavel
           FROM ANUNCIO a JOIN EMPRESA e ON e.cnpj = a.cnpj_fk
          WHERE a.id_anuncio = ? AND a.condicao = 'ATIVO'"
    );
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar o anúncio.");
}
// Anúncio inexistente, sem preço (sob consulta) ou da própria empresa: não dá para comprar
if (!$item || (float)$item["valor"] <= 0 || (string)$item["cnpj_fk"] === (string)$_SESSION["id"]) {
    header("Location: " . ($item ? "anuncio.php?id=" . $id : "anuncios.php"));
    exit;
}
$vendedorNome = trim((string)$item["nome_responsavel"]) !== "" ? $item["nome_responsavel"] : $item["nome_fantasia"];
$valorItem    = (float)$item["valor"];
$moeda        = function ($v) { return "R$ " . number_format($v, 2, ",", "."); };
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagamento | Industrade</title>
  <meta name="description" content="Escolha PIX, cartão ou boleto para finalizar sua compra na Industrade.">
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
      <h1 class="pagina-titulo">Pagamento</h1>
      <p class="pagina-sub">Escolha a forma de pagamento para finalizar sua compra.</p>

      <div class="card-form card-medio">
        <h2>Resumo da compra</h2>
        <div class="resumo-compra">
          <div>
            <p><strong style="font-size:1.0625rem"><?php echo e($item["nome"]); ?></strong></p>
            <p class="card-meta">Vendedor: <?php echo e($vendedorNome); ?> · <?php echo e($item["nome_fantasia"]); ?></p>
          </div>
          <strong><?php echo e(precoFmt($valorItem)); ?></strong>
        </div>
      </div>

      <!-- Ao confirmar, o pedido é gravado no banco por compra_finalizar.php (ver script no fim da página).
           Só o anúncio é enviado ao servidor: os dados de cartão/PIX/boleto não saem do navegador. -->
      <form class="card-form card-medio" id="form-pagamento" method="POST" action="compra_finalizar.php" novalidate>
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id_anuncio" value="<?php echo (int)$id; ?>">
        <p id="pagamento-erro" role="alert" style="color:#c0392b;margin-bottom:var(--space-sm)" hidden></p>
        <fieldset>
          <legend>Forma de pagamento</legend>
          <div class="opcoes">
            <label class="opcao"><input type="radio" name="pagamento" value="pix" data-alterna="pagamento" required><span>PIX</span></label>
            <label class="opcao"><input type="radio" name="pagamento" value="cartao" data-alterna="pagamento" required><span>Cartão</span></label>
            <label class="opcao"><input type="radio" name="pagamento" value="boleto" data-alterna="pagamento" required><span>Boleto</span></label>
          </div>
        <div class="grade-campos" data-painel-de="pagamento" data-painel="pix" hidden style="margin-top:var(--space-sm)">
          <div class="pix-caixa col-total">
            <div class="pix-qr" role="img" aria-label="QR Code de exemplo"></div>
            <p>O QR Code e o código “copia e cola” serão gerados ao confirmar o pagamento.</p>
          </div>
          <div class="campo ">
            <label for="pix_nome">Nome ou Razão Social do pagador</label>
            <input type="text" id="pix_nome" name="pix_nome" required >
          </div>
          <div class="campo ">
            <label for="pix_doc">CNPJ do pagador</label>
            <input type="text" id="pix_doc" name="pix_doc" required data-mask="cnpj" inputmode="numeric">
          </div>
          <div class="campo col-total">
            <label for="pix_email">E-mail para envio do comprovante</label>
            <input type="email" id="pix_email" name="pix_email" required >
          </div>
        </div>
        <div class="grade-campos" data-painel-de="pagamento" data-painel="cartao" hidden style="margin-top:var(--space-sm)">
          <div class="campo col-total">
            <label for="cartao_num">Número do cartão</label>
            <input type="text" id="cartao_num" name="cartao_num" required data-mask="cartao" placeholder="0000 0000 0000 0000" inputmode="numeric" autocomplete="cc-number">
          </div>
          <div class="campo col-total">
            <label for="cartao_nome">Nome impresso no cartão</label>
            <input type="text" id="cartao_nome" name="cartao_nome" required autocomplete="cc-name">
          </div>
          <div class="campo ">
            <label for="cartao_val">Validade</label>
            <input type="text" id="cartao_val" name="cartao_val" required data-mask="validade" placeholder="MM/AA" autocomplete="cc-exp">
          </div>
          <div class="campo ">
            <label for="cartao_cvv">CVV</label>
            <input type="text" id="cartao_cvv" name="cartao_cvv" required placeholder="000" inputmode="numeric" maxlength="4" autocomplete="cc-csc">
          </div>
          <div class="campo ">
            <label for="cartao_doc">CNPJ/CPF do titular</label>
            <input type="text" id="cartao_doc" name="cartao_doc" required inputmode="numeric">
          </div>
          <div class="campo ">
            <label for="cartao_parc">Parcelas</label>
            <select id="cartao_parc" name="cartao_parc" required><option value="">Selecione</option><?php foreach ([1, 2, 3, 6, 12] as $n): ?><option><?php echo $n; ?>x de <?php echo e($moeda($valorItem / $n)); ?> (sem juros)</option><?php endforeach; ?></select>
          </div>
        </div>
        <div class="grade-campos" data-painel-de="pagamento" data-painel="boleto" hidden style="margin-top:var(--space-sm)">
          <div class="campo col-total">
            <label for="bol_razao">Razão Social do pagador</label>
            <input type="text" id="bol_razao" name="bol_razao" required >
          </div>
          <div class="campo ">
            <label for="bol_doc">CNPJ</label>
            <input type="text" id="bol_doc" name="bol_doc" required data-mask="cnpj" inputmode="numeric">
          </div>
          <div class="campo ">
            <label for="bol_email">E-mail para envio do boleto</label>
            <input type="email" id="bol_email" name="bol_email" required >
          </div>
          <div class="campo ">
            <label for="bol_cep">CEP</label>
            <input type="text" id="bol_cep" name="bol_cep" required data-mask="cep" inputmode="numeric">
          </div>
          <div class="campo ">
            <label for="bol_log">Logradouro</label>
            <input type="text" id="bol_log" name="bol_log" required >
          </div>
          <div class="campo ">
            <label for="bol_num">Número</label>
            <input type="text" id="bol_num" name="bol_num" required >
          </div>
          <div class="campo ">
            <label for="bol_bairro">Bairro</label>
            <input type="text" id="bol_bairro" name="bol_bairro" required >
          </div>
          <div class="campo ">
            <label for="bol_cidade">Cidade</label>
            <input type="text" id="bol_cidade" name="bol_cidade" required >
          </div>
          <div class="campo ">
            <label for="bol_uf">UF</label>
            <select id="bol_uf" name="bol_uf" required><option value="">Selecione</option><option>AC</option><option>AL</option><option>AP</option><option>AM</option><option>BA</option><option>CE</option><option>DF</option><option>ES</option><option>GO</option><option>MA</option><option>MT</option><option>MS</option><option>MG</option><option>PA</option><option>PB</option><option>PR</option><option>PE</option><option>PI</option><option>RJ</option><option>RN</option><option>RS</option><option>RO</option><option>RR</option><option>SC</option><option>SP</option><option>SE</option><option>TO</option></select>
          </div>
        </div>
        </fieldset>

        <div class="acoes-form">
          <a class="btn btn-ghost" href="anuncio.php?id=<?php echo (int)$id; ?>" style="border-color:var(--cor-preto)">Voltar ao anúncio</a>
          <button type="submit" class="btn btn-primary btn-lg" id="btn-confirmar">Confirmar pagamento</button>
        </div>
      </form>

      <!-- Pop-up de compra concluída -->
      <dialog class="modal" id="modal-compra" aria-labelledby="modal-compra-titulo">
        <span class="modal-icone" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="28" height="28" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </span>
        <h2 id="modal-compra-titulo">Compra realizada com sucesso!</h2>
        <p>Iremos encaminhar seu pedido ao vendedor.</p>
        <div class="modal-acoes">
          <button type="button" class="btn btn-primary" id="modal-compra-ok" autofocus>Avaliar o vendedor</button>
        </div>
      </dialog>
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
    // Confirmar pagamento: valida o formulário, grava a compra no banco e mostra o pop-up.
    // Ao fechar o pop-up o comprador vai para o perfil do vendedor, para avaliá-lo.
    (function () {
      var form   = document.getElementById('form-pagamento');
      var botao  = document.getElementById('btn-confirmar');
      var erro   = document.getElementById('pagamento-erro');
      var modal  = document.getElementById('modal-compra');
      var destino = 'pedidos.php';

      function mostrarErro(msg) {
        erro.textContent = msg;
        erro.hidden = false;
        botao.disabled = false;
        botao.textContent = 'Confirmar pagamento';
        erro.scrollIntoView({ block: 'center' });
      }
      function irParaVendedor() { window.location.href = destino; }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.reportValidity()) return;
        erro.hidden = true;
        botao.disabled = true;
        botao.textContent = 'Processando…';

        // Envia só o necessário para registrar o pedido (nunca os dados de pagamento)
        var dados = new URLSearchParams();
        dados.set('csrf', form.elements['csrf'].value);
        dados.set('id_anuncio', form.elements['id_anuncio'].value);

        fetch(form.action, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
          body: dados
        })
          .then(function (r) { return r.json(); })
          .then(function (resp) {
            if (!resp.ok) { mostrarErro(resp.erro || 'Não foi possível concluir a compra.'); return; }
            destino = resp.destino;
            botao.textContent = 'Compra realizada';
            if (modal.showModal) { modal.showModal(); } else { irParaVendedor(); }
          })
          .catch(function () { mostrarErro('Não foi possível concluir a compra. Verifique sua conexão e tente novamente.'); });
      });

      document.getElementById('modal-compra-ok').addEventListener('click', irParaVendedor);
      modal.addEventListener('close', irParaVendedor);   // Esc também segue para a avaliação
    })();
  </script>

</body>
</html>
