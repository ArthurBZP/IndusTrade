/* Industrade — anuncios.js: filtros por categoria e preço, ordenação e contagem */
(function () {
  var lista = document.getElementById('lista-anuncios');
  var cards = Array.prototype.slice.call(lista.querySelectorAll('.card-maquina'));
  var form = document.getElementById('form-filtros');
  var checks = form.querySelectorAll('input[name="categoria"]');
  var minEl = document.getElementById('preco-min');
  var maxEl = document.getElementById('preco-max');
  var consultaEl = document.getElementById('incluir-consulta');
  var ordemEl = document.getElementById('ordem');
  var contagem = document.getElementById('contagem');
  var vazio = document.getElementById('sem-resultados');
  var painel = document.getElementById('filtros');

  // Painel de filtros: sempre aberto no desktop, recolhível no mobile
  var desktop = window.matchMedia('(min-width: 60em)');
  function ajustarPainel() { painel.open = desktop.matches; }
  ajustarPainel();
  if (desktop.addEventListener) desktop.addEventListener('change', ajustarPainel);

  // Pré-seleção por URL: anuncios.php?categoria=energia
  var param = new URLSearchParams(window.location.search).get('categoria');
  if (param) {
    param.split(',').forEach(function (c) {
      var el = form.querySelector('input[name="categoria"][value="' + c + '"]');
      if (el) el.checked = true;
    });
  }

  function numero(el) {
    var v = parseFloat(el.value);
    return isNaN(v) ? null : v;
  }

  function aplicar() {
    var selecionadas = Array.prototype.filter.call(checks, function (c) { return c.checked; })
      .map(function (c) { return c.value; });
    var min = numero(minEl);
    var max = numero(maxEl);
    var filtrandoPreco = min !== null || max !== null;
    var visiveis = [];

    cards.forEach(function (card) {
      var cat = card.getAttribute('data-categoria');
      var precoAttr = card.getAttribute('data-preco');
      var preco = precoAttr === '' ? null : parseFloat(precoAttr);

      var okCat = selecionadas.length === 0 || selecionadas.indexOf(cat) !== -1;
      var okPreco = true;
      if (filtrandoPreco) {
        if (preco === null) {
          okPreco = consultaEl.checked;
        } else {
          okPreco = (min === null || preco >= min) && (max === null || preco <= max);
        }
      } else if (preco === null) {
        okPreco = consultaEl.checked;
      }

      var mostrar = okCat && okPreco;
      card.hidden = !mostrar;
      if (mostrar) visiveis.push(card);
    });

    // Ordenação (anúncios "Sob consulta" ficam sempre no fim)
    var ordem = ordemEl.value;
    var todos = cards.slice().sort(function (a, b) {
      var pa = a.getAttribute('data-preco'), pb = b.getAttribute('data-preco');
      if (ordem !== 'padrao') {
        if (pa === '' && pb !== '') return 1;
        if (pb === '' && pa !== '') return -1;
        if (pa !== '' && pb !== '') {
          var d = parseFloat(pa) - parseFloat(pb);
          if (d !== 0) return ordem === 'menor' ? d : -d;
        }
      }
      return parseInt(a.getAttribute('data-ordem'), 10) - parseInt(b.getAttribute('data-ordem'), 10);
    });
    todos.forEach(function (card) { lista.appendChild(card); });

    var n = visiveis.length;
    contagem.textContent = n === 0 ? 'Nenhum anúncio encontrado'
      : n === 1 ? '1 anúncio encontrado' : n + ' anúncios encontrados';
    vazio.hidden = n !== 0;
    lista.hidden = n === 0;
  }

  form.addEventListener('input', aplicar);
  form.addEventListener('change', aplicar);
  form.addEventListener('submit', function (e) { e.preventDefault(); });
  // "reset" restaura os valores padrão; reaplica depois que o navegador limpa os campos
  form.addEventListener('reset', function () { setTimeout(aplicar, 0); });
  document.getElementById('limpar-vazio').addEventListener('click', function () {
    form.reset();
  });

  aplicar();
})();
