<?php
/** Post: posicionamento por perfil de investidor no segundo turno. */
return [
    'slug'       => 'eleicoes-2026-perfil-investidor-segundo-turno',
    'titulo'     => 'Como cada perfil de investidor pode se posicionar no segundo turno',
    'resumo'     => 'Conservador, moderado e arrojado: princípios para lidar com a volatilidade da eleição sem apostar a carteira inteira no resultado.',
    'data'       => '2026-10-09',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>Eleição gera volatilidade, e a reação mais comum é mexer na carteira por impulso. Antes de qualquer ajuste, vale lembrar que o <strong>perfil de investidor</strong> e o <strong>prazo</strong> dos seus objetivos pesam mais que o resultado de uma única eleição. Veja a <?= lp('eleicoes-2026-investimentos-lula-ou-flavio', 'visão geral dos dois cenários') ?> antes de ler este texto.</p>
        <p>O conteúdo é educativo e geral: não considera a sua situação pessoal.</p>

        <h2>Princípios que valem para todo mundo</h2>
        <ul>
            <li><strong>Reserva de emergência fora do jogo.</strong> Fica em ativos de liquidez diária e baixo risco, como Tesouro Selic ou CDB com liquidez, e não depende da eleição.</li>
            <li><strong>Não aposte tudo em um resultado.</strong> Ninguém sabe o desfecho nem como o mercado vai reagir. Diversificar é a proteção contra errar a aposta.</li>
            <li><strong>Mudanças graduais.</strong> Dividir ajustes em várias etapas reduz o risco de agir no pior momento.</li>
            <li><strong>Considere custos.</strong> Vender gera imposto e custos. Girar a carteira por causa de uma eleição pode custar mais do que rende.</li>
        </ul>

        <h2>Perfil conservador</h2>
        <p>Prioriza segurança e previsibilidade.</p>
        <ul>
            <li>Núcleo em pós-fixados (Tesouro Selic, CDB, LCI/LCA), que rendem a taxa de juros do momento e oscilam pouco.</li>
            <li>Se tiver títulos IPCA+, prefira prazos curtos e médios, mantidos até o vencimento, para não sofrer com a marcação a mercado.</li>
            <li>Evite apostar em prefixados longos, que oscilam bastante com a mudança das expectativas.</li>
            <li>Pode manter uma pequena parte em ativos no exterior ou em dólar como proteção, sem exagero.</li>
        </ul>

        <h2>Perfil moderado</h2>
        <p>Aceita oscilação em troca de retorno maior no longo prazo.</p>
        <ul>
            <li>Núcleo em renda fixa (pós-fixados e IPCA+) e uma parte em ações diversificadas, com empresas de setores diferentes.</li>
            <li>Mistura de empresas pagadoras de dividendos, exportadoras e domésticas, para que a carteira não dependa de um único cenário.</li>
            <li>Alguma exposição ao exterior (fundos, ETFs, BDRs) ajuda a diversificar o risco do país.</li>
            <li>Após o resultado, rebalancear aos poucos, voltando aos percentuais planejados, em vez de reagir ao primeiro dia.</li>
        </ul>

        <h2>Perfil arrojado</h2>
        <p>Tolera volatilidade e perdas temporárias maiores.</p>
        <ul>
            <li>Pode ter visão tática, mas com limite de tamanho por posição e plano de saída definido antes.</li>
            <li>Evite alavancagem e derivativos que você não domine: em período eleitoral os movimentos são rápidos.</li>
            <li>Teses por cenário devem ser pequenas parcelas da carteira, e não a carteira inteira. Veja <?= lp('eleicoes-2026-quais-investimentos-ganham-e-sofrem', 'o que tende a ganhar e sofrer em cada cenário') ?>.</li>
            <li>Mantenha uma parte líquida para aproveitar oportunidades se houver queda forte.</li>
        </ul>

        <h2>Cinco perguntas antes de mexer na carteira</h2>
        <ol>
            <li>Meu objetivo e meu prazo mudaram por causa da eleição? (em geral, não)</li>
            <li>Minha reserva de emergência está intacta?</li>
            <li>Estou reagindo a uma expectativa que já pode estar no preço?</li>
            <li>Quanto vou pagar de imposto e custos para mudar?</li>
            <li>Se eu estiver errado, a perda é suportável?</li>
        </ol>
        <p>Para acompanhar o câmbio, use o <a href="/dolar-grafico/">gráfico do dólar</a> e o <a href="/conversor-de-moedas/">conversor</a>.</p>
        <p class="note">Conteúdo educativo e informativo, sem recomendação personalizada de investimento. Cada investidor deve avaliar objetivos, prazo e tolerância a risco, de preferência com um profissional habilitado.</p>
        <?php
    },
];
