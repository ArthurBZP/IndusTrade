/* Industrade — modal.js: abre e fecha os pop-ups (<dialog class="modal">).

   No HTML:
     <button data-abre-modal="id-do-dialog">  -> abre o pop-up
     <button data-fecha-modal>                 -> fecha o pop-up em que está
   Também fecha com Esc (nativo do <dialog>) e clicando fora da caixa. */
(function () {
  'use strict';

  document.querySelectorAll('[data-abre-modal]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var modal = document.getElementById(btn.getAttribute('data-abre-modal'));
      if (!modal || !modal.showModal) return;   // navegador muito antigo: segue o link normal
      e.preventDefault();
      modal.showModal();
    });
  });

  document.querySelectorAll('dialog.modal').forEach(function (modal) {
    modal.querySelectorAll('[data-fecha-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () { modal.close(); });
    });
    // Clique no fundo escuro (fora da caixa) fecha
    modal.addEventListener('click', function (e) {
      if (e.target !== modal) return;
      var r = modal.getBoundingClientRect();
      var dentro = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
      if (!dentro) modal.close();
    });
  });
})();
