<?php
/** Post: Black Friday e compras internacionais: dólar, IOF e taxa de importação */
return [
    'slug'       => 'black-friday-compras-internacionais-dolar-iof',
    'titulo'     => 'Black Friday e compras internacionais: dólar, IOF e taxa de importação',
    'resumo'     => 'A Black Friday é em 27 de novembro. Veja como calcular o preço real de uma compra em dólar, com câmbio, IOF e impostos.',
    'data'       => '2026-11-24',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>A Black Friday de 2026 será em 27 de novembro. Produtos em dólar parecem baratos, mas o preço final no Brasil depende de várias camadas.</p>
        <h2>Como calcular o preço real</h2>
        <ol>
        <li>Converta o preço pelo <a href="/dolar-comercial/">dólar</a> do dia usando o <a href="/conversor-de-moedas/">conversor</a>.</li>
        <li>Acrescente o IOF do cartão ou da conta (veja <?= lp('como-pagar-menos-iof-viagem-internacional','como pagar menos IOF') ?>).</li>
        <li>Considere o frete internacional.</li>
        <li>Verifique impostos de importação: as regras de tributação de compras internacionais mudaram nos últimos anos, então consulte a Receita Federal e a loja.</li>
        </ol>
        <h2>Dicas</h2>
        <ul>
        <li>Compare com o preço nacional já com tudo incluído.</li>
        <li>Desconfie de ofertas muito abaixo do mercado e confira a reputação da loja.</li>
        <li>Veja se o cartão cobra spread extra na conversão.</li>
        <li>Guarde comprovantes para eventuais trocas e garantia.</li>
        </ul>
        <p>Para entender a variação do câmbio, leia <?= lp('o-que-faz-o-dolar-subir-ou-cair','o que faz o dólar subir ou cair') ?>.</p>
        <p class="note">As regras tributárias podem mudar; confirme as vigentes na data da compra.</p>
        <?php
    },
];
