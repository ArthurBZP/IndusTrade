/* Industrade — extras.js: complementos que rodam em cima das páginas existentes.
   Carregado pelo js/tema.js (que já está no <head> de todas as páginas).

   1) Rodapé ....... aviso de denúncia (industrade@gmail.com) + links das páginas institucionais
   2) Anúncio ...... pop-up "Conversar com o Vendedor" com contato completo e avaliações
   3) Perfil ....... aba "Produtos Comprados" (dados reais do banco, via api_compras.php)

   Tudo é montado com textContent / createElement (nada de innerHTML com dados do banco). */
(function () {
  'use strict';

  var EMAIL_DENUNCIA = 'industrade@gmail.com';

  function el(tag, classe, texto) {
    var e = document.createElement(tag);
    if (classe) e.className = classe;
    if (texto !== undefined && texto !== null) e.textContent = texto;
    return e;
  }
  function link(href, classe, texto) {
    var a = el('a', classe, texto);
    a.setAttribute('href', href);
    return a;
  }
  function pronto(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }
  function buscarJson(url) {
    return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.json().then(function (j) { j._status = r.status; return j; });
      });
  }
  function dataBr(texto) {                     // "2026-11-09" ou "2026-11-09 14:30:00" -> "09/11/2026"
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(texto || '');
    return m ? m[3] + '/' + m[2] + '/' + m[1] : '';
  }
  function moeda(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function nota1(n) { return Number(n).toFixed(1).replace('.', ','); }
  function engrenagens(nota) {                 // mesmas imagens usadas pelo site nas avaliações
    var wrap = el('span', 'engrenagens');
    wrap.setAttribute('role', 'img');
    wrap.setAttribute('aria-label', 'Avaliação: ' + nota1(nota) + ' de 5 engrenagens');
    for (var g = 1; g <= 5; g++) {
      var arq = nota >= g ? 'Engrenagem no logo.png' : (nota >= g - 0.5 ? 'Engrenagem no logo metade.png' : 'Engrenagem no logo vazia.png');
      var item = el('span', 'engrenagem-item');
      var img = el('img');
      img.src = 'img/' + arq; img.alt = ''; img.width = 18; img.height = 18;
      item.appendChild(img);
      wrap.appendChild(item);
    }
    return wrap;
  }

  /* ============================================================
     1) RODAPÉ
     ============================================================ */
  function rodape() {
    var footer = document.querySelector('.site-footer');
    if (!footer) return;

    // Os links do rodapé apontavam para endereços que não existiam (/sobre, /ajuda…).
    var paginas = {
      '/sobre': 'sobre.php', '/ajuda': 'ajuda.php', '/frete': 'frete.php',
      '/termos': 'termos.php', '/privacidade': 'privacidade.php', '/regras-troca': 'regras-troca.php'
    };
    footer.querySelectorAll('a[href]').forEach(function (a) {
      var destino = paginas[a.getAttribute('href')];
      if (destino) a.setAttribute('href', destino);
    });

    if (footer.querySelector('.footer-denuncia')) return;
    var assunto = 'Denúncia de anúncio — Industrade';
    var corpo = 'Página do anúncio: ' + window.location.href +
                '\n\nDescreva o problema (produto com defeito, informação falsa, suspeita de golpe…):\n';
    var caixa = el('div', 'footer-denuncia');
    caixa.setAttribute('role', 'region');
    caixa.setAttribute('aria-label', 'Denunciar anúncio');
    caixa.appendChild(el('h3', '', 'Denunciar anúncio'));
    var p = el('p');
    p.appendChild(document.createTextNode('Encontrou um produto com problema ou suspeita de golpe? Entre em contato pelo e-mail '));
    p.appendChild(link('mailto:' + EMAIL_DENUNCIA + '?subject=' + encodeURIComponent(assunto) + '&body=' + encodeURIComponent(corpo), '', EMAIL_DENUNCIA));
    p.appendChild(document.createTextNode(' que nós resolvemos.'));
    caixa.appendChild(p);

    var copy = footer.querySelector('.copyright');
    if (copy) footer.insertBefore(caixa, copy); else footer.appendChild(caixa);
  }

  /* ============================================================
     2) ANÚNCIO — pop-up "Conversar com o Vendedor"
     ============================================================ */
  function popupVendedor() {
    var dlg = document.getElementById('modal-telefone');
    var linkPerfil = document.querySelector('.vendedor-card a[href*="vendedor.php?cnpj="]');
    if (!dlg || !linkPerfil) return;                         // sem pop-up = anúncio da própria empresa

    var m = /cnpj=(\d{14})/.exec(linkPerfil.getAttribute('href'));
    if (!m) return;
    var cnpj = m[1];
    var tituloAnuncio = ((document.getElementById('anuncio-titulo') || {}).textContent || '').trim();

    buscarJson('vendedor_contato.php?cnpj=' + cnpj).then(function (d) {
      // 401 = visitante sem login: o pop-up original (convite para entrar/criar conta) continua valendo.
      if (!d.ok) return;
      montarContato(dlg, d, tituloAnuncio);
    }).catch(function () { /* mantém o pop-up original */ });
  }

  function montarContato(dlg, d, tituloAnuncio) {
    var fechar = dlg.querySelector('.modal-fechar');
    while (dlg.firstChild) dlg.removeChild(dlg.firstChild);
    if (fechar) dlg.appendChild(fechar);
    dlg.classList.add('modal-vendedor');
    dlg.setAttribute('aria-labelledby', 'modal-vendedor-titulo');

    var perfil = 'vendedor.php?cnpj=' + d.cnpj;
    var nome = d.responsavel || d.empresa;

    dlg.appendChild(el('h2', '', 'Fale com o vendedor')).id = 'modal-vendedor-titulo';
    dlg.appendChild(el('p', 'vendedor-intro', 'Antes de fechar negócio, confira o perfil completo do vendedor e as avaliações de quem já comprou.'));

    // Quem é
    var quem = el('div', 'contato-quem');
    var linhaNome = el('p', 'contato-nome', nome);
    if (d.verificada) linhaNome.appendChild(el('span', 'contato-selo', 'Verificado'));
    quem.appendChild(linhaNome);
    quem.appendChild(el('p', 'contato-sub', d.empresa + ' · ' + d.local + ' · Membro desde ' + d.desde));
    dlg.appendChild(quem);

    // Reputação
    var rep = el('div', 'contato-rep');
    if (d.total) {
      rep.appendChild(engrenagens(d.media));
      rep.appendChild(el('span', 'contato-rep-texto', nota1(d.media) + ' de 5 · ' + d.total + (d.total === 1 ? ' avaliação' : ' avaliações') +
        (d.vendas ? ' · ' + d.vendas + (d.vendas === 1 ? ' venda' : ' vendas') : '')));
    } else {
      rep.appendChild(el('span', 'contato-rep-texto', 'Ainda sem avaliações' + (d.vendas ? ' · ' + d.vendas + (d.vendas === 1 ? ' venda' : ' vendas') : '')));
    }
    dlg.appendChild(rep);

    if (d.recentes.length) {
      var lista = el('ul', 'contato-avals');
      d.recentes.forEach(function (a) {
        var li = el('li');
        var topo = el('p', 'contato-aval-topo');
        topo.appendChild(el('strong', '', a.avaliador));
        topo.appendChild(document.createTextNode(' · ' + nota1(a.nota) + ' de 5 · ' + dataBr(a.data)));
        li.appendChild(topo);
        if (a.comentario) {
          var txt = a.comentario.length > 140 ? a.comentario.slice(0, 137) + '…' : a.comentario;
          li.appendChild(el('p', 'contato-aval-txt', '“' + txt + '”'));
        }
        lista.appendChild(li);
      });
      dlg.appendChild(lista);
    }

    // Contato
    var dados = el('dl', 'contato-dados');
    var dEmail = el('div'); dEmail.appendChild(el('dt', '', 'E-mail'));
    var ddE = el('dd'); ddE.appendChild(link('mailto:' + d.email + '?subject=' + encodeURIComponent('Anúncio “' + tituloAnuncio + '” na Industrade'), '', d.email));
    dEmail.appendChild(ddE); dados.appendChild(dEmail);
    var dTel = el('div'); dTel.appendChild(el('dt', '', 'Telefone'));
    var ddT = el('dd'); ddT.appendChild(link('tel:+' + d.tel_digitos, '', d.telefone));
    dTel.appendChild(ddT); dados.appendChild(dTel);
    dlg.appendChild(dados);

    if (tituloAnuncio) dlg.appendChild(el('p', 'contato-dica', 'Informe que viu o anúncio “' + tituloAnuncio + '” na Industrade.'));
    dlg.appendChild(el('p', 'contato-alerta', 'Desconfie de pedidos de pagamento fora da plataforma. Em caso de golpe, escreva para ' + EMAIL_DENUNCIA + '.'));

    var acoes = el('div', 'modal-acoes');
    acoes.appendChild(link(perfil, 'btn btn-primary', 'Ver perfil completo'));
    acoes.appendChild(link(perfil + '&aba=avaliacoes', 'btn btn-outline-preto', 'Ver avaliações'));
    var tel = link('tel:+' + d.tel_digitos, 'btn btn-outline-preto', 'Ligar agora');
    acoes.appendChild(tel);
    var fechaBtn = el('button', 'btn btn-ghost', 'Fechar');
    fechaBtn.type = 'button';
    fechaBtn.addEventListener('click', function () { dlg.close(); });
    acoes.appendChild(fechaBtn);
    dlg.appendChild(acoes);
  }

  /* ============================================================
     3) PERFIL — aba "Produtos Comprados"
     ============================================================ */
  var STATUS = { REALIZADO: 'Pedido realizado', ENVIADO: 'Enviado', ENTREGUE: 'Entregue', CANCELADO: 'Cancelado' };

  function abaComprados() {
    var abas = document.querySelector('.abas');
    var painelVendidos = document.getElementById('painel-vendidos');
    if (!abas || !painelVendidos || document.getElementById('aba-comprados')) return;

    var aba = el('button', 'aba', 'Produtos Comprados');
    aba.type = 'button';
    aba.id = 'aba-comprados';
    aba.setAttribute('role', 'tab');
    aba.setAttribute('aria-controls', 'painel-comprados');
    aba.setAttribute('aria-selected', 'false');
    abas.appendChild(aba);

    var painel = el('div', 'painel-comprados');
    painel.id = 'painel-comprados';
    painel.setAttribute('role', 'tabpanel');
    painel.setAttribute('aria-labelledby', 'aba-comprados');
    painel.hidden = true;
    painelVendidos.parentNode.insertBefore(painel, painelVendidos.nextSibling);

    var carregado = false;
    function carregar() {
      if (carregado) return;
      carregado = true;
      painel.appendChild(el('p', 'vazio', 'Carregando suas compras…'));
      buscarJson('api_compras.php').then(function (d) {
        painel.textContent = '';
        if (!d.ok) { painel.appendChild(el('p', 'vazio', 'Não foi possível carregar suas compras agora. Tente novamente.')); carregado = false; return; }
        desenharCompras(painel, d.compras);
      }).catch(function () {
        painel.textContent = '';
        painel.appendChild(el('p', 'vazio', 'Não foi possível carregar suas compras agora. Tente novamente.'));
        carregado = false;
      });
    }

    function selecionar() {
      document.querySelectorAll('.abas .aba').forEach(function (a) {
        var ativa = a === aba;
        a.setAttribute('aria-selected', ativa ? 'true' : 'false');
        var alvo = document.getElementById(a.getAttribute('aria-controls'));
        if (alvo) alvo.hidden = !ativa;
      });
      carregar();
    }
    aba.addEventListener('click', selecionar);

    // O script de abas da página só conhece as abas originais; ao clicar em uma delas, esconde esta.
    document.querySelectorAll('.abas .aba').forEach(function (outra) {
      if (outra === aba) return;
      outra.addEventListener('click', function () {
        aba.setAttribute('aria-selected', 'false');
        painel.hidden = true;
      });
    });

    var qs = new URLSearchParams(window.location.search);
    if (qs.get('aba') === 'comprados' || window.location.hash === '#comprados') selecionar();
  }

  function desenharCompras(painel, compras) {
    if (!compras.length) {
      var vazio = el('p', 'vazio', 'Você ainda não comprou nenhum produto. ');
      vazio.appendChild(link('anuncios.php', 'link-forte', 'Ver anúncios'));
      painel.appendChild(vazio);
      return;
    }
    var ul = el('ul', 'lista-produtos lista-comprados');
    compras.forEach(function (c) {
      var li = el('li', 'item-produto item-comprado');
      var corpo = el('div');
      var h3 = el('h3', '', c.produto);
      h3.appendChild(el('span', 'tag-comprado', STATUS[c.status] || c.status));
      corpo.appendChild(h3);
      corpo.appendChild(el('p', 'card-preco', moeda(c.valor)));

      var dl = el('dl', 'vendido-dados');
      var dVend = el('div'); dVend.appendChild(el('dt', '', 'Vendedor'));
      var ddV = el('dd'); ddV.appendChild(link('vendedor.php?cnpj=' + encodeURIComponent(c.cnpj_vendedor), 'link-forte', c.vendedor));
      dVend.appendChild(ddV); dl.appendChild(dVend);
      [['Data da compra', dataBr(c.data)], ['Pedido', 'Nº ' + c.id_pedido]].forEach(function (par) {
        var d = el('div'); d.appendChild(el('dt', '', par[0])); d.appendChild(el('dd', '', par[1])); dl.appendChild(d);
      });
      corpo.appendChild(dl);
      li.appendChild(corpo);

      if (c.status !== 'CANCELADO') {
        var acoes = el('div', 'item-acoes');
        if (c.nota !== null) {
          var estrelas = el('span', 'estrelas-nota');
          estrelas.setAttribute('role', 'img');
          estrelas.setAttribute('aria-label', 'Sua avaliação: ' + c.nota + ' de 5 estrelas');
          estrelas.appendChild(document.createTextNode('★'.repeat(c.nota)));
          estrelas.appendChild(el('span', 'vazia', '★'.repeat(5 - c.nota)));
          acoes.appendChild(el('span', 'card-meta', 'Sua avaliação:'));
          acoes.appendChild(estrelas);
        } else {
          acoes.appendChild(link('vendedor.php?cnpj=' + encodeURIComponent(c.cnpj_vendedor) + '&avaliar=' + c.id_pedido + '#avaliar', 'btn btn-primary btn-sm', 'Avaliar vendedor'));
        }
        li.appendChild(acoes);
      }
      ul.appendChild(li);
    });
    painel.appendChild(ul);
    var rodape = el('p', 'painel-comprados-rodape');
    rodape.appendChild(link('pedidos.php', 'btn btn-outline-preto btn-sm', 'Ver detalhes em Minhas Compras'));
    painel.appendChild(rodape);
  }

  pronto(function () {
    try { rodape(); } catch (e) { /* o rodapé nunca deve derrubar a página */ }
    try { popupVendedor(); } catch (e) { }
    try { abaComprados(); } catch (e) { }
  });
})();
