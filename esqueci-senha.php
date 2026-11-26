<?php
// ============================================================================
// "Esqueci minha senha" (página demonstrativa).
//
// Recebe o CNPJ e o e-mail da empresa e mostra a confirmação de que o link de
// redefinição foi enviado. NESTA VERSÃO NENHUM E-MAIL É ENVIADO e a senha não é
// alterada: a página existe para a apresentação do fluxo.
//
// A mensagem de confirmação é sempre a mesma, exista ou não uma conta com os
// dados informados, para não revelar quais CNPJs/e-mails estão cadastrados.
//
// Para tornar funcional no futuro: gerar um token com validade (ex.: 30 min),
// gravá-lo em uma tabela (ex.: RECUPERACAO_SENHA), enviar o link por e-mail e
// criar a página que valida o token e grava a nova senha com password_hash().
// ============================================================================
require_once __DIR__ . "/sessao.php";
if (isset($_SESSION["id"])) { header("Location: perfil.php"); exit; }

$erro = "";
$cnpjDigitado  = "";
$emailDigitado = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cnpjDigitado  = trim((string)($_POST["cnpj"] ?? ""));
    $emailDigitado = trim((string)($_POST["email"] ?? ""));
    $cnpj = preg_replace('/\D/', '', $cnpjDigitado);

    if (!csrf_valido()) {
        $erro = "Sessão expirada. Atualize a página e tente novamente.";
    } elseif (strlen($cnpj) !== 14) {
        $erro = "Informe um CNPJ válido, com 14 dígitos.";
    } elseif (!filter_var($emailDigitado, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } else {
        // Demonstração: nada é enviado. Redireciona para evitar reenvio ao atualizar a página.
        header("Location: esqueci-senha.php?enviado=1");
        exit;
    }
}
$enviado = isset($_GET["enviado"]);
function hs($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Esqueci minha senha | Industrade</title>
  <meta name="description" content="Recupere o acesso à conta da sua empresa na Industrade.">
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
<?php if ($enviado): ?>
      <div class="card-form card-estreito" role="status" aria-labelledby="rec-titulo">
        <h1 id="rec-titulo" class="pagina-titulo">Verifique seu e-mail</h1>
        <p class="pagina-sub">Se os dados informados correspondem a uma conta cadastrada, enviaremos um link para criar uma nova senha. O link vale por 30 minutos.</p>
        <p class="texto-apoio" style="margin-block-start:0">Não recebeu? Confira a caixa de spam ou <a href="esqueci-senha.php">tente novamente</a>.</p>
        <div class="acoes-form">
          <a class="btn btn-primary btn-lg" href="login.php" style="width:100%;text-align:center">Voltar para o login</a>
        </div>
      </div>
<?php else: ?>
      <form class="card-form card-estreito" action="esqueci-senha.php" method="POST" aria-labelledby="rec-titulo">
        <h1 id="rec-titulo" class="pagina-titulo">Esqueci minha senha</h1>
        <p class="pagina-sub">Informe o CNPJ e o e-mail cadastrados. Enviaremos um link para você criar uma nova senha.</p>
        <?php if ($erro !== ""): ?>
          <p role="alert" style="color:#c0392b;margin-bottom:var(--space-sm)"><?php echo hs($erro); ?></p>
        <?php endif; ?>
        <input type="hidden" name="csrf" value="<?php echo hs(csrf_token()); ?>">
        <div class="grade-campos" style="grid-template-columns:1fr">
          <div class="campo ">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" required data-mask="cnpj" placeholder="00.000.000/0000-00" inputmode="numeric" value="<?php echo hs($cnpjDigitado); ?>">
          </div>
          <div class="campo ">
            <label for="email">E-mail cadastrado</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="contato@suaempresa.com.br" value="<?php echo hs($emailDigitado); ?>">
          </div>
        </div>
        <div class="acoes-form">
          <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Enviar link de redefinição</button>
        </div>
        <p class="texto-apoio">Lembrou a senha? <a href="login.php">Voltar para o login</a></p>
      </form>
<?php endif; ?>
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
          <li><a href="sobre.php">Sobre nós</a></li>
          <li><a href="ajuda.php">Central de ajuda</a></li>
          <li><a href="frete.php">Políticas de frete e logística</a></li>
        </ul>
      </nav>

      <nav class="footer-coluna" aria-label="Termos legais">
        <h3>Termos legais</h3>
        <ul>
          <li><a href="termos.php">Termos de uso</a></li>
          <li><a href="privacidade.php">Política de privacidade</a></li>
          <li><a href="regras-troca.php">Regras de troca</a></li>
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
