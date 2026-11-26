<?php
// Botões do canto direito do menu: muda conforme a pessoa estar logada ou não.
// O botão de tema (claro/escuro) aparece nos dois casos; quem troca o tema é o js/tema.js.
require_once __DIR__ . "/sessao.php";
$logadoMenu = isset($_SESSION["id"]);
$nomeMenu   = htmlspecialchars($_SESSION["nome"] ?? "Meu perfil", ENT_QUOTES, "UTF-8");
?>
        <div class="nav-cta">
          <button type="button" class="tema-toggle" data-tema-toggle aria-label="Mudar para o tema escuro" title="Mudar para o tema escuro">
            <svg class="icone-lua" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
              <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"></path>
            </svg>
            <svg class="icone-sol" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
              <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"></circle>
              <path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>
            <span class="tema-toggle-texto">Tema escuro</span>
          </button>
<?php if ($logadoMenu): ?>
          <a class="btn btn-ghost" href="perfil.php"><?php echo $nomeMenu; ?></a>
          <a class="btn btn-primary" href="logout.php">Sair</a>
<?php else: ?>
          <a class="btn btn-ghost" href="login.php">Entrar</a>
          <a class="btn btn-primary" href="cadastro.php">Criar conta</a>
<?php endif; ?>
        </div>
