<?php
$inst = [
    "chave" => "sobre", "titulo" => "Sobre nós", "selo" => "Institucional",
    "descricao" => "Conheça a Industrade, o marketplace B2B para compra, venda e troca de maquinário industrial usado.",
    "intro" => "A Industrade é um marketplace B2B: um espaço em que empresas compram, vendem e trocam maquinário industrial usado e outros recursos de produção.",
];
require __DIR__ . "/institucional_topo.php";
?>
      <section class="inst-secao" style="margin-top:0" aria-labelledby="como-funciona">
        <h2 id="como-funciona">Como a Industrade funciona</h2>
        <div class="inst-grid inst-grid-3">
          <div class="inst-card"><span class="inst-num" aria-hidden="true">1</span><h3>Cadastre sua empresa</h3><p>O acesso é feito com o CNPJ da empresa. Assim, quem compra e quem vende são sempre empresas identificadas.</p></div>
          <div class="inst-card"><span class="inst-num" aria-hidden="true">2</span><h3>Anuncie ou encontre</h3><p>Publique máquinas e recursos com fotos, especificações e preço (fixo ou negociável), ou procure no catálogo o que sua produção precisa.</p></div>
          <div class="inst-card"><span class="inst-num" aria-hidden="true">3</span><h3>Negocie e compre</h3><p>Converse com o vendedor, confira as avaliações dele e, quando o anúncio tem preço, finalize a compra pelo site.</p></div>
        </div>
      </section>

      <section class="inst-secao" aria-labelledby="guia-rodape">
        <h2 id="guia-rodape">O que significa cada link do rodapé</h2>
        <p>O rodapé reúne as páginas de apoio do site. Veja o que você encontra em cada uma:</p>
        <div class="inst-grid">
          <div class="inst-card"><h3><a href="sobre.php">Sobre nós</a></h3><p>Esta página: apresenta a Industrade e explica como o site funciona.</p></div>
          <div class="inst-card"><h3><a href="ajuda.php">Central de ajuda</a></h3><p>Respostas passo a passo para as dúvidas mais comuns: cadastro, anúncios, compras, avaliações e denúncias.</p></div>
          <div class="inst-card"><h3><a href="frete.php">Políticas de frete e logística</a></h3><p>Explica como o transporte do equipamento é combinado entre comprador e vendedor e o que combinar antes de despachar.</p></div>
          <div class="inst-card"><h3><a href="termos.php">Termos de uso</a></h3><p>As regras para usar a plataforma: responsabilidades de quem anuncia, de quem compra e condutas não permitidas.</p></div>
          <div class="inst-card"><h3><a href="privacidade.php">Política de privacidade</a></h3><p>Quais dados são guardados, o que aparece para outros usuários e como você pode pedir correções.</p></div>
          <div class="inst-card"><h3><a href="regras-troca.php">Regras de troca</a></h3><p>Como funciona a troca de equipamentos entre empresas e o que fazer se o produto não for como anunciado.</p></div>
          <div class="inst-card"><h3>Contato</h3><p>E-mail e telefone da Industrade para dúvidas gerais, além das redes sociais (Instagram e LinkedIn).</p></div>
          <div class="inst-card"><h3>Denunciar anúncio</h3><p>Se um produto tiver problema ou for suspeito de golpe, escreva para <a href="mailto:industrade@gmail.com?subject=Den%C3%BAncia%20de%20an%C3%BAncio%20%E2%80%94%20Industrade">industrade@gmail.com</a>.</p></div>
        </div>
      </section>

      <section class="inst-secao" aria-labelledby="confianca">
        <h2 id="confianca">Confiança entre empresas</h2>
        <p>Cada vendedor tem um perfil público com anúncios, tempo de cadastro e avaliações de quem já comprou dele. Só quem realizou uma compra pode avaliar, e cada compra recebe uma única avaliação, o que mantém a nota baseada em negócios reais.</p>
      </section>
<?php require __DIR__ . "/institucional_base.php"; ?>
