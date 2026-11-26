<?php
$inst = [
    "chave" => "ajuda", "titulo" => "Central de ajuda", "selo" => "Suporte",
    "descricao" => "Respostas para as dúvidas mais comuns sobre cadastro, anúncios, compras, avaliações e denúncias na Industrade.",
    "intro" => "Aqui você encontra o passo a passo das principais tarefas do site. Clique em uma pergunta para ver a resposta.",
];
require __DIR__ . "/institucional_topo.php";
?>
      <section class="inst-faq" aria-label="Perguntas frequentes">
        <details open>
          <summary>Como crio a conta da minha empresa?</summary>
          <div><p>Clique em <a href="cadastro.php">Criar conta</a>, informe os dados da empresa (CNPJ, razão social, nome fantasia, e-mail, telefone), o endereço e defina uma senha. Depois é só <a href="login.php">entrar</a> com o CNPJ e a senha.</p></div>
        </details>
        <details>
          <summary>Como publico um anúncio?</summary>
          <div><p>Com a conta ativa, abra o seu perfil e clique em <strong>Anunciar Produto</strong>. Escolha se é maquinário ou recurso, preencha as especificações, adicione fotos e defina o preço: <strong>fixo</strong> ou <strong>negociável</strong>.</p><p>Para alterar ou excluir, use os botões <strong>Editar</strong> e <strong>Excluir</strong> na aba <em>Produtos Anunciados</em> do perfil.</p></div>
        </details>
        <details>
          <summary>Como compro um produto?</summary>
          <div><p>Abra o anúncio e clique em <strong>Comprar</strong>, escolha a forma de pagamento (PIX, cartão ou boleto) e confirme. A compra fica registrada em <a href="pedidos.php">Minhas Compras</a> e na aba <strong>Produtos Comprados</strong> do seu perfil, e o anúncio passa a aparecer como vendido.</p><p>Anúncios com “valor sob consulta” não têm botão de compra: o preço é combinado direto com o vendedor.</p></div>
        </details>
        <details>
          <summary>O pagamento do site é real?</summary>
          <div><p>Nesta versão o pagamento é uma <strong>simulação</strong>: ele registra o pedido, mas nenhum valor é cobrado ou transferido, e os dados de cartão, PIX ou boleto digitados não são enviados nem guardados. Combine a forma real de pagamento diretamente com o vendedor.</p></div>
        </details>
        <details>
          <summary>Como falo com o vendedor?</summary>
          <div><p>No anúncio, clique em <strong>Conversar com o Vendedor</strong> (é preciso estar logado). Abre um pop-up com o e-mail, o telefone e as avaliações do vendedor, além de atalhos para o perfil completo.</p></div>
        </details>
        <details>
          <summary>Como avalio um vendedor?</summary>
          <div><p>Depois de comprar, use <strong>Avaliar vendedor</strong> em Minhas Compras (ou na aba Produtos Comprados do perfil) e escolha de 1 a 5 estrelas. Só quem comprou pode avaliar, e cada compra recebe uma avaliação.</p></div>
        </details>
        <details>
          <summary>Não aparece o botão “Comprar”. Por quê?</summary>
          <div><p>O botão não aparece quando o anúncio é da sua própria empresa, quando o valor é “sob consulta” ou quando o produto já foi vendido ou pausado.</p></div>
        </details>
        <details>
          <summary>Como mudo para o tema escuro?</summary>
          <div><p>Use o botão de lua/sol no cabeçalho. A escolha fica salva no seu navegador e vale em todas as páginas.</p></div>
        </details>
        <details>
          <summary>Como denuncio um anúncio com problema ou golpe?</summary>
          <div><p>Escreva para <a href="mailto:industrade@gmail.com?subject=Den%C3%BAncia%20de%20an%C3%BAncio%20%E2%80%94%20Industrade">industrade@gmail.com</a> informando o link do anúncio e o que aconteceu. Quanto mais detalhes (conversas, fotos, comprovantes), mais rápido conseguimos resolver.</p></div>
        </details>
      </section>
<?php require __DIR__ . "/institucional_base.php"; ?>
