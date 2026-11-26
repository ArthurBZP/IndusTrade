<?php
$inst = [
    "chave" => "privacidade", "titulo" => "Política de privacidade", "selo" => "Termos legais",
    "descricao" => "Saiba quais dados a Industrade guarda, o que fica visível para outros usuários e como pedir correções.",
    "intro" => "Explicamos aqui, sem juridiquês, quais dados a Industrade guarda, para que servem e o que outras pessoas conseguem ver.",
];
require __DIR__ . "/institucional_topo.php";
?>
      <section class="inst-secao" style="margin-top:0">
        <h2>Dados que guardamos</h2>
        <ul class="inst-lista">
          <li><strong>Cadastro da empresa:</strong> CNPJ, razão social, nome fantasia, responsável, e-mail, telefone e endereço.</li>
          <li><strong>Senha:</strong> guardada de forma criptografada (hash), nunca em texto aberto.</li>
          <li><strong>Atividade no site:</strong> anúncios publicados, fotos, pedidos realizados e avaliações dadas e recebidas.</li>
          <li><strong>Foto de perfil</strong>, se você enviar uma.</li>
        </ul>
      </section>
      <section class="inst-secao">
        <h2>O que outras pessoas veem</h2>
        <ul class="inst-lista">
          <li><strong>Para qualquer visitante:</strong> nome fantasia, cidade/UF, anúncios ativos, tempo de cadastro e avaliações do vendedor.</li>
          <li><strong>Somente para usuários logados:</strong> e-mail e telefone do vendedor, para que possam negociar.</li>
          <li>Sua senha e seus pedidos nunca são exibidos a terceiros; o vendedor só vê as compras feitas nos anúncios dele.</li>
        </ul>
      </section>
      <section class="inst-secao">
        <h2>Dados de pagamento</h2>
        <p>O pagamento do site é demonstrativo: número de cartão, dados de PIX e de boleto digitados na tela de pagamento <strong>não são enviados ao servidor nem guardados</strong>. Apenas o pedido (produto, valor, comprador e data) é registrado.</p>
      </section>
      <section class="inst-secao">
        <h2>Cookies e armazenamento no navegador</h2>
        <ul class="inst-lista">
          <li><strong>Cookie de sessão:</strong> mantém você conectado por até 30 dias, até clicar em “Sair”.</li>
          <li><strong>Tema claro/escuro:</strong> sua escolha fica salva no navegador. Não há cookies de publicidade.</li>
        </ul>
      </section>
      <section class="inst-secao">
        <h2>Seus direitos</h2>
        <p>Você pode atualizar os dados da sua empresa e pedir correção ou exclusão da conta escrevendo para <a href="mailto:industrade@gmail.com?subject=Privacidade%20%E2%80%94%20Industrade">industrade@gmail.com</a>.</p>
      </section>
      <p class="inst-atualizado">Última atualização: novembro de 2026.</p>
<?php require __DIR__ . "/institucional_base.php"; ?>
