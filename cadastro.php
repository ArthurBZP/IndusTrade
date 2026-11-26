<?php
require_once __DIR__ . "/sessao.php";
// Valores digitados antes de um erro (para não precisar preencher tudo de novo)
$dados = $_SESSION["cadastro_dados"] ?? [];
unset($_SESSION["cadastro_dados"]);
function v($campo) {
    global $dados;
    return htmlspecialchars($dados[$campo] ?? "", ENT_QUOTES, "UTF-8");
}
$erros = [
    "campos_obrigatorios" => "Preencha todos os campos obrigatórios.",
    "cnpj_invalido"       => "CNPJ inválido: informe os 14 dígitos.",
    "senha_curta"         => "A senha precisa ter pelo menos 8 caracteres.",
    "email_invalido"      => "E-mail inválido.",
    "telefone_invalido"   => "Telefone inválido.",
    "cep_invalido"        => "CEP inválido: informe os 8 dígitos.",
    "uf_invalida"         => "Selecione uma UF válida.",
    "cnpj_existente"      => "Já existe uma conta com este CNPJ.",
    "email_existente"     => "Este e-mail já está em uso por outra empresa.",
    "duplicado"           => "CNPJ ou e-mail já cadastrado.",
    "server_error"        => "Erro no servidor. Tente novamente.",
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar conta | Industrade</title>
  <meta name="description" content="Cadastre sua empresa na Industrade, o marketplace B2B de maquinário industrial usado.">
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
      <h1 class="pagina-titulo">Criar conta</h1>
      <p class="pagina-sub">Cadastre sua empresa para comprar, vender e trocar maquinário na Industrade.</p>

      <form class="card-form card-medio" action="cadastro_process.php" method="POST">
        <?php if (isset($_GET['error'])): ?>
          <p role="alert" style="color:#c0392b;margin-bottom:var(--space-sm)">
            <?php echo $erros[$_GET['error']] ?? $erros['server_error']; ?>
          </p>
        <?php endif; ?>
        <fieldset>
          <legend>Dados de acesso</legend>
          <div class="grade-campos">
          <div class="campo ">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" required data-mask="cnpj" placeholder="00.000.000/0000-00" inputmode="numeric" autocomplete="off" value="<?php echo v('cnpj'); ?>">
          </div>
          <div class="campo ">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
          </div>
          </div>
        </fieldset>

        <fieldset style="margin-top:var(--space-md)">
          <legend>Dados da empresa</legend>
          <div class="grade-campos">
          <div class="campo ">
            <label for="razao">Razão Social</label>
            <input type="text" id="razao" name="razao" required value="<?php echo v('razao'); ?>">
          </div>
          <div class="campo ">
            <label for="fantasia">Nome Fantasia</label>
            <input type="text" id="fantasia" name="fantasia" required value="<?php echo v('fantasia'); ?>">
          </div>
          <div class="campo ">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo v('email'); ?>">
          </div>
          <div class="campo ">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" required data-mask="tel" placeholder="(00) 00000-0000" value="<?php echo v('telefone'); ?>">
          </div>
          <div class="campo ">
            <label for="responsavel">Nome do responsável <span class="opcional">(opcional)</span></label>
            <input type="text" id="responsavel" name="responsavel" maxlength="100" autocomplete="name" value="<?php echo v('responsavel'); ?>">
          </div>
          </div>
        </fieldset>

        <fieldset style="margin-top:var(--space-md)">
          <legend>Endereço</legend>
          <div class="grade-campos">
          <div class="campo ">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" required data-mask="cep" placeholder="00000-000" inputmode="numeric" value="<?php echo v('cep'); ?>">
          </div>
          <div class="campo ">
            <label for="logradouro">Logradouro</label>
            <input type="text" id="logradouro" name="logradouro" required value="<?php echo v('logradouro'); ?>">
          </div>
          <div class="campo ">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" required value="<?php echo v('numero'); ?>">
          </div>
          <div class="campo ">
            <label for="complemento">Complemento <span class="opcional">(opcional)</span></label>
            <input type="text" id="complemento" name="complemento" value="<?php echo v('complemento'); ?>">
          </div>
          <div class="campo ">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" required value="<?php echo v('bairro'); ?>">
          </div>
          <div class="campo ">
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" required value="<?php echo v('cidade'); ?>">
          </div>
          <div class="campo ">
            <label for="uf">UF</label>
            <select id="uf" name="uf" required><option value="">Selecione</option><option<?php if (v('uf') === "AC") echo " selected"; ?>>AC</option><option<?php if (v('uf') === "AL") echo " selected"; ?>>AL</option><option<?php if (v('uf') === "AP") echo " selected"; ?>>AP</option><option<?php if (v('uf') === "AM") echo " selected"; ?>>AM</option><option<?php if (v('uf') === "BA") echo " selected"; ?>>BA</option><option<?php if (v('uf') === "CE") echo " selected"; ?>>CE</option><option<?php if (v('uf') === "DF") echo " selected"; ?>>DF</option><option<?php if (v('uf') === "ES") echo " selected"; ?>>ES</option><option<?php if (v('uf') === "GO") echo " selected"; ?>>GO</option><option<?php if (v('uf') === "MA") echo " selected"; ?>>MA</option><option<?php if (v('uf') === "MT") echo " selected"; ?>>MT</option><option<?php if (v('uf') === "MS") echo " selected"; ?>>MS</option><option<?php if (v('uf') === "MG") echo " selected"; ?>>MG</option><option<?php if (v('uf') === "PA") echo " selected"; ?>>PA</option><option<?php if (v('uf') === "PB") echo " selected"; ?>>PB</option><option<?php if (v('uf') === "PR") echo " selected"; ?>>PR</option><option<?php if (v('uf') === "PE") echo " selected"; ?>>PE</option><option<?php if (v('uf') === "PI") echo " selected"; ?>>PI</option><option<?php if (v('uf') === "RJ") echo " selected"; ?>>RJ</option><option<?php if (v('uf') === "RN") echo " selected"; ?>>RN</option><option<?php if (v('uf') === "RS") echo " selected"; ?>>RS</option><option<?php if (v('uf') === "RO") echo " selected"; ?>>RO</option><option<?php if (v('uf') === "RR") echo " selected"; ?>>RR</option><option<?php if (v('uf') === "SC") echo " selected"; ?>>SC</option><option<?php if (v('uf') === "SP") echo " selected"; ?>>SP</option><option<?php if (v('uf') === "SE") echo " selected"; ?>>SE</option><option<?php if (v('uf') === "TO") echo " selected"; ?>>TO</option></select>
          </div>
          </div>
        </fieldset>

        <div class="acoes-form">
          <button type="submit" class="btn btn-primary btn-lg">Criar conta</button>
        </div>
        <p class="texto-apoio">Já tem conta? <a href="login.php">Entrar</a></p>
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

</body>
</html>
