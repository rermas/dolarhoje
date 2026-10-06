<?php
// Copie para config.php NO SERVIDOR (ele é ignorado pelo Git e bloqueado no .htaccess).
define('DB_HOST', 'localhost');
define('DB_NAME', 'simul637_dolarhoje');
define('DB_USER', 'simul637_rermas');
define('DB_PASS', 'COLOQUE_A_SENHA_AQUI');

// Opcionais
define('SITE_URL', 'https://www.dolarhoje.net.br');
define('GA_ID', '');          // ID do Google Analytics 4, ex.: G-XXXXXXXXXX (IDs UA- não funcionam mais)
define('ADSENSE_ID', '');     // ex.: ca-pub-1615119579984751
define('SPREAD_USD', 0.045);  // dólar turismo = comercial x (1 + spread)
define('CRON_KEY', '');       // opcional: chave longa (16+) para o cron por URL /fetch-cotacoes.php?key=...
define('SPREAD_EUR', 0.05);   // euro turismo  = comercial x (1 + spread)
