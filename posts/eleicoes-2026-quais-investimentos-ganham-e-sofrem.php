<?php
/** Post: classes de ativos por cenário eleitoral. */
return [
    'slug'       => 'eleicoes-2026-quais-investimentos-ganham-e-sofrem',
    'titulo'     => 'Quais investimentos tendem a ganhar ou sofrer em cada cenário do segundo turno',
    'resumo'     => 'Renda fixa, bolsa, dólar, ouro e fundos imobiliários: como cada classe de ativo costuma reagir se Lula ou Flávio Bolsonaro vencer.',
    'data'       => '2026-10-12',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>Cada classe de ativo reage de forma diferente a mudanças no cenário de juros, câmbio e confiança. A tabela abaixo resume reações <strong>típicas</strong> em cada cenário, sempre condicionadas ao que o governo eleito sinalizar. Leia primeiro a <?= lp('eleicoes-2026-investimentos-lula-ou-flavio', 'visão geral dos dois cenários') ?>.</p>
        <p>Aviso: são hipóteses de mercado, não previsões nem recomendações. Ativos que "sofrem" em um cenário não estão errados, só ficam menos favorecidos no curto prazo.</p>

        <table>
            <thead><tr><th>Classe</th><th>Se Lula vencer</th><th>Se Flávio Bolsonaro vencer</th></tr></thead>
            <tbody>
            <tr><td>Pós-fixados (Selic, CDI)</td><td>Beneficiados: rendem a taxa de juros e protegem da volatilidade</td><td>Seguem sólidos, mas com custo de oportunidade se os juros caírem mais rápido</td></tr>
            <tr><td>Prefixados e IPCA+ longos</td><td>Tendem a oscilar para baixo se a curva abrir</td><td>Podem se valorizar com o fechamento da curva</td></tr>
            <tr><td>IPCA+ curto e médio</td><td>Boa proteção, sobretudo mantidos até o vencimento</td><td>Mantêm o papel de proteção contra a inflação</td></tr>
            <tr><td>Ações de dividendos e defensivas</td><td>Tendem a ter preferência pela previsibilidade</td><td>Seguem atrativas, porém com menos destaque</td></tr>
            <tr><td>Exportadoras</td><td>Beneficiadas se o dólar subir</td><td>Menos favorecidas se o real se fortalecer</td></tr>
            <tr><td>Cíclicas domésticas (varejo, construção) e small caps</td><td>Tendem a sofrer se os juros ficarem altos por mais tempo</td><td>Costumam ser as mais beneficiadas se os juros caírem</td></tr>
            <tr><td>Estatais</td><td>Risco de interferência é o ponto de atenção</td><td>Possível reavaliação positiva se houver menos interferência</td></tr>
            <tr><td>Dólar, ouro e exterior</td><td>Proteção útil se o câmbio subir</td><td>Perdem espaço como proteção se o real se fortalecer</td></tr>
            <tr><td>FIIs</td><td>De papel, atrelados ao CDI, resistem melhor</td><td>De tijolo costumam se beneficiar de juros menores</td></tr>
            </tbody>
        </table>

        <h2>Renda fixa: o detalhe da marcação a mercado</h2>
        <p>Títulos prefixados e IPCA+ longos oscilam de preço quando os juros mudam, mas quem os <strong>mantém até o vencimento</strong> recebe a taxa contratada. A oscilação só vira prejuízo para quem precisa vender antes. Por isso, o prazo da carteira importa mais que a eleição.</p>

        <h2>Bolsa: setor importa mais que "a bolsa"</h2>
        <p>Dizer que a bolsa sobe ou cai por causa do resultado simplifica demais. Empresas exportadoras e domésticas, endividadas e com caixa, reguladas e estatais reagem de formas diferentes. Diversificar entre setores reduz a dependência de um cenário.</p>

        <h2>Dólar e ouro: proteção, não aposta</h2>
        <p>Ter parte do patrimônio em dólar, ouro ou ativos no exterior é uma forma de diversificar o risco do país, e não uma previsão de alta. Acompanhe o <a href="/dolar-comercial/">dólar hoje</a> e o <a href="/dolar-grafico/">gráfico</a> para ver a tendência.</p>

        <h2>O que serve nos dois cenários</h2>
        <ul>
            <li>Reserva de emergência em liquidez diária.</li>
            <li>Diversificação entre renda fixa, ações e exterior.</li>
            <li>Vencimentos escalonados na renda fixa.</li>
            <li>Mudanças graduais, com plano definido antes, e não reação a manchetes. Veja <?= lp('eleicoes-2026-perfil-investidor-segundo-turno', 'como cada perfil pode se posicionar') ?>.</li>
        </ul>
        <p class="note">Conteúdo educativo e informativo, sem recomendação de compra ou venda de ativos específicos. Rentabilidade passada não garante resultados futuros. Consulte um profissional habilitado antes de investir.</p>
        <?php
    },
];
