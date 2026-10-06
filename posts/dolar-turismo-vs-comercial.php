<?php
/** Post: diferença entre dólar turismo e comercial. */
return [
    'slug'       => 'dolar-turismo-vs-comercial',
    'titulo'     => 'Dólar turismo x dólar comercial: qual a diferença e por que o turismo é mais caro',
    'resumo'     => 'Entenda o que é cada cotação, de onde vem a diferença entre elas e como estimar quanto você realmente vai pagar ao comprar dólar para viajar.',
    'data'       => '2026-10-06',
    'atualizado' => null,
    'conteudo'   => function (): void {
        $c = cotacoes()['usd'] ?? null;
        ?>
        <p>Quem acompanha a cotação do dólar logo percebe que existem pelo menos dois números: o <a href="/dolar-comercial/">dólar comercial</a>, que aparece nos noticiários, e o <a href="/dolar-turismo/">dólar turismo</a>, que é o que a casa de câmbio cobra de quem vai viajar. Eles nunca são iguais, e a diferença tem explicação.</p>

        <h2>O que é o dólar comercial</h2>
        <p>É a cotação de referência do mercado de câmbio, negociada entre bancos e grandes empresas, em operações como importação, exportação e investimentos. É o número que sobe e desce ao longo do dia e que serve de base para todos os outros.</p>

        <h2>O que é o dólar turismo</h2>
        <p>É o preço de varejo: o valor pelo qual uma casa de câmbio, banco ou corretora vende moeda em espécie, cartão pré-pago ou conta global ao viajante. Hoje não existe um mercado oficial separado para o "turismo"; o termo descreve o preço final, que parte do comercial e recebe acréscimos.</p>

        <h2>De onde vem a diferença</h2>
        <ul>
            <li><strong>Spread:</strong> a margem da instituição. É onde ela ganha dinheiro e varia de uma casa para outra.</li>
            <li><strong>Custo de operar com cédulas:</strong> transporte, segurança, armazenamento e seguro do dinheiro em espécie.</li>
            <li><strong>Volume pequeno:</strong> quem compra US$ 500 não tem o poder de negociação de uma empresa que compra milhões.</li>
            <li><strong>Impostos:</strong> o IOF é cobrado por fora e soma ao preço final.</li>
        </ul>

        <h2>Quanto o turismo costuma custar a mais</h2>
        <p>Não existe um número fixo: depende da instituição, do canal (balcão, entrega, aplicativo) e até do valor comprado. Por isso o Dólar Hoje mostra uma <strong>estimativa</strong>, calculada como a cotação comercial mais um percentual de spread, e não a cotação oficial de nenhuma casa.</p>
        <?php if ($c): $com = $c['v']; $tur = turismo('usd', $com); ?>
        <p>Com a cotação de hoje, a conta fica assim para US$ 1.000:</p>
        <ul>
            <li>Dólar comercial: R$ <?= fmt($com) ?> &rarr; R$ <?= number_format(1000 * $com, 2, ',', '.') ?></li>
            <li>Dólar turismo estimado (comercial + <?= number_format(spread('usd') * 100, 1, ',', '') ?>%): R$ <?= fmt($tur) ?> &rarr; R$ <?= number_format(1000 * $tur, 2, ',', '.') ?></li>
            <li>Diferença: R$ <?= number_format(1000 * ($tur - $com), 2, ',', '.') ?>, ainda sem o IOF.</li>
        </ul>
        <?php endif; ?>

        <h2>Como pagar menos</h2>
        <ol>
            <li>Compare o <strong>valor final em reais</strong> em pelo menos três instituições, já com todas as taxas. Não compare só a cotação anunciada.</li>
            <li>Pergunte se há taxa de entrega, de emissão do cartão ou de recarga.</li>
            <li>Compre aos poucos, em vez de uma vez só, para diluir a oscilação do câmbio.</li>
            <li>Avalie conta global e cartão pré-pago, que costumam ter spread menor que o dinheiro em espécie. Compare o custo total, inclusive o IOF.</li>
        </ol>
        <p>Para simular valores, use o <a href="/conversor-de-moedas/">conversor de moedas</a> e a <a href="/dolar-turismo/">calculadora de dólar turismo</a>. Veja também: <?= lp('como-pagar-menos-iof-viagem-internacional', 'como pagar menos IOF em viagens internacionais') ?>.</p>
        <p class="note">Este artigo tem caráter informativo e não é recomendação financeira.</p>
        <?php
    },
];
