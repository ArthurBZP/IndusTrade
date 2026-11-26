<?php
$inst = [
    "chave" => "frete", "titulo" => "Políticas de frete e logística", "selo" => "Entrega",
    "descricao" => "Como funciona o frete na Industrade: transporte combinado entre comprador e vendedor e cuidados antes de despachar o equipamento.",
    "intro" => "Maquinário industrial costuma ser pesado e exige cuidados no transporte. Por isso, os anúncios indicam “Frete a combinar”: comprador e vendedor definem juntos como o equipamento será entregue.",
];
require __DIR__ . "/institucional_topo.php";
?>
      <section class="inst-secao" style="margin-top:0" aria-labelledby="frete-quem">
        <h2 id="frete-quem">O que significa “Frete a combinar”</h2>
        <p>A Industrade conecta as empresas, mas não executa nem garante o transporte nesta versão. O valor, o prazo e a transportadora são acertados entre as partes, usando os dados de contato do vendedor.</p>
      </section>

      <div class="inst-grid">
        <div class="inst-card"><h3>Retirada no local</h3><p>O comprador vai até a empresa vendedora. É uma boa chance de ver o equipamento funcionando antes de levar.</p></div>
        <div class="inst-card"><h3>Transportadora</h3><p>Uma das partes contrata o frete. Combine quem paga, quem contrata e quem acompanha a entrega.</p></div>
      </div>

      <section class="inst-secao" aria-labelledby="frete-combinar">
        <h2 id="frete-combinar">O que combinar antes de despachar</h2>
        <ul class="inst-lista">
          <li>Quem paga o frete e em que momento.</li>
          <li>Se o equipamento precisa ser desmontado, embalado ou fixado para viajar, e quem faz isso.</li>
          <li>Seguro da carga durante o transporte.</li>
          <li>Prazo e janela de entrega ou de retirada.</li>
          <li>Emissão de nota fiscal e documentos do equipamento.</li>
          <li>Conferência na chegada: fotos e vistoria na presença do transportador.</li>
        </ul>
      </section>

      <p class="inst-atualizado">Dica: guarde por escrito tudo o que for combinado (e-mail ou mensagem). Isso facilita resolver qualquer divergência.</p>
<?php require __DIR__ . "/institucional_base.php"; ?>
