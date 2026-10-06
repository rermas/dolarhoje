<?php
/** Post: IOF em viagens internacionais. */
return [
    'slug'       => 'como-pagar-menos-iof-viagem-internacional',
    'titulo'     => 'Como pagar menos IOF em viagens internacionais',
    'resumo'     => 'Entenda onde o IOF incide quando você compra moeda ou paga no exterior, e que escolhas ajudam a reduzir o custo total da viagem.',
    'data'       => '2026-10-13',
    'atualizado' => null,
    'conteudo'   => function (): void {
        ?>
        <p>O IOF (Imposto sobre Operações Financeiras) é cobrado em operações de câmbio, e costuma ser uma das maiores surpresas no custo de uma viagem. O ponto principal: a alíquota varia conforme o tipo de operação e pode mudar por decreto do governo. Por isso, antes de fechar qualquer compra, confirme a alíquota vigente com a instituição ou no site da Receita Federal.</p>

        <h2>Onde o IOF incide</h2>
        <ul>
            <li>Compra de moeda em espécie.</li>
            <li>Compras no exterior com cartão de crédito ou débito.</li>
            <li>Recarga de cartão pré-pago em moeda estrangeira.</li>
            <li>Transferências e remessas internacionais, em geral, inclusive para contas globais.</li>
        </ul>
        <p>As alíquotas de cada modalidade podem ser diferentes entre si. Comparar só a cotação do dólar não basta: o que vale é o <strong>custo total em reais</strong>, somando cotação, spread, IOF e tarifas.</p>

        <h2>O que ajuda a gastar menos</h2>
        <ol>
            <li><strong>Compare o valor final</strong>, em reais, de cada forma de pagamento: espécie, cartão de crédito, pré-pago e conta global. Pergunte quanto vai pagar no total por determinado valor em moeda estrangeira.</li>
            <li><strong>Pague na moeda local.</strong> Em maquininhas no exterior, é comum aparecer a opção de pagar em reais. Essa conversão, feita pelo lojista, costuma ter cotação pior. Escolha sempre a moeda local.</li>
            <li><strong>Evite sobras de dinheiro em espécie.</strong> Voltar com cédulas e vendê-las de novo gera custo duas vezes (spread na compra e na venda).</li>
            <li><strong>Planeje as recargas.</strong> Em cartões pré-pago e contas globais, recarregar quando o câmbio estiver mais favorável ajuda, mas sem tentar adivinhar o melhor dia: dividir em partes diminui o risco.</li>
            <li><strong>Reserve algum dinheiro em espécie</strong> só para despesas em que cartão não funciona, e não para a viagem inteira.</li>
        </ol>

        <h2>Faça a conta antes</h2>
        <p>Use o <a href="/conversor-de-moedas/">conversor</a> para ver o valor da sua viagem em reais, e a estimativa de <a href="/dolar-turismo/">dólar turismo</a> para calcular o gasto com cédulas. Depois some o IOF da modalidade escolhida. Veja também <?= lp('dolar-turismo-vs-comercial', 'a diferença entre dólar turismo e comercial') ?>.</p>
        <p class="note">Informação de caráter geral, sem aconselhamento fiscal. Regras e alíquotas mudam; confirme sempre a legislação vigente.</p>
        <?php
    },
];
