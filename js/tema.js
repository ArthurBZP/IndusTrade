/* Industrade — tema.js: tema claro/escuro.
   Carregado no <head> de todas as páginas (antes do conteúdo), para a página
   já abrir no tema escolhido, sem "piscar" no tema claro.

   A escolha fica salva no navegador (localStorage), então vale para todas as
   páginas do site e continua valendo depois de fechar o navegador. */
(function () {
  'use strict';

  var CHAVE = 'industrade.tema';
  var raiz = document.documentElement;

  function temaSalvo() {
    try { return localStorage.getItem(CHAVE) === 'dark' ? 'dark' : 'light'; } catch (e) { return 'light'; }
  }

  function aplicar(tema) {
    raiz.setAttribute('data-theme', tema);
    // Atualiza o texto/descrição de todos os botões de tema da página
    document.querySelectorAll('[data-tema-toggle]').forEach(function (btn) {
      var destino = tema === 'dark' ? 'Tema claro' : 'Tema escuro';
      btn.setAttribute('aria-label', 'Mudar para o ' + destino.toLowerCase());
      btn.setAttribute('title', 'Mudar para o ' + destino.toLowerCase());
      var texto = btn.querySelector('.tema-toggle-texto');
      if (texto) texto.textContent = destino;
    });
  }

  aplicar(temaSalvo());   // imediatamente, antes de desenhar a página

  document.addEventListener('DOMContentLoaded', function () {
    aplicar(temaSalvo()); // agora os botões já existem: acerta os textos

    document.querySelectorAll('[data-tema-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var novo = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(CHAVE, novo); } catch (e) { /* sem storage: vale só nesta página */ }
        aplicar(novo);
      });
    });
  });

  // Trocou o tema em outra aba do navegador? Acompanha aqui também.
  window.addEventListener('storage', function (e) {
    if (e.key === CHAVE || e.key === null) aplicar(temaSalvo());
  });
  // ---- Complementos do site (rodapé, pop-up de contato, aba de compras) ----
  // tema.js é o único script carregado no <head> de TODAS as páginas; por isso ele
  // também carrega o js/extras.js e o css/extras.css, sem precisar editar nenhuma página.
  (function () {
    var atual = document.currentScript && document.currentScript.src;
    if (!atual) return;
    var head = document.head || document.documentElement;
    if (!document.querySelector('link[href$="css/extras.css"]')) {
      var css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = new URL('../css/extras.css', atual).href;
      head.appendChild(css);
    }
    if (!document.querySelector('script[src$="js/extras.js"]')) {
      var js = document.createElement('script');
      js.src = new URL('extras.js', atual).href;
      js.defer = true;
      head.appendChild(js);
    }
  })();
})();
