<?php
/** Post: Selic, CDI e IPCA. */
return [
    'slug'       => 'selic-cdi-ipca-o-que-sao',
    'titulo'     => 'Selic, CDI e IPCA: o que são e como se relacionam',
    'resumo'     => 'Entenda de forma simples o que cada indicador mede, quem os define e como eles afetam seus investimentos e o dólar.',
    'data'       => '2026-10-27',
    'atualizado' => null,
    'conteudo'   => function (): void {
        $ind = indicadores();
        ?>
        <p>Três siglas aparecem em toda conversa sobre economia e investimentos. Elas se relacionam entre si e também com o câmbio.</p>

        <h2>Selic</h2>
        <p>A taxa Selic é a taxa básica de juros do país. O Comitê de Política Monetária (Copom) do Banco Central define a meta da Selic em reuniões periódicas, cerca de oito por ano, e a taxa é expressa em % ao ano. Ela serve de referência para todos os outros juros da economia, de financiamentos a aplicações de renda fixa. Juros altos desestimulam o consumo e ajudam a segurar a inflação, e também tornam o Brasil mais atraente para investidores estrangeiros.</p>

        <h2>CDI</h2>
        <p>O CDI (Certificado de Depósito Interbancário) é a taxa dos empréstimos de curtíssimo prazo entre bancos. Ele anda muito próximo da Selic e é a principal referência de rentabilidade da renda fixa: um investimento que "rende 100% do CDI" acompanha, na prática, a Selic.</p>

        <h2>IPCA</h2>
        <p>O IPCA, divulgado mensalmente pelo IBGE, é a inflação oficial do país: mede a variação média dos preços de uma cesta de produtos e serviços consumidos pelas famílias. Compará-lo com a rentabilidade dos investimentos mostra se o seu dinheiro está ganhando ou perdendo poder de compra, o chamado ganho real.</p>

        <?php if ($ind): ?>
        <h2>Valores mais recentes no Dólar Hoje</h2>
        <ul>
            <?php foreach ($ind as [$l, $v, $ref]): ?>
            <li><strong><?= h($l) ?>:</strong> <?= h($v) ?> (<?= h($ref) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h2>E o dólar com isso?</h2>
        <p>A diferença entre os juros brasileiros e os de outros países influencia o fluxo de capital e, por consequência, o câmbio. Veja <?= lp('o-que-faz-o-dolar-subir-ou-cair', 'o que faz o dólar subir ou cair') ?> e acompanhe a <a href="/">cotação de hoje</a>.</p>
        <p class="note">Conteúdo informativo, não constitui recomendação de investimento.</p>
        <?php
    },
];
