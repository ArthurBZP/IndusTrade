/* Industrade — forms.js: máscaras, alternância de painéis e navegação do protótipo */
(function () {
  // ---- Máscaras ----
  function mascara(seletor, fn) {
    document.querySelectorAll(seletor).forEach(function (el) {
      el.addEventListener('input', function () { el.value = fn(el.value.replace(/\D/g, '')); });
    });
  }
  mascara('[data-mask="cnpj"]', function (v) {
    v = v.slice(0, 14);
    return v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
  });
  mascara('[data-mask="cep"]', function (v) { return v.slice(0, 8).replace(/^(\d{5})(\d)/, '$1-$2'); });
  mascara('[data-mask="tel"]', function (v) {
    v = v.slice(0, 11);
    return v.length > 10 ? v.replace(/^(\d{2})(\d{5})(\d)/, '($1) $2-$3')
                         : v.replace(/^(\d{2})(\d{4})(\d)/, '($1) $2-$3');
  });
  mascara('[data-mask="cartao"]', function (v) { return v.slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 '); });
  mascara('[data-mask="validade"]', function (v) { return v.slice(0, 4).replace(/^(\d{2})(\d)/, '$1/$2'); });

  // ---- Alternância de painéis por radio: data-alterna="grupo" data-mostra="painel" ----
  function atualizarGrupo(nome) {
    var marcado = document.querySelector('input[data-alterna="' + nome + '"]:checked');
    document.querySelectorAll('[data-painel-de="' + nome + '"]').forEach(function (p) {
      var ativo = marcado && p.getAttribute('data-painel') === marcado.value;
      p.hidden = !ativo;
      p.querySelectorAll('input, select, textarea').forEach(function (c) {
        c.disabled = !ativo;
      });
    });
  }
  var grupos = {};
  document.querySelectorAll('input[data-alterna]').forEach(function (r) {
    grupos[r.getAttribute('data-alterna')] = true;
    r.addEventListener('change', function () { atualizarGrupo(r.getAttribute('data-alterna')); });
  });
  Object.keys(grupos).forEach(atualizarGrupo);

  // ---- Envio do protótipo: valida e redireciona para data-destino ----
  document.querySelectorAll('form[data-destino]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!f.reportValidity()) return;
      window.location.href = f.getAttribute('data-destino');
    });
  });
})();
