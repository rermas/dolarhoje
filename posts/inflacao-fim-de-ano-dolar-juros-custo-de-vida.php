<?php
/** Post: Inflação no fim de ano: como dólar, combustíveis e alimentos afetam seu bolso */
return [
    'slug'       => 'inflacao-fim-de-ano-dolar-juros-custo-de-vida',
    'titulo'     => 'Inflação no fim de ano: como dólar, combustíveis e alimentos afetam seu bolso',
    'resumo'     => 'Entenda por que o IPCA tende a pressionar em dezembro, como o câmbio entra na conta e como proteger seu orçamento.',
    'data'       => '2026-11-04',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>O fim de ano costuma trazer demanda mais alta por alimentos, passagens aéreas, viagens e presentes, o que pode pressionar preços. Quando o câmbio e o petróleo também sobem, o efeito se amplia.</p>
        <h2>Como o dólar chega ao preço</h2>
        <ul>
        <li><strong>Importados:</strong> eletrônicos e peças ficam mais caros quando o <a href="/dolar-comercial/">dólar</a> sobe.</li>
        <li><strong>Insumos:</strong> fertilizantes, trigo e combustíveis têm referência em dólar.</li>
        <li><strong>Passagens e hospedagem:</strong> pacotes internacionais e o querosene de aviação.</li>
        </ul>
        <h2>Juros e inflação</h2>
        <p>Se a inflação teima em ficar alta, o Banco Central tende a manter juros elevados por mais tempo, o que encarece crédito e financiamentos. Veja <?= lp('selic-cdi-ipca-o-que-sao','o que são Selic, CDI e IPCA') ?>.</p>
        <h2>Como se proteger</h2>
        <ul>
        <li>Planeje compras de fim de ano com antecedência e compare preços.</li>
        <li>Evite parcelar no rotativo do cartão; os juros são muito altos.</li>
        <li>Mantenha parte da reserva em investimentos que acompanham a inflação (IPCA+) se o objetivo é longo.</li>
        <li>Para viagens, divida a compra de moeda em etapas.</li>
        </ul>
        <p>Leia ainda <?= lp('petroleo-guerras-impacto-dolar-inflacao-bolsa','como o petróleo afeta a inflação') ?>.</p>
        <?php
    },
];
