<?php
/** Post: impacto das eleições 2026 nos investimentos (visão geral por cenário). */
return [
    'slug'       => 'eleicoes-2026-investimentos-lula-ou-flavio',
    'titulo'     => 'Eleições 2026 e investimentos: o que muda se Lula ou Flávio Bolsonaro vencer',
    'resumo'     => 'Como o mercado costuma reagir a cada cenário do segundo turno, por quais canais a eleição chega à sua carteira e o que acompanhar depois do resultado.',
    'data'       => '2026-10-07',
    'atualizado' => null,
    'conteudo'   => function (): void {
        $ind = indicadores();
        ?>
        <p>A eleição presidencial mexe com investimentos por um motivo simples: ela muda as <strong>expectativas</strong> sobre as contas públicas, os juros e a relação do governo com as empresas. Como preços de mercado negociam o futuro, dólar, bolsa e juros futuros costumam reagir antes mesmo do resultado.</p>
        <p>Este artigo explica, de forma neutra, como o mercado costuma raciocinar nos dois cenários do segundo turno, entre Lula e Flávio Bolsonaro. Não é opinião política nem recomendação de voto ou de investimento. O texto foi escrito antes do resultado.</p>

        <h2>Por quais canais a eleição chega à sua carteira</h2>
        <ul>
            <li><strong>Risco fiscal:</strong> quanto mais dúvida sobre a trajetória da dívida pública, maior o prêmio que os investidores exigem para financiar o governo. Isso afeta os juros longos e o dólar.</li>
            <li><strong>Juros:</strong> a <?= lp('selic-cdi-ipca-o-que-sao', 'Selic') ?> é definida pelo Banco Central, mas os juros de longo prazo (títulos prefixados e IPCA+) dependem da confiança no cenário fiscal.</li>
            <li><strong>Câmbio:</strong> mais confiança tende a atrair capital e fortalecer o real; mais incerteza, o contrário. Veja <?= lp('o-que-faz-o-dolar-subir-ou-cair', 'o que faz o dólar subir ou cair') ?>.</li>
            <li><strong>Empresas:</strong> política para estatais, regulação e crédito afetam setores de formas diferentes.</li>
        </ul>

        <h2>O que o mercado realmente olha</h2>
        <p>Menos o nome do candidato e mais os sinais concretos depois da eleição:</p>
        <ul>
            <li>Quem será o ministro da Fazenda e a equipe econômica.</li>
            <li>Compromisso com a meta fiscal e o arcabouço de gastos.</li>
            <li>A força do governo eleito no Congresso, que decide se as medidas passam.</li>
            <li>A relação com o Banco Central e a postura em relação às estatais.</li>
        </ul>

        <h2>Dois cenários, em linhas gerais</h2>
        <table>
            <thead><tr><th>Ponto</th><th>Vitória de Lula</th><th>Vitória de Flávio Bolsonaro</th></tr></thead>
            <tbody>
            <tr><td>Como o mercado costuma ler</td><td>Continuidade do governo atual. O foco é o compromisso com a meta fiscal e o ritmo dos gastos.</td><td>Alternância. Costuma-se associar a expectativa de maior ajuste fiscal e menos intervenção, com dúvida sobre governabilidade.</td></tr>
            <tr><td>Se os sinais forem bons</td><td>Juros longos e dólar podem se acalmar, e a bolsa reagir bem.</td><td>Juros futuros podem fechar, o real se fortalecer e a bolsa subir.</td></tr>
            <tr><td>Se os sinais forem ruins</td><td>Juros longos e dólar sob pressão, e bolsa mais seletiva.</td><td>Frustração de expectativas, ruído político e volatilidade.</td></tr>
            </tbody>
        </table>
        <p>Em nenhum dos dois casos o resultado é automático. A reação depende do que for anunciado e da avaliação dos investidores sobre o que é viável aprovar.</p>

        <h2>O que já está no preço</h2>
        <p>Quando o mercado espera um resultado, boa parte do movimento acontece antes. O risco para o investidor comum é comprar ou vender no impulso, pagando caro por algo que já aconteceu. Por isso, mudanças bruscas na carteira em cima da eleição costumam ter resultado pior que um plano definido com antecedência.</p>

        <?php if ($ind): ?>
        <h2>Onde estamos hoje</h2>
        <ul>
            <?php foreach ($ind as [$l, $v, $ref]): ?>
            <li><strong><?= h($l) ?>:</strong> <?= h($v) ?> (<?= h($ref) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h2>Continue a leitura</h2>
        <p><?= lp('eleicoes-2026-perfil-investidor-segundo-turno', 'Como cada perfil de investidor pode se posicionar') ?> e <?= lp('eleicoes-2026-quais-investimentos-ganham-e-sofrem', 'quais investimentos tendem a ganhar ou sofrer em cada cenário') ?>. Acompanhe também o <a href="/dolar-grafico/">gráfico do dólar</a>.</p>
        <p class="note">Conteúdo educativo e informativo, sem recomendação de compra, venda ou voto. Cenários são hipóteses; resultados passados e expectativas não garantem resultados futuros. Consulte um profissional habilitado antes de investir.</p>
        <?php
    },
];
