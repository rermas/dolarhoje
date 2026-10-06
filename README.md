# Dólar Hoje

Site em um único `index.php` (PHP 8.1+, PDO/MySQL). Web: rotas, SEO e páginas. CLI: `php index.php cron` atualiza as cotações.

## Instalação
1. Copie `index.php`, `.htaccess` e `robots.txt` para a raiz do domínio (ou use o deploy do cPanel: ajuste `DEPLOYPATH` em `.cpanel.yml`).
2. **Apague os `.php` antigos** da raiz (libra.php, query.php, classe.php, etc.): o `.htaccess` manda todo `.php` para o `index.php`, mas os arquivos antigos não devem ficar expostos (o `classe.php` tem a senha do banco).
3. Crie `config.php` na raiz (não vai para o Git) com `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` e, opcionais, `SITE_URL`, `GA_ID`, `ADSENSE_ID`, `SPREAD_USD`, `SPREAD_EUR`, `CRON_KEY`, no formato `define('DB_PASS', 'senha');`.
4. Cron (cPanel > Cron Jobs), por exemplo de hora em hora entre 8h e 19h:
   `5 8-19 * * * /usr/local/bin/php /home/simul637/public_html/index.php cron >/dev/null 2>&1`
   Alternativa por URL (se o cron só aceita URL): defina `CRON_KEY` no `config.php` e agende `wget -q -O /dev/null "https://www.dolarhoje.net.br/fetch-cotacoes.php?key=SUA_CHAVE"`. Sem chave válida a URL responde 404.
5. Opcional: `GA_ID` (padrão `UA-6425016-24`; aceita `G-...`; vazio desliga) e `ADSENSE_ID` (padrão `ca-pub-1615119579984751`; vazio desliga) no `config.php`.

## Rotas
`/`, `/dolar-comercial/`, `/dolar-turismo/`, `/dolar-real/`, `/euro/`, `/euro-turismo/`, `/conversor-de-moedas/`, `/{moeda}.php` (todas as moedas do menu "+ Outros"), `/sitemap.xml`, `/?action=cotacoes` (JSON). URLs antigas sem página equivalente: edite `LEGADO` no `index.php`.

## Turismo
`turismo = comercial x (1 + spread)`; spreads em `config.php` (padrão 4,5% USD e 5% EUR). É estimativa: calibre comparando com casas de câmbio reais.

## Banco
Usa as tabelas `moedas`, `selic`, `ipca` e `cdi` (não usa mais `moedahistorico`, sem histórico de cotações). O cron ignora valores que variem mais de 35% em relação ao anterior.
