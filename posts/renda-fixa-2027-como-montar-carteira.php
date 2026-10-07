<?php
/** Post: Renda fixa em 2027: pós-fixado, prefixado ou IPCA+? Como pensar a carteira */
return [
    'slug'       => 'renda-fixa-2027-como-montar-carteira',
    'titulo'     => 'Renda fixa em 2027: pós-fixado, prefixado ou IPCA+? Como pensar a carteira',
    'resumo'     => 'Com eleição definida e juros ainda elevados, veja como comparar títulos pós-fixados, prefixados e atrelados à inflação.',
    'data'       => '2026-10-30',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>Com o cenário eleitoral resolvido e juros ainda em patamar elevado, muita gente volta a se perguntar onde colocar o dinheiro na renda fixa. A resposta depende de prazo, objetivo e perfil.</p>
        <h2>Os três grandes grupos</h2>
        <ul>
        <li><strong>Pós-fixados (Selic/CDI):</strong> acompanham os juros do dia a dia; adequados para reserva de emergência e curto prazo.</li>
        <li><strong>Prefixados:</strong> taxa travada na compra; ganham valor se os juros caírem e perdem se subirem, caso vendidos antes do vencimento.</li>
        <li><strong>IPCA+:</strong> garantem ganho acima da inflação; úteis para objetivos longos, como aposentadoria.</li>
        </ul>
        <h2>Como combinar</h2>
        <p>Uma abordagem comum é dividir por objetivo: reserva de emergência em pós-fixado com liquidez; metas de médio prazo em prefixado ou IPCA+ com vencimento próximo ao objetivo; e parte do longo prazo em IPCA+.</p>
        <h2>Pontos de atenção</h2>
        <ul>
        <li>Imposto de renda regressivo, de 22,5% a 15% conforme o prazo, na maioria dos títulos (isentos como LCI, LCA têm regras próprias).</li>
        <li>Limite de cobertura do FGC para emissões bancárias.</li>
        <li>Marcação a mercado nos títulos com vencimento longo.</li>
        </ul>
        <p>Revise os conceitos em <?= lp('selic-cdi-ipca-o-que-sao','Selic, CDI e IPCA') ?> e as estratégias por perfil em <?= lp('eleicoes-2026-perfil-investidor-segundo-turno','perfil de investidor no segundo turno') ?>.</p>
        <p class="note">Conteúdo educativo, sem recomendação de investimento. Cenários são hipóteses e mudam com novos fatos; confira dados atualizados e, se necessário, converse com um profissional habilitado.</p>
        <?php
    },
];
