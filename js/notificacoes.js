/* Industrade — notificacoes.js: sino de notificações do perfil (vendedor).

   As vendas vêm do BANCO DE DADOS (api_vendas.php: PEDIDO + ITEM_PEDIDO + ANUNCIO),
   ou seja, valem para qualquer computador/navegador em que o vendedor entrar.
   Antes isto usava localStorage e um botão "Simular venda" com compradores inventados;
   ambos foram removidos.

   Só o controle de "lida / não lida" fica no navegador (por empresa), o que é
   uma preferência de leitura e não um dado de venda.

   A aba "Produtos Vendidos" já é desenhada pelo servidor (perfil.php); aqui apenas
   acrescentamos, sem recarregar, as vendas que chegarem enquanto a página está aberta. */
(function () {
  'use strict';

  var sino = document.getElementById('sino-notificacoes');
  var listaVendidos = document.getElementById('lista-vendidos');
  if (!sino || !listaVendidos) return;               // só roda no perfil

  var INTERVALO = 30000;                              // consulta o servidor a cada 30 s
  var painel = document.getElementById('notif-painel');
  var badge = document.getElementById('notif-badge');
  var listaNotif = document.getElementById('notif-lista');
  var vazioNotif = document.getElementById('notif-vazio');
  var btnLidas = document.getElementById('notif-marcar-lidas');
  var btnVerVendidos = document.getElementById('notif-ver-vendidos');
  var btnSimular = document.getElementById('notif-simular');
  var live = document.getElementById('notif-live');
  var vendidosVazio = document.getElementById('vendidos-vazio');
  var abaVendidos = document.getElementById('aba-vendidos');

  if (btnSimular) btnSimular.hidden = true;           // sem simulação: só vendas reais

  var vendas = [];          // [{id, produto, comprador, valor, data}] mais recentes primeiro
  var chaveLidas = 'industrade.notif.lidas';
  var idsNaPagina = null;   // vendas já mostradas (desenhadas pelo servidor ou por nós)

  // ---------- Leitura/gravação do "lidas" (tolerante a navegador sem storage) ----------
  function lerLidas() {
    try { return JSON.parse(localStorage.getItem(chaveLidas)) || []; } catch (e) { return []; }
  }
  function gravarLidas(l) {
    try { localStorage.setItem(chaveLidas, JSON.stringify(l)); } catch (e) { /* ignora */ }
  }

  function el(tag, classe, texto) {
    var e = document.createElement(tag);
    if (classe) e.className = classe;
    if (texto !== undefined) e.textContent = texto;
    return e;
  }
  function moeda(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function dataBr(t, comHora) {
    var m = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/.exec(t || '');
    if (!m) return '';
    var d = m[3] + '/' + m[2] + '/' + m[1];
    return comHora && m[4] ? d + ' ' + m[4] + ':' + m[5] : d;
  }

  // ---------- Notificações ----------
  function renderNotificacoes() {
    var lidas = lerLidas();
    var naoLidas = 0;
    listaNotif.textContent = '';

    vendas.forEach(function (v) {
      var lida = lidas.indexOf(v.id) !== -1;
      if (!lida) naoLidas++;
      var li = el('li', 'notif-item' + (lida ? '' : ' nao-lida'));
      li.appendChild(el('span', 'notif-ponto'));
      var texto = el('div', 'notif-texto');
      var p = el('p');
      p.appendChild(el('strong', '', v.comprador));
      p.appendChild(document.createTextNode(' comprou '));
      p.appendChild(el('strong', '', v.produto));
      p.appendChild(document.createTextNode(' por ' + moeda(v.valor) + '.'));
      texto.appendChild(p);
      texto.appendChild(el('span', 'notif-hora', dataBr(v.data, true)));
      li.appendChild(texto);
      listaNotif.appendChild(li);
    });

    vazioNotif.hidden = vendas.length !== 0;
    badge.textContent = naoLidas > 9 ? '9+' : String(naoLidas);
    badge.hidden = naoLidas === 0;
    sino.setAttribute('aria-label', naoLidas
      ? 'Notificações: ' + naoLidas + (naoLidas === 1 ? ' nova' : ' novas')
      : 'Notificações: nenhuma nova');
    btnLidas.hidden = naoLidas === 0;
  }

  // ---------- Aba Produtos Vendidos: só acrescenta o que chegou depois do carregamento ----------
  function acrescentarVendidas(novas) {
    novas.slice().reverse().forEach(function (v) {
      var li = el('li', 'item-produto item-vendido nova');
      var corpo = el('div');
      var h3 = el('h3', '', v.produto + ' ');
      h3.appendChild(el('span', 'tag-vendido', 'Vendido'));
      corpo.appendChild(h3);
      corpo.appendChild(el('p', 'card-preco', moeda(v.valor)));
      var dl = el('dl', 'vendido-dados');
      [['Comprador', v.comprador], ['Data da venda', dataBr(v.data)]].forEach(function (par) {
        var d = el('div');
        d.appendChild(el('dt', '', par[0]));
        d.appendChild(el('dd', '', par[1]));
        dl.appendChild(d);
      });
      corpo.appendChild(dl);
      li.appendChild(corpo);
      listaVendidos.insertBefore(li, listaVendidos.firstChild);
    });
    if (vendidosVazio) vendidosVazio.hidden = listaVendidos.children.length !== 0;
  }

  // ---------- Consulta ao servidor ----------
  function atualizar() {
    fetch('api_vendas.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) return;
        chaveLidas = 'industrade.notif.lidas.' + d.eu;       // "lidas" separado por empresa
        vendas = d.vendas;

        if (idsNaPagina === null) {
          // 1ª consulta: a aba já foi desenhada pelo servidor. Se entrou uma venda entre o
          // carregamento da página e esta consulta, ela ainda não está na lista: acrescenta.
          var jaDesenhadas = listaVendidos.children.length;
          if (vendas.length > jaDesenhadas) acrescentarVendidas(vendas.slice(0, vendas.length - jaDesenhadas));
          idsNaPagina = vendas.map(function (v) { return v.id; });
        } else {
          var novas = vendas.filter(function (v) { return idsNaPagina.indexOf(v.id) === -1; });
          if (novas.length) {
            acrescentarVendidas(novas);
            idsNaPagina = idsNaPagina.concat(novas.map(function (v) { return v.id; }));
            live.textContent = 'Nova venda: ' + novas[0].produto + ' foi comprado por ' + novas[0].comprador + '.';
          }
        }
        renderNotificacoes();
      })
      .catch(function () { /* sem conexão: tenta de novo no próximo ciclo */ });
  }

  // ---------- Painel do sino ----------
  function alternarPainel(abrir) {
    painel.hidden = !abrir;
    sino.setAttribute('aria-expanded', abrir ? 'true' : 'false');
  }
  sino.addEventListener('click', function (e) {
    e.stopPropagation();
    alternarPainel(painel.hidden);
  });
  document.addEventListener('click', function (e) {
    if (!painel.hidden && !painel.contains(e.target) && e.target !== sino) alternarPainel(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !painel.hidden) { alternarPainel(false); sino.focus(); }
  });
  btnLidas.addEventListener('click', function () {
    var lidas = lerLidas();
    vendas.forEach(function (v) { if (lidas.indexOf(v.id) === -1) lidas.push(v.id); });
    gravarLidas(lidas);
    renderNotificacoes();
  });
  btnVerVendidos.addEventListener('click', function () {
    alternarPainel(false);
    abaVendidos.click();            // reaproveita o script de abas já existente
    abaVendidos.focus();
  });

  // "Tempo real": consulta periódica + ao voltar para a aba do navegador
  setInterval(function () { if (!document.hidden) atualizar(); }, INTERVALO);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) atualizar(); });
  window.addEventListener('pageshow', function (e) { if (e.persisted) atualizar(); });

  atualizar();
})();
