<?php
// Topo das páginas institucionais do rodapé (sobre, ajuda, frete, termos, privacidade, regras-troca).
// Arquivo NOVO. Reaproveita o mesmo cabeçalho, CSS e tema das demais páginas.
// Cada página define antes: $inst = ["chave", "titulo", "descricao", "selo", "intro"].
require_once __DIR__ . "/sessao.php";
function inst_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$instPaginas = [
    "sobre"        => ["sobre.php",        "Sobre nós"],
    "ajuda"        => ["ajuda.php",        "Central de ajuda"],
    "frete"        => ["frete.php",        "Frete e logística"],
    "termos"       => ["termos.php",       "Termos de uso"],
    "privacidade"  => ["privacidade.php",  "Privacidade"],
    "regras-troca" => ["regras-troca.php", "Regras de troca"],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo inst_e($inst["titulo"]); ?> | Industrade</title>
  <meta name="description" content="<?php echo inst_e($inst["descricao"]); ?>">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/mobile.css">
  <link rel="stylesheet" href="css/tema.css">
  <script src="js/tema.js"></script>
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/extras.css">
</head>
<body>

  <a class="skip-link" href="#conteudo-principal">Pular para o conteúdo principal</a>

  <header class="site-header">
    <div class="header-inner">

      <a class="logo" href="index.php" aria-label="Industrade — página inicial">
        <img class="logo-mark" src="img/Engrenagem no logo.png" width="36" height="36" alt="" aria-hidden="true">
        <span class="logo-text">Industrade</span>
      </a>

      <input type="checkbox" id="menu-toggle" class="menu-toggle-input">
      <label for="menu-toggle" class="menu-toggle-label" aria-hidden="false">
        <span class="visually-hidden">Abrir menu de navegação</span>
        <span class="menu-icon"></span>
      </label>

      <nav class="primary-nav" aria-label="Navegação principal">
        <?php include __DIR__ . "/nav_cta.php"; ?>
      </nav>

    </div>
  </header>

  <main id="conteudo-principal" class="pagina-interna">
    <div class="section-inner">
      <div class="inst-topo">
        <span class="inst-selo"><?php echo inst_e($inst["selo"]); ?></span>
        <h1 class="pagina-titulo"><?php echo inst_e($inst["titulo"]); ?></h1>
        <p class="inst-intro"><?php echo inst_e($inst["intro"]); ?></p>
      </div>

      <nav aria-label="Páginas institucionais">
        <ul class="inst-nav">
          <?php foreach ($instPaginas as $chave => $p): ?>
          <li><a href="<?php echo inst_e($p[0]); ?>"<?php echo $chave === $inst["chave"] ? ' aria-current="page"' : ""; ?>><?php echo inst_e($p[1]); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
