<?php
declare(strict_types=1);
/**
 * Dólar Hoje — arquivo único.
 *  Web: roteia, consulta o banco (PDO + prepared statements) e renderiza.
 *  CLI: `php index.php cron` atualiza as cotações (Wise) na tabela moedas.
 * Segredos ficam em config.php (fora do Git). Veja README.md.
 */
date_default_timezone_set('America/Sao_Paulo');
if (is_file(__DIR__ . '/config.php')) require __DIR__ . '/config.php';

function cfg(string $k, mixed $d = ''): mixed { return defined($k) ? constant($k) : $d; }
function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* nome no banco => [ISO, rótulo, URL amigável] */
const MOEDAS = [
    'usd' => ['USD', 'Dólar Americano', 'dolar-comercial'], 'euro' => ['EUR', 'Euro', 'euro'], 'libra' => ['GBP', 'Libra Esterlina', 'libra-esterlina'],
    'usdaus' => ['AUD', 'Dólar Australiano', 'dolar-australiano'], 'usdcan' => ['CAD', 'Dólar Canadense', 'dolar-canadense'], 'sgd' => ['SGD', 'Dólar de Singapura', 'dolar-singapura'],
    'suico' => ['CHF', 'Franco Suíço', 'franco-suico'], 'china' => ['CNY', 'Yuan Chinês', 'yuan-chines'], 'japao' => ['JPY', 'Iene Japonês', 'iene-japones'],
    'arg' => ['ARS', 'Peso Argentino', 'peso-argentino'], 'chile' => ['CLP', 'Peso Chileno', 'peso-chileno'], 'uru' => ['UYU', 'Peso Uruguaio', 'peso-uruguaio'],
    'mxn' => ['MXN', 'Peso Mexicano', 'peso-mexicano'], 'cop' => ['COP', 'Peso Colombiano', 'peso-colombiano'], 'par' => ['PYG', 'Guarani Paraguaio', 'guarani-paraguaio'],
    'rub' => ['RUB', 'Rublo Russo', 'rublo-russo'], 'inr' => ['INR', 'Rupia Indiana', 'rupia-indiana'], 'idr' => ['IDR', 'Rupia Indonésia', 'rupia-indonesia'],
    'pkr' => ['PKR', 'Rupia Paquistanesa', 'rupia-paquistanesa'], 'aed' => ['AED', 'Dirham dos Emirados', 'dirham-emirados'], 'mad' => ['MAD', 'Dirham Marroquino', 'dirham-marroquino'],
    'qar' => ['QAR', 'Rial do Catar', 'rial-catari'], 'try' => ['TRY', 'Lira Turca', 'lira-turca'], 'hrk' => ['HRK', 'Kuna Croata', 'kuna-croata'],
    'iqd' => ['IQD', 'Dinar Iraquiano', 'dinar-iraquiano'], 'dkk' => ['DKK', 'Coroa Dinamarquesa', 'coroa-dinamarquesa'], 'sek' => ['SEK', 'Coroa Sueca', 'coroa-sueca'],
];
/* páginas de diretório com modo: caminho => [moeda, modo] */
const PAGINAS = [
    'dolar-comercial' => ['usd', 'comercial'], 'dolar-real' => ['usd', 'real'], 'dolar-turismo' => ['usd', 'turismo'],
    'euro' => ['euro', 'comercial'], 'euro-turismo' => ['euro', 'turismo'],
];
/* demais páginas (função de render) */
const ESPECIAIS = ['conversor-de-moedas' => 'pgConversor', 'dolar-ptax' => 'pgPtax', 'dolar-paralelo' => 'pgParalelo', 'dolar-grafico' => 'pgGrafico'];
/* URLs antigas fora do padrão "/{página}.php" => destino (301). Todo "/x.php" cujo x é uma moeda ou página acima redireciona sozinho. */
const LEGADO = [
    'index.php' => '/', 'dolar.php' => '/dolar-comercial/', 'dolarptax.php' => '/dolar-ptax/', 'bolivarvenezuelano.php' => '/', 'euro-comercial' => '/euro/',
];
function url(string $m): string { return '/' . MOEDAS[$m][2] . '/'; }
function slugs(): array { static $s = null; return $s ??= array_combine(array_column(MOEDAS, 2), array_keys(MOEDAS)); }

/* ---------- banco (PDO, prepared statements reais) ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = (string)cfg('DB_DSN') ?: 'mysql:host=' . cfg('DB_HOST', 'localhost') . ';dbname=' . cfg('DB_NAME') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string)cfg('DB_USER') ?: null, (string)cfg('DB_PASS') ?: null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
function q(string $sql, array $p = []): array { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function q1(string $sql, array $p = []): ?array { return q($sql, $p)[0] ?? null; }
function x(string $sql, array $p = []): int { $s = db()->prepare($sql); $s->execute($p); return $s->rowCount(); }

/* ---------- dados ---------- */
function cotacoes(): array {
    static $c = null;
    if ($c === null) {
        $raw = [];
        foreach (q('SELECT nome, valor, data FROM moedas') as $r) {
            if (isset(MOEDAS[$r['nome']]) && (float)$r['valor'] > 0) $raw[$r['nome']] = ['v' => (float)$r['valor'], 'data' => (string)$r['data']];
        }
        $c = [];
        foreach (MOEDAS as $n => $_) if (isset($raw[$n])) $c[$n] = $raw[$n];
    }
    return $c;
}
function spread(string $m): float { return (float)cfg($m === 'usd' ? 'SPREAD_USD' : 'SPREAD_EUR', $m === 'usd' ? 0.045 : 0.05); }
function turismo(string $m, float $com): float { return $com * (1 + spread($m)); }
function fmt(float $v): string { return number_format($v, $v >= 0.1 ? 4 : 6, ',', '.'); }
function indicadores(): array {
    $meses = [1 => 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $o = [];
    if ($s = q1('SELECT valor, data FROM selic ORDER BY data DESC LIMIT 1'))
        $o[] = ['SELIC (meta)', number_format((float)$s['valor'], 2, ',', '') . '% a.a.', 'reunião do Copom de ' . date('d/m/Y', strtotime((string)$s['data']))];
    foreach (['ipca' => 'IPCA', 'cdi' => 'CDI'] as $t => $l)
        if ($r = q1("SELECT mes, ano, valor FROM $t ORDER BY ano DESC, mes DESC LIMIT 1"))
            $o[] = [$l . ' (mês)', number_format((float)$r['valor'], 2, ',', '') . '%', ($meses[(int)$r['mes']] ?? '') . '/' . $r['ano']];
    return $o;
}
function dataIso(string $br): string { $p = explode('/', $br); return count($p) === 3 ? "$p[2]-$p[1]-$p[0]" : date('Y-m-d'); }
/* histórico (tabela moedahistorico: uma linha por moeda por dia). Se a tabela não existir, o site segue sem histórico. */
function historico(string $m, int $dias): array {
    try {
        $r = q('SELECT data AS d, valor AS v FROM moedahistorico WHERE moeda = ? AND data >= ? ORDER BY data', [$m, date('Y-m-d', strtotime("-$dias days"))]);
    } catch (PDOException) { $r = []; }
    $pts = array_map(fn($x) => ['d' => (string)$x['d'], 'v' => (float)$x['v']], $r);
    if ($c = cotacoes()[$m] ?? null) {   // inclui a cotação atual (hoje) se o dia ainda não estiver gravado no histórico
        $d = dataIso($c['data']);
        if ($d > ($pts ? $pts[count($pts) - 1]['d'] : '')) $pts[] = ['d' => $d, 'v' => $c['v']];
    }
    return $pts;
}
function anteriores(): array {   // último valor registrado antes da data da cotação atual, por moeda
    static $a = null;
    if ($a !== null) return $a;
    $a = []; $lim = '';
    foreach (cotacoes() as $r) $lim = max($lim, dataIso($r['data']));
    if ($lim === '') return $a;
    try {
        foreach (q('SELECT h.moeda, h.valor, h.data FROM moedahistorico h JOIN (SELECT moeda, MAX(data) AS d FROM moedahistorico WHERE data < ? GROUP BY moeda) x ON x.moeda = h.moeda AND x.d = h.data', [$lim]) as $r)
            $a[$r['moeda']] = ['v' => (float)$r['valor'], 'd' => (string)$r['data']];
    } catch (PDOException) {}
    return $a;
}
function gravaHist(string $m, float $v, ?string $d = null): void {
    $d ??= date('Y-m-d');
    try {
        $e = q1('SELECT cod FROM moedahistorico WHERE data = ? AND moeda = ?', [$d, $m]);
        $e ? x('UPDATE moedahistorico SET valor = ? WHERE cod = ?', [$v, $e['cod']])
           : x('INSERT INTO moedahistorico (data, moeda, valor) VALUES (?, ?, ?)', [$d, $m, $v]);
    } catch (PDOException $ex) { echo "AVISO histórico ($m): " . $ex->getMessage() . "\n"; }
}

/* ---------- cron (CLI) ---------- */
function http(string $url, ?int &$code = null): ?string {
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    $h = ['Accept: text/html,application/xhtml+xml', 'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'];
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_ENCODING => '', CURLOPT_USERAGENT => $ua, CURLOPT_HTTPHEADER => $h]);
        $r = curl_exec($c); $code = (int)curl_getinfo($c, CURLINFO_RESPONSE_CODE); curl_close($c);
        return $code === 200 && is_string($r) ? $r : null;
    }
    $r = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 20, 'user_agent' => $ua, 'header' => implode("\r\n", $h), 'ignore_errors' => true]]));
    $code = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int)$m[1] : 0;
    return $code === 200 && $r !== false ? $r : null;
}
function numero(string $s): ?float {
    if (str_contains($s, ',') && str_contains($s, '.')) $s = str_replace(',', '.', str_replace('.', '', $s));
    else $s = str_replace(',', '.', $s);
    return is_numeric($s) && (float)$s > 0 ? (float)$s : null;
}
function wise_parse(string $html, string $iso): ?float {
    $i = preg_quote($iso, '/');
    foreach (['/\$?1\s+' . $i . '\s*=\s*([\d.,]+)\s+BRL/i', '/>(\d+[.,]\d+)\s+BRL</i'] as $re)
        if (preg_match($re, $html, $m) && ($v = numero($m[1])) !== null) return $v;
    return null;
}
/* PTAX (Banco Central, API Olinda): grava compra ('ptaxc') e venda ('ptax') dos últimos dias úteis ainda não registrados. Falhas aqui não afetam o resto. */
function ptax(): void {
    try {
        for ($i = 0; $i < 6; $i++) {
            $t = strtotime("-$i days"); $d = date('Y-m-d', $t);
            if ((int)date('N', $t) >= 6 || ($i === 0 && (int)date('G') < 14)) continue;   // sem fim de semana; PTAX do dia só após as 13h
            if (q1('SELECT cod FROM moedahistorico WHERE data = ? AND moeda = ?', [$d, 'ptax'])) continue;
            $r = http("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoDolarDia(dataCotacao=@dataCotacao)?@dataCotacao='" . date('m-d-Y', $t) . "'&\$top=1&\$format=json", $code);
            $l = is_string($r) ? (json_decode($r, true)['value'][0] ?? null) : null;
            if (!$l || (float)($l['cotacaoVenda'] ?? 0) <= 0) { echo "PTAX $d: sem dados (HTTP " . ($code ?: 'sem resposta') . ")\n"; continue; }
            gravaHist('ptax', (float)$l['cotacaoVenda'], $d); gravaHist('ptaxc', (float)$l['cotacaoCompra'], $d);
            echo "OK PTAX $d = {$l['cotacaoVenda']}\n"; usleep(500000);
        }
    } catch (Throwable $e) { echo 'AVISO PTAX: ' . $e->getMessage() . "\n"; }
}
function cron(): int {
    $lk = @fopen(sys_get_temp_dir() . '/dolarhoje-cron.lock', 'c');
    if ($lk && !flock($lk, LOCK_EX | LOCK_NB)) { echo "já em execução\n"; return 0; }
    $hoje = date('d/m/Y'); $ok = 0; $falha = 0;
    foreach (MOEDAS as $nome => [$cod]) {
        $v = null; $motivo = '';
        for ($t = 0; $t < 2 && $v === null; $t++) {   // 1 nova tentativa após 3s (429/403/instabilidade)
            if ($t) sleep(3);
            $html = http("https://wise.com/br/currency-converter/{$cod}-to-brl-rate?amount=1", $code);
            if ($html === null) { $motivo = 'HTTP ' . ($code ?: 'sem resposta'); continue; }
            $v = wise_parse($html, $cod);
            if ($v === null) $motivo = 'HTTP 200, mas o valor não foi encontrado no HTML (layout da Wise mudou?)';
        }
        $ant = q1('SELECT valor FROM moedas WHERE nome = ?', [$nome]);
        if ($v === null) { echo "FALHA $cod: $motivo\n"; $falha++; }
        elseif ($ant && (float)$ant['valor'] > 0 && abs($v / (float)$ant['valor'] - 1) > 0.35) { echo "IGNORADO $cod: $v difere >35% de {$ant['valor']}\n"; $falha++; }
        else {
            $ant ? x('UPDATE moedas SET valor = ?, data = ? WHERE nome = ?', [$v, $hoje, $nome])
                 : x('INSERT INTO moedas (nome, valor, data) VALUES (?, ?, ?)', [$nome, $v, $hoje]);
            gravaHist($nome, $v);
            echo "OK $cod = $v\n"; $ok++;
        }
        usleep(1200000);
    }
    ptax();
    echo "$ok atualizadas, $falha com falha\n";
    return $ok > 0 ? 0 : 1;
}

if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') === 'cron') exit(cron());
    fwrite(STDERR, "uso: php index.php cron\n"); exit(2);
}

/* ---------- render ---------- */
function base(): string { return rtrim((string)cfg('SITE_URL', 'https://www.dolarhoje.net.br'), '/'); }
const CSS = ':root{--p:#fd7e14;--pd:#c2590a;--s:#6c757d;--bg:#fff;--soft:#fff6ee;--tx:#212529;--bd:#e9ecef}*{box-sizing:border-box;margin:0}html{scroll-behavior:smooth}'
 . 'body{font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--tx);background:var(--bg)}a{color:var(--pd)}.w{max-width:1100px;margin:0 auto;padding:0 16px}'
 . 'header{border-bottom:3px solid var(--p);background:#fff;position:sticky;top:0;z-index:9}header .w{display:flex;flex-wrap:wrap;align-items:center;gap:4px 18px;min-height:56px}'
 . '.logo{font-weight:800;font-size:1.25rem;color:var(--p);text-decoration:none}nav{display:flex;flex-wrap:wrap;gap:2px 14px;align-items:center}nav a,nav summary{color:var(--s);text-decoration:none;font-weight:600;padding:6px 0;cursor:pointer}nav a:hover,nav summary:hover{color:var(--p)}'
 . 'details.o{position:relative}details.o div{position:absolute;right:0;top:100%;background:#fff;border:1px solid var(--bd);border-radius:8px;box-shadow:0 8px 24px #0002;padding:8px;display:grid;grid-template-columns:repeat(2,minmax(150px,1fr));gap:2px 12px;width:min(92vw,400px)}details.o div a{padding:4px 6px;font-weight:500}'
 . '.hero{background:linear-gradient(135deg,var(--p),#ff9a3d);color:#fff;padding:28px 0}.hero h1{font-size:clamp(1.5rem,4vw,2.2rem);line-height:1.2}.hero p{opacity:.95;margin-top:6px}'
 . '.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:20px 0}.card{border:1px solid var(--bd);border-radius:12px;padding:16px;background:#fff;text-decoration:none;color:inherit;display:block;border-top:4px solid var(--p)}a.card:hover{box-shadow:0 4px 16px #0001}.card small{color:var(--s);display:block}.card b{display:block;font-size:1.7rem;color:var(--tx);font-variant-numeric:tabular-nums}.card.t{border-top-color:var(--s)}'
 . '.up{color:#198754}.dn{color:#dc3545}main{padding-bottom:30px}h2{font-size:1.3rem;margin:26px 0 10px}p{margin:8px 0}table{width:100%;border-collapse:collapse;font-variant-numeric:tabular-nums}th,td{padding:8px 10px;border-bottom:1px solid var(--bd);text-align:left}th{background:var(--soft);font-size:.85rem;color:var(--s)}td:last-child,th:last-child{text-align:right}'
 . '.calc,.cv{display:flex;flex-wrap:wrap;gap:10px;align-items:end;background:var(--soft);border-radius:12px;padding:14px;margin:12px 0}.calc label,.cv label{display:grid;font-size:.85rem;color:var(--s);font-weight:600}input,select{font:inherit;padding:8px 10px;border:1px solid #ced4da;border-radius:8px;min-width:130px}output{font-size:1.4rem;font-weight:800;color:var(--pd)}'
 . 'details.f{border:1px solid var(--bd);border-radius:8px;padding:10px 14px;margin:8px 0}details.f summary{font-weight:600;cursor:pointer}.note{font-size:.85rem;color:var(--s)}footer{background:#f8f9fa;border-top:1px solid var(--bd);padding:20px 0;color:var(--s);font-size:.9rem}.bc{font-size:.85rem;color:var(--s);margin:12px 0 0}'
 . '.ch{width:100%;height:auto;display:block;margin:8px 0}.ch text{font:11px system-ui,sans-serif;fill:#6c757d}form.f2{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:12px 0}form.f2 label{display:grid;font-size:.85rem;color:var(--s);font-weight:600}button{font:inherit;font-weight:700;padding:8px 18px;border:0;border-radius:8px;background:var(--p);color:#fff;cursor:pointer}details.o div{max-height:70vh;overflow:auto}'
 . '@media(max-width:640px){header{position:static}details.o div{position:static;box-shadow:none;width:auto}}';

function layout(string $title, string $desc, string $path, string $main, string $hero = '', string $ld = '', int $status = 200, bool $index = true): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    $url = base() . $path;
    $outros = '<a href="/dolar-ptax/">Dólar PTAX</a><a href="/dolar-paralelo/">Dólar Paralelo</a>';
    foreach (MOEDAS as $n => [$c, $l]) if (!in_array($n, ['usd', 'euro'], true)) $outros .= '<a href="' . url($n) . '">' . h($l) . '</a>';
    $nomePg = trim(explode(':', $title)[0]); $crumb = '';
    if ($path !== '/' && $status === 200) {
        $crumb = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => base() . '/'], ['@type' => 'ListItem', 'position' => 2, 'name' => $nomePg, 'item' => $url]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        $main = '<nav class="bc" aria-label="Você está em"><a href="/">Início</a> › ' . h($nomePg) . '</nav>' . $main;
    }
    $gsc = (string)cfg('GSC_VERIFY'); $gsc = preg_match('/^[\w-]{20,100}$/', $gsc) ? '<meta name="google-site-verification" content="' . $gsc . '">' : '';
    $ga = (string)cfg('GA_ID', 'G-96RWYM8GWR'); $ad = (string)cfg('ADSENSE_ID', 'ca-pub-1615119579984751');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . h($title) . '</title><meta name="description" content="' . h($desc) . '">'
       . ($index ? '<meta name="robots" content="index,follow,max-image-preview:large">' : '<meta name="robots" content="noindex">')
       . '<link rel="canonical" href="' . h($url) . '"><meta name="theme-color" content="#fd7e14">' . $gsc
       . '<meta property="og:type" content="website"><meta property="og:locale" content="pt_BR"><meta property="og:site_name" content="Dólar Hoje">'
       . '<meta property="og:title" content="' . h($title) . '"><meta property="og:description" content="' . h($desc) . '"><meta property="og:url" content="' . h($url) . '">'
       . '<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Ccircle cx=%2716%27 cy=%2716%27 r=%2716%27 fill=%27%23fd7e14%27/%3E%3Ctext x=%2716%27 y=%2722%27 font-size=%2718%27 text-anchor=%27middle%27 fill=%27white%27 font-family=%27Arial%27 font-weight=%27bold%27%3E$%3C/text%3E%3C/svg%3E">'
       . '<style>' . CSS . '</style>' . gtagHead($ga) . adsenseHead($ad) . ($ld ? '<script type="application/ld+json">' . $ld . '</script>' : '') . $crumb
       . '</head><body><header><div class="w"><a class="logo" href="/">Dólar Hoje</a><nav aria-label="Principal">'
       . '<a href="/dolar-comercial/">Dólar</a><a href="/dolar-turismo/">Dólar Turismo</a><a href="/euro/">Euro</a><a href="/euro-turismo/">Euro Turismo</a><a href="/dolar-grafico/">Gráfico</a><a href="/conversor-de-moedas/">Conversor</a>'
       . '<details class="o"><summary>+ Outros</summary><div>' . $outros . '</div></details></nav></div></header>'
       . $hero . '<main class="w">' . $main . '</main>'
       . '<footer><div class="w"><p>Cotações de referência, atualizadas várias vezes ao dia a partir de fontes públicas. Caráter informativo, sem oferta de compra ou venda. Valores de turismo são estimativas e variam entre casas de câmbio.</p><p>&copy; ' . date('Y') . ' Dólar Hoje</p></div></footer>'
       . '<script>document.querySelectorAll(".calc").forEach(function(f){var r=+f.dataset.r,a=f.querySelector("[data-k=f]"),b=f.querySelector("[data-k=b]"),n=function(x){return x>=1?x.toFixed(2):String(+x.toPrecision(4))};a.oninput=function(){b.value=n(a.value*r)};b.oninput=function(){a.value=n(b.value/r)}});'
       . 'var cv=document.getElementById("cv");if(cv){var R=JSON.parse(document.getElementById("rt").textContent),g=function(i){return cv.querySelector(i)},u=function(){var o=g("#v").value*R[g("#de").value]/R[g("#pa").value];g("output").textContent=isFinite(o)?o.toLocaleString("pt-BR",{maximumFractionDigits:o<1?6:4}):"-"};cv.oninput=u;u()}</script>'
       . '</body></html>';
}
function gtagHead(string $id): string {   // snippet padrão do Google (gtag.js), aceita G-, UA- ou AW-
    if (!preg_match('/^(G|UA|AW)-[A-Z0-9-]+$/', $id)) return '';
    return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . $id . '");</script>';
}
function adsenseHead(string $id): string {   // snippet padrão do AdSense
    return preg_match('/^ca-pub-\d+$/', $id) ? '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . $id . '" crossorigin="anonymous"></script>' : '';
}
function hero(string $h1, string $p): string { return '<div class="hero"><div class="w"><h1>' . h($h1) . '</h1><p>' . h($p) . '</p></div></div>'; }
function card(string $label, string $valor, string $sub, string $href, bool $t = false, string $pre = 'R$ '): string {
    return '<a class="card' . ($t ? ' t' : '') . '" href="' . h($href) . '"><small>' . h($label) . '</small><b>' . h($pre) . h($valor) . '</b><small>' . $sub . '</small></a>';
}
function calc(string $iso, float $rate, bool $inv = false): string {   // $inv: reais -> moeda
    $r = $inv ? 1 / $rate : $rate;
    $vb = $r >= 1 ? number_format($r, 2, '.', '') : (string)(float)number_format($r, 6, '.', '');
    return '<form class="calc" data-r="' . sprintf('%.10F', $r) . '" onsubmit="return false"><label>' . h($inv ? 'Reais (BRL)' : $iso) . '<input type="number" step="any" inputmode="decimal" data-k="f" value="1"></label>'
         . '<label>' . h($inv ? $iso : 'Reais (BRL)') . '<input type="number" step="any" inputmode="decimal" data-k="b" value="' . $vb . '"></label></form>';
}
function fmtq(float $v): string { return $v >= 100 ? number_format($v, 2, ',', '.') : fmt($v); }
function tabelaValores(string $iso, float $rate, bool $inv = false): string {   // valores prontos: moeda -> reais, ou reais -> moeda
    $o = '';
    foreach ($inv ? [1, 10, 50, 100, 500, 1000, 5000, 10000] : [1, 5, 10, 50, 100, 500, 1000, 5000, 10000] as $n)
        $o .= '<tr><td>' . ($inv ? 'R$ ' . number_format($n, 0, ',', '.') : number_format($n, 0, ',', '.') . ' ' . h($iso)) . '</td><td>'
            . ($inv ? number_format($n / $rate, 2, ',', '.') . ' ' . h($iso) : 'R$ ' . number_format($n * $rate, 2, ',', '.')) . '</td></tr>';
    return '<table><thead><tr><th>' . ($inv ? 'Reais (BRL)' : h($iso)) . '</th><th>' . ($inv ? h($iso) : 'Reais (BRL)') . '</th></tr></thead><tbody>' . $o . '</tbody></table>';
}
function convs(): array { static $c = null; if ($c === null) { $c = []; foreach (MOEDAS as $m => $v) $c[$m === 'usd' ? 'dolar' : $v[2]] = $m; } return $c; }
function cbase(string $m): string { return $m === 'usd' ? 'dolar' : MOEDAS[$m][2]; }
function faq(array $itens): array {
    $html = ''; $ld = [];
    foreach ($itens as [$p, $r]) {
        $html .= '<details class="f"><summary>' . h($p) . '</summary><p>' . h($r) . '</p></details>';
        $ld[] = ['@type' => 'Question', 'name' => $p, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $r]];
    }
    return [$html, json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
}
function irPara(string $to): never { header('Location: ' . $to, true, 301); exit; }
function erro(int $status, string $msg): never {
    layout($status === 404 ? 'Página não encontrada | Dólar Hoje' : 'Indisponível | Dólar Hoje', $msg, '/', '<h1>' . h($msg) . '</h1><p><a href="/">Voltar para a página inicial</a></p>', '', '', $status, false);
    exit;
}

/* ---------- histórico: gráfico SVG (sem JS) e tabela ---------- */
function pctSpan(float $atual, float $base): string {
    if ($base <= 0) return '';
    $p = ($atual / $base - 1) * 100;
    $cl = $p > 0.004 ? 'up' : ($p < -0.004 ? 'dn' : '');
    return '<span class="' . $cl . '">' . ($cl === 'up' ? '▲ ' : ($cl === 'dn' ? '▼ ' : '')) . number_format(abs($p), 2, ',', '') . '%</span>';
}
function grafico(array $pts, string $rotulo): string {
    $n = count($pts);
    if ($n < 2) return '<p class="note">O histórico está sendo formado: o gráfico aparece quando houver ao menos dois dias registrados.</p>';
    [$W, $H, $L, $R, $T, $B] = [720, 260, 64, 12, 14, 28];
    $vs = array_column($pts, 'v'); $mn = min($vs); $mx = max($vs);
    $pad = ($mx - $mn) > $mx * 0.002 ? ($mx - $mn) * 0.1 : $mx * 0.002; $mn -= $pad; $mx += $pad;
    $X = fn(int $i): float => round($L + ($W - $L - $R) * $i / ($n - 1), 1);
    $Y = fn(float $v): float => round($T + ($H - $T - $B) * (1 - ($v - $mn) / ($mx - $mn)), 1);
    $linha = ''; foreach ($pts as $i => $p) $linha .= ($i ? ' ' : '') . $X($i) . ',' . $Y($p['v']);
    $svg = '<svg class="ch" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . h($rotulo) . '"><title>' . h($rotulo) . '</title>';
    foreach ([0, .5, 1] as $f) {
        $v = $mn + ($mx - $mn) * $f; $y = $Y($v);
        $svg .= '<line x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . $y . '" y2="' . $y . '" stroke="#e9ecef"/><text x="' . ($L - 6) . '" y="' . ($y + 4) . '" text-anchor="end">' . fmt($v) . '</text>';
    }
    $svg .= '<polygon points="' . $X(0) . ',' . ($H - $B) . ' ' . $linha . ' ' . $X($n - 1) . ',' . ($H - $B) . '" fill="#fd7e14" fill-opacity=".12"/>'
          . '<polyline points="' . $linha . '" fill="none" stroke="#fd7e14" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>'
          . '<circle cx="' . $X($n - 1) . '" cy="' . $Y($pts[$n - 1]['v']) . '" r="4" fill="#fd7e14"/>';
    foreach ([[0, 'start'], [intdiv($n - 1, 2), 'middle'], [$n - 1, 'end']] as [$i, $a])
        $svg .= '<text x="' . $X($i) . '" y="' . ($H - 8) . '" text-anchor="' . $a . '">' . date('d/m/y', strtotime($pts[$i]['d'])) . '</text>';
    return $svg . '</svg>';
}
function histTabela(array $pts, int $max = 10, array $cols = []): string {
    $t = count($pts);
    if ($t < 1) return '';
    $o = '';
    for ($i = $t - 1; $i >= max(0, $t - $max); $i--)
        $o .= '<tr><td>' . date('d/m/Y', strtotime($pts[$i]['d'])) . '</td><td>R$ ' . fmt($pts[$i]['v']) . '</td><td>' . ($i ? pctSpan($pts[$i]['v'], $pts[$i - 1]['v']) : '–') . '</td></tr>';
    return '<table><thead><tr><th>Data</th><th>Valor</th><th>Variação</th></tr></thead><tbody>' . $o . '</tbody></table>';
}
function nomeMoeda(string $m): string { return $m === 'usd' ? 'Dólar' : MOEDAS[$m][1]; }

/* textos próprios de algumas moedas: 'sobre' (HTML confiável) e 'faq' ({v} = valor atual) */
const INFO = [
    'usdcan' => [
        'sobre' => [
            'O dólar canadense (CAD) é a moeda oficial do Canadá, emitida pelo Banco do Canadá. É conhecido como <b>loonie</b>, apelido que vem do mergulhão-do-norte (<i>loon</i>) estampado na moeda de 1 dólar.',
            'A economia canadense é muito ligada às commodities, sobretudo petróleo, gás natural, minerais e produtos agrícolas, e aos Estados Unidos, o principal parceiro comercial do país. Por isso o CAD costuma reagir ao preço do petróleo, às decisões de juros do Banco do Canadá e ao ritmo da economia norte-americana.',
            'Para o brasileiro, a cotação CAD/BRL interessa a quem estuda ou faz intercâmbio no Canadá, planeja viagem (Toronto, Vancouver, Montreal, Quebec, Calgary), envia dinheiro ou compra em lojas do exterior. O valor em reais é afetado por dois movimentos ao mesmo tempo: o do dólar canadense frente ao dólar americano e o do real frente ao dólar.',
        ],
        'faq' => [
            ['Quanto vale 1 dólar canadense em reais hoje?', 'A cotação de referência de hoje é de R$ {v} por 1 CAD. Na compra em banco ou casa de câmbio o valor final é maior, por causa do spread, do IOF e de eventuais taxas.'],
            ['Por que o dólar canadense é chamado de loonie?', 'Porque a moeda de 1 dólar do Canadá traz a imagem de um mergulhão-do-norte, ave chamada <i>loon</i> em inglês. O apelido se estendeu à moeda como um todo.'],
            ['O que faz o dólar canadense subir ou cair?', 'Principalmente o preço do petróleo e de outras commodities, a política de juros do Banco do Canadá, o desempenho da economia dos Estados Unidos e o apetite global por risco.'],
            ['Vale levar dólar americano ou canadense para o Canadá?', 'O ideal é a moeda local, porque evita uma conversão extra. Na prática, cartões são aceitos quase em toda parte; compare o custo total (câmbio, IOF e tarifas) entre cartão, conta global e dinheiro em espécie.'],
        ],
    ],
    'usdaus' => [
        'sobre' => [
            'O dólar australiano (AUD) é a moeda oficial da Austrália, emitida pelo Banco da Reserva da Austrália (RBA), e também é usada por países do Pacífico como Kiribati, Nauru e Tuvalu. É chamado de <b>aussie</b> no mercado.',
            'É uma moeda de commodities: a economia australiana exporta minério de ferro, carvão, gás natural e produtos agrícolas, e a China é um dos principais destinos dessas vendas. O AUD costuma responder ao preço desses produtos, ao crescimento chinês, aos juros definidos pelo RBA e ao apetite global por risco.',
            'Para quem mora no Brasil, a cotação AUD/BRL importa para intercâmbio, programas de trabalho e férias, viagens (Sydney, Melbourne, Gold Coast, Perth) e pagamentos em dólar australiano. Regras de visto e custos mudam; confirme sempre nos canais oficiais do governo australiano.',
        ],
        'faq' => [
            ['Quanto vale 1 dólar australiano em reais hoje?', 'A cotação de referência de hoje é de R$ {v} por 1 AUD. O valor cobrado por bancos e casas de câmbio é maior, pois inclui spread, IOF e taxas.'],
            ['Por que o dólar australiano é chamado de aussie?', '"Aussie" é o apelido informal dos australianos, e por extensão da moeda do país, o AUD, nos mercados financeiros.'],
            ['O que influencia o dólar australiano?', 'Preços de minério de ferro, carvão e gás, a economia da China, a taxa de juros do Banco da Reserva da Austrália e o cenário global de risco.'],
            ['Onde trocar dólar australiano com menor custo?', 'Compare o valor final em reais, com todos os custos, entre bancos, casas de câmbio e contas globais. Em geral, usar cartão ou conta internacional costuma ser mais barato do que cédulas em espécie.'],
        ],
    ],
    'sgd' => [
        'sobre' => [
            'O dólar de Singapura (SGD) é a moeda de Singapura, emitida pela Autoridade Monetária de Singapura (MAS), que também atua como banco central do país.',
            'A MAS é um caso à parte: em vez de conduzir a política monetária principalmente pelos juros, ela administra a taxa de câmbio efetiva do SGD, permitindo que flutue dentro de uma faixa em relação a uma cesta de moedas de parceiros comerciais. Isso ajuda a explicar a reputação de estabilidade da moeda.',
            'Singapura é um dos principais centros financeiros, comerciais e portuários da Ásia, com custo de vida elevado. A cotação SGD/BRL interessa a quem viaja para o país ou faz conexão por lá, estuda, faz negócios ou paga serviços e produtos em dólar de Singapura.',
        ],
        'faq' => [
            ['Quanto vale 1 dólar de Singapura em reais hoje?', 'A cotação de referência de hoje é de R$ {v} por 1 SGD. Na compra efetiva, bancos e casas de câmbio cobram valor maior, por causa do spread, do IOF e de taxas.'],
            ['Quem emite o dólar de Singapura?', 'A Autoridade Monetária de Singapura (MAS), banco central do país.'],
            ['O dólar de Singapura é estável?', 'Historicamente sim. A MAS administra o câmbio dentro de uma faixa frente a uma cesta de moedas, o que reduz oscilações bruscas. Ainda assim, o valor em reais varia com o dólar americano e com o real.'],
            ['Preciso de dólar de Singapura para viajar?', 'Cartões internacionais são amplamente aceitos, mas ter algum dinheiro em espécie ajuda em feiras e estabelecimentos pequenos. Compare o custo total de cada opção.'],
        ],
    ],
    'hrk' => [
        'sobre' => ['A Croácia adotou o euro em 1º de janeiro de 2023 e a kuna deixou de circular. A cotação exibida é de referência, para consulta: acompanha o euro pela taxa fixa de conversão (7,53450 kunas por euro). Para viagens à Croácia, use o <a href="/euro/">euro</a>.'],
        'faq' => [],
    ],
];

function pgHome(): void {
    $c = cotacoes(); $ant = anteriores();
    $cards = '';
    foreach (['usd' => ['Dólar comercial', 'Dólar turismo', '/dolar-comercial/', '/dolar-turismo/'], 'euro' => ['Euro comercial', 'Euro turismo', '/euro/', '/euro-turismo/']] as $m => [$l1, $l2, $u1, $u2]) {
        if (!isset($c[$m])) continue;
        $var = isset($ant[$m]) ? ' · ' . pctSpan($c[$m]['v'], $ant[$m]['v']) : '';
        $cards .= card($l1, fmt($c[$m]['v']), 'em ' . h($c[$m]['data']) . $var, $u1)
                . card($l2 . ' (estimado)', fmt(turismo($m, $c[$m]['v'])), 'comercial + ' . number_format(spread($m) * 100, 1, ',', '') . '%', $u2, true);
    }
    $ind = '';
    foreach (indicadores() as [$l, $v, $ref]) $ind .= '<div class="card t"><small>' . h($l) . '</small><b>' . h($v) . '</b><small>' . h($ref) . '</small></div>';
    $linhas = '';
    foreach ($c as $n => $r)
        $linhas .= '<tr><td><a href="' . url($n) . '">' . h(MOEDAS[$n][1]) . '</a></td><td>' . h(MOEDAS[$n][0]) . '</td><td>R$ ' . fmt($r['v']) . '</td><td>' . (isset($ant[$n]) ? pctSpan($r['v'], $ant[$n]['v']) : '–') . '</td></tr>';
    $pts = historico('usd', 30);
    $graf = count($pts) > 1 ? '<h2>Dólar nos últimos 30 dias</h2>' . grafico($pts, 'Dólar em reais, últimos 30 dias') . '<p><a href="/dolar-grafico/">Ver gráfico completo do dólar</a></p>' : '';
    $ld = json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Dólar Hoje', 'url' => base() . '/', 'inLanguage' => 'pt-BR'], JSON_UNESCAPED_SLASHES);
    $usd = isset($c['usd']) ? 'R$ ' . fmt($c['usd']['v']) : '';
    layout('Dólar hoje: cotação do dólar, euro e outras moedas', "Dólar comercial $usd e turismo, euro e mais " . (count($c) - 2) . ' moedas em real, com histórico, conversor e Selic, IPCA e CDI.', '/',
        '<div class="grid">' . $cards . '</div>' . $graf . '<h2>Indicadores econômicos</h2><div class="grid">' . $ind . '</div>'
        . '<h2>Todas as moedas em real</h2><table><thead><tr><th>Moeda</th><th>Código</th><th>Valor em R$</th><th>Variação</th></tr></thead><tbody>' . $linhas . '</tbody></table>'
        . '<h2>Como ler as cotações</h2><p>O <b>dólar comercial</b> é a referência do mercado, usada em operações de comércio exterior e investimentos. O <b>dólar turismo</b> é o valor cobrado de quem compra moeda em espécie ou cartão pré-pago para viajar e fica acima do comercial por causa do spread das casas de câmbio. Aqui o turismo é <a href="/dolar-turismo/">estimado a partir do comercial</a>.</p>'
        . '<h2>Conversões populares</h2><p><a href="/dolar-real/">Dólar para real</a> · <a href="/real-para-dolar/">Real para dólar</a> · <a href="/euro-para-real/">Euro para real</a> · <a href="/real-para-euro/">Real para euro</a> · <a href="/libra-esterlina-para-real/">Libra para real</a> · <a href="/dolar-canadense-para-real/">Dólar canadense para real</a> · <a href="/dolar-australiano-para-real/">Dólar australiano para real</a> · <a href="/peso-argentino-para-real/">Peso argentino para real</a></p>'
        . '<p>Veja também: <a href="/dolar-ptax/">Dólar PTAX</a> · <a href="/dolar-paralelo/">Dólar paralelo</a> · <a href="/dolar-grafico/">Gráfico do dólar</a> · <a href="/conversor-de-moedas/">Conversor de moedas</a>.</p>',
        hero('Dólar hoje', 'Cotação do dólar, euro e outras moedas em real, atualizada ao longo do dia.'), $ld);
}

function pgMoeda(string $m, string $modo): void {
    $c = cotacoes();
    if (!isset($c[$m])) erro(503, 'Cotação indisponível no momento');
    [$iso, $nome] = MOEDAS[$m]; $nome = nomeMoeda($m); $com = $c[$m]['v']; $data = $c[$m]['data'];
    $b = cbase($m); $nl = strtolower($nome);
    $rel = '<h2>Veja também</h2><p>' . ($m === 'usd' ? '<a href="/real-para-dolar/">Real para dólar</a> · ' : '<a href="/' . $b . '-para-real/">' . h($nome) . ' para real</a> · <a href="/real-para-' . $b . '/">Real para ' . h($nl) . '</a> · ') . ($m === 'usd' ? '<a href="/dolar-turismo/">Dólar turismo</a> · <a href="/dolar-real/">Dólar para real</a> · <a href="/dolar-ptax/">Dólar PTAX</a> · <a href="/dolar-paralelo/">Dólar paralelo</a> · <a href="/dolar-grafico/">Gráfico do dólar</a> · ' : '') . ($m === 'euro' ? '<a href="/euro-turismo/">Euro turismo</a> · ' : '') . '<a href="/conversor-de-moedas/">Conversor de moedas</a> · <a href="/">Todas as cotações</a></p>';
    if ($modo === 'turismo') {
        $tur = turismo($m, $com); $sp = number_format(spread($m) * 100, 1, ',', '');
        [$fh, $fld] = faq([
            ["Qual a diferença entre {$nome} comercial e turismo?", "O comercial é a cotação de referência do mercado. O turismo é o valor praticado na venda ao viajante, mais alto porque inclui a margem (spread) da casa de câmbio."],
            ["Como o {$nome} turismo é calculado aqui?", "Aplicamos um acréscimo de $sp% sobre a cotação comercial do dia. É uma estimativa: cada casa de câmbio define o próprio spread."],
            ['O valor inclui IOF?', 'Não. O IOF e eventuais taxas de entrega ou cartão pré-pago são cobrados à parte. Confirme o valor final com a instituição.'],
            ["Onde conseguir o melhor {$nome} turismo?", 'Compare ao menos três casas de câmbio e bancos, pergunte o valor final em reais com todos os custos e compre aos poucos para diluir a oscilação.'],
        ]);
        layout("$nome turismo hoje: cotação estimada R$ " . fmt($tur), "$nome turismo hoje: R$ " . fmt($tur) . " (estimativa a partir do comercial R$ " . fmt($com) . "). Calculadora e perguntas frequentes.", $m === 'usd' ? '/dolar-turismo/' : '/euro-turismo/',
            '<div class="grid">' . card("$nome turismo (estimado)", fmt($tur), 'comercial + ' . $sp . '%', '#', true) . card("$nome comercial", fmt($com), 'em ' . h($data), url($m)) . '</div>'
            . '<h2>Calculadora de ' . h($nome) . ' turismo</h2>' . calc($iso, $tur) . '<h2>Tabela: ' . h($nome) . ' turismo em reais</h2>' . tabelaValores($iso, $tur)
            . '<p class="note">Estimativa: cotação comercial multiplicada por ' . number_format(1 + spread($m), 3, ',', '') . '. Não inclui IOF nem taxas.</p>'
            . '<h2>Perguntas frequentes</h2>' . $fh . $rel,
            hero("$nome turismo hoje", 'Estimativa do valor para viajantes, calculada a partir da cotação comercial.'), $fld);
        return;
    }
    $real = $modo === 'real';
    $path = $real ? '/dolar-real/' : url($m);
    $h1 = $real ? 'Dólar para real: converta USD em BRL' : "$nome hoje";
    $title = $real ? 'Dólar para real hoje: conversor USD/BRL' : "$nome hoje: cotação em real ($iso/BRL)";
    $ant = anteriores()[$m] ?? null; $var = $ant ? ' · ' . pctSpan($com, $ant['v']) . ' vs ' . date('d/m', strtotime($ant['d'])) : '';
    $info = INFO[$m] ?? ['sobre' => [], 'faq' => []];
    $sobre = '<h2>Sobre o ' . h($nome) . '</h2>';
    foreach ($info['sobre'] as $par) $sobre .= '<p>' . $par . '</p>';
    $sobre .= '<p>Cotação de referência de 1 ' . h($iso) . ' em reais (BRL), atualizada ao longo do dia. Bancos e casas de câmbio praticam valores diferentes por causa de spread, taxas e impostos.</p>';
    $itens = [];
    foreach ($info['faq'] as [$p, $r]) $itens[] = [$p, str_replace('{v}', fmt($com), strip_tags($r))];
    if (!$itens) $itens = [["Quanto vale 1 $iso em reais hoje?", "A cotação de referência de hoje é de R$ " . fmt($com) . " por 1 $iso (atualizada em $data)."], ['De onde vêm as cotações?', 'De fontes públicas de mercado, atualizadas várias vezes ao dia. Servem como referência e não incluem spread, IOF ou taxas.']];
    [$fh, $fld] = faq($itens);
    $pts = historico($m, 30);
    $hist = '<h2>Histórico do ' . h($nome) . '</h2>' . grafico($pts, "$iso/BRL, últimos 30 dias") . histTabela($pts, 10)
          . ($m === 'usd' ? '<p><a href="/dolar-grafico/">Ver gráfico completo do dólar</a></p>' : '<p><a href="/dolar-grafico/?moeda=' . $m . '">Ver gráfico de períodos maiores</a></p>');
    layout($title, ($real ? 'Converta dólar em real' : "Cotação do $nome ($iso) hoje") . ': R$ ' . fmt($com) . ". Histórico recente e conversor $iso/BRL.", $path,
        '<div class="grid">' . card("$iso/BRL", fmt($com), 'atualizado em ' . h($data) . $var, '#') . ($m === 'usd' || $m === 'euro' ? card("$nome turismo (estimado)", fmt(turismo($m, $com)), 'veja a página de turismo', $m === 'usd' ? '/dolar-turismo/' : '/euro-turismo/', true) : '') . '</div>'
        . '<h2>Conversor ' . h($iso) . ' para real</h2>' . calc($iso, $com) . '<h2>Tabela de conversão</h2>' . tabelaValores($iso, $com) . $hist . $sobre
        . '<h2>Perguntas frequentes</h2>' . $fh . $rel,
        hero($h1, "1 $iso = R$ " . fmt($com)), $fld);
}

function pgConv(string $m, bool $inv): void {
    $c = cotacoes();
    if (!isset($c[$m])) erro(503, 'Cotação indisponível no momento');
    [$iso] = MOEDAS[$m]; $nome = nomeMoeda($m); $n = strtolower($nome); $v = $c[$m]['v']; $data = $c[$m]['data']; $b = cbase($m);
    $path = $inv ? "/real-para-$b/" : "/$b-para-real/"; $par = $inv ? "/$b-para-real/" : "/real-para-$b/";
    $ant = anteriores()[$m] ?? null; $var = $ant ? ' · ' . pctSpan($v, $ant['v']) . ' vs ' . date('d/m', strtotime($ant['d'])) : '';
    $um = $inv ? '1 real = ' . fmtq(1 / $v) . " $iso" : "1 $iso = R$ " . fmt($v);
    $ex = $inv ? 'R$ 100 = ' . number_format(100 / $v, 2, ',', '.') . " $iso" : "100 $iso = R$ " . number_format(100 * $v, 2, ',', '.');
    $itens = $inv ? [
        ["Quanto vale 1 real em $n hoje?", 'Pela cotação de referência de hoje, 1 real vale ' . fmtq(1 / $v) . " $iso."],
        ["Quantos $iso dá para comprar com 100 reais?", 'Com R$ 100 você compra cerca de ' . number_format(100 / $v, 2, ',', '.') . " $iso, sem considerar spread, IOF e taxas."],
        ["Como converter reais em $n?", "Divida o valor em reais pela cotação do $n (hoje R$ " . fmt($v) . ' por 1 ' . $iso . ') ou use a calculadora desta página.'],
        ['O valor inclui IOF e spread?', 'Não. É a cotação de referência. Bancos e casas de câmbio cobram valor maior, e o IOF e outras taxas são adicionais.'],
    ] : [
        ["Quanto vale 1 $n em reais hoje?", 'A cotação de referência de hoje é de R$ ' . fmt($v) . " por 1 $iso (atualizada em $data)."],
        ["Quanto é 100 $n em reais?", "100 $iso equivalem a R$ " . number_format(100 * $v, 2, ',', '.') . ', pela cotação de referência de hoje.'],
        ["Como converter $n para real?", "Multiplique o valor em $iso pela cotação (R$ " . fmt($v) . ') ou use a calculadora desta página.'],
        ['O valor inclui IOF e spread?', 'Não. É a cotação de referência. Bancos e casas de câmbio cobram valor maior, e o IOF e outras taxas são adicionais.'],
    ];
    [$fh, $fld] = faq($itens);
    $rel = '<h2>Veja também</h2><p><a href="' . $par . '">' . ($inv ? ucfirst($n) . ' para real' : 'Real para ' . $n) . '</a> · <a href="' . url($m) . '">' . h($nome) . ' hoje e histórico</a> · <a href="/conversor-de-moedas/">Conversor de moedas</a> · <a href="/">Todas as cotações</a></p>';
    layout($inv ? "Real para $n hoje: converter BRL em $iso" : ucfirst($n) . " para real hoje: converter $iso em BRL",
        ($inv ? "Real para $n: $um" : ucfirst($n) . " para real: $um") . '. Calculadora e tabela de valores prontos, atualizadas hoje.', $path,
        '<div class="grid">' . card($inv ? "Real para $iso" : "$iso para real", $inv ? fmtq(1 / $v) . " $iso" : fmt($v), 'atualizado em ' . h($data) . $var, '#', false, $inv ? '' : 'R$ ') . card($inv ? "$iso/BRL" : 'Real para ' . $iso, $inv ? fmt($v) : fmtq(1 / $v) . " $iso", $inv ? 'cotação do ' . h($n) : 'o caminho inverso', $inv ? url($m) : $par, true, $inv ? 'R$ ' : '') . '</div>'
        . '<h2>Calculadora: ' . ($inv ? "real para $n" : "$n para real") . '</h2>' . calc($iso, $v, $inv)
        . '<h2>Tabela de conversão</h2>' . tabelaValores($iso, $v, $inv)
        . '<h2>Como converter</h2><p>' . ($inv ? "Para saber quanto você compra em $n com seus reais, divida o valor em reais pela cotação." : "Para converter $n em reais, multiplique o valor em $iso pela cotação.") . " Exemplo: $ex. A cotação é de referência e não inclui spread, IOF ou taxas.</p>"
        . '<h2>Perguntas frequentes</h2>' . $fh . $rel,
        hero($inv ? "Real para $n" : ucfirst($n) . ' para real', $um), $fld);
}

function pgGrafico(): void {
    $c = cotacoes();
    $m = isset(MOEDAS[$_GET['moeda'] ?? '']) ? (string)$_GET['moeda'] : 'usd';
    $dias = in_array((int)($_GET['periodo'] ?? 0), [30, 90, 180, 365], true) ? (int)$_GET['periodo'] : 90;
    $nome = nomeMoeda($m); $iso = MOEDAS[$m][0];
    $pts = historico($m, $dias);
    $op = ''; foreach (MOEDAS as $n => [$i, $l]) $op .= '<option value="' . $n . '"' . ($n === $m ? ' selected' : '') . '>' . h($l) . ' (' . $i . ')</option>';
    $pe = ''; foreach ([30 => '30 dias', 90 => '3 meses', 180 => '6 meses', 365 => '1 ano'] as $d => $l) $pe .= '<option value="' . $d . '"' . ($d === $dias ? ' selected' : '') . '>' . $l . '</option>';
    $resumo = '';
    if (count($pts) > 1) {
        $vs = array_column($pts, 'v'); $f = $pts[0]['v']; $u = $pts[count($pts) - 1]['v'];
        $resumo = '<div class="grid"><div class="card"><small>Variação no período</small><b>' . pctSpan($u, $f) . '</b></div><div class="card t"><small>Máxima</small><b>R$ ' . fmt(max($vs)) . '</b></div><div class="card t"><small>Mínima</small><b>R$ ' . fmt(min($vs)) . '</b></div></div>';
    }
    $atual = isset($c[$m]) ? 'R$ ' . fmt($c[$m]['v']) : '';
    layout($m === 'usd' ? 'Gráfico do dólar hoje: evolução do USD/BRL' : "Gráfico do $nome: evolução do $iso/BRL", "Gráfico e histórico do " . ($m === 'usd' ? 'dólar' : $nome) . " em reais" . ($atual ? " (hoje $atual)" : '') . ', com máxima, mínima e variação no período.', '/dolar-grafico/',
        '<form class="f2" method="get" action="/dolar-grafico/"><label>Moeda<select name="moeda">' . $op . '</select></label><label>Período<select name="periodo">' . $pe . '</select></label><button>Ver gráfico</button></form>'
        . $resumo . grafico($pts, "$iso/BRL, últimos $dias dias") . histTabela($pts, 30)
        . '<h2>Como ler o gráfico</h2><p>A linha mostra o valor de 1 ' . h($iso) . ' em reais ao fim de cada dia registrado. Subidas indicam que a moeda estrangeira ficou mais cara em relação ao real; quedas, o contrário. Para decisões de compra, observe também a tendência do período, e não só o valor do dia.</p>'
        . '<p>Veja também: <a href="/dolar-comercial/">dólar comercial</a> · <a href="/dolar-turismo/">dólar turismo</a> · <a href="/dolar-ptax/">dólar PTAX</a> · <a href="/conversor-de-moedas/">conversor</a>.</p>',
        hero($m === 'usd' ? 'Gráfico do dólar' : "Gráfico do $nome", 'Evolução da cotação em reais ao longo do tempo.'));
}

function pgPtax(): void {
    $c = cotacoes(); $v = historico('ptax', 45); $cp = array_column(historico('ptaxc', 45), 'v', 'd');
    $cards = '';
    if ($v) {
        $u = $v[count($v) - 1]; $a = count($v) > 1 ? $v[count($v) - 2] : null;
        $cards .= card('PTAX venda', fmt($u['v']), 'em ' . date('d/m/Y', strtotime($u['d'])) . ($a ? ' · ' . pctSpan($u['v'], $a['v']) : ''), '#');
        if (isset($cp[$u['d']])) $cards .= card('PTAX compra', fmt($cp[$u['d']]), 'em ' . date('d/m/Y', strtotime($u['d'])), '#', true);
    }
    if (isset($c['usd'])) $cards .= card('Dólar comercial agora', fmt($c['usd']['v']), 'atualizado em ' . h($c['usd']['data']), '/dolar-comercial/', true);
    $tab = '';
    foreach (array_reverse($v) as $r) $tab .= '<tr><td>' . date('d/m/Y', strtotime($r['d'])) . '</td><td>R$ ' . (isset($cp[$r['d']]) ? fmt($cp[$r['d']]) : '–') . '</td><td>R$ ' . fmt($r['v']) . '</td></tr>';
    $tab = $tab ? '<h2>PTAX dos últimos dias</h2><table><thead><tr><th>Data</th><th>Compra</th><th>Venda</th></tr></thead><tbody>' . $tab . '</tbody></table>' : '<p class="note">Os valores da PTAX aparecem aqui conforme forem sendo registrados. Para a série oficial completa, consulte o site do Banco Central.</p>';
    [$fh, $fld] = faq([
        ['O que é a PTAX?', 'É a taxa de câmbio de referência do dólar calculada pelo Banco Central do Brasil. A PTAX do dia é a média de quatro taxas apuradas em janelas de consulta a instituições financeiras ao longo da manhã, por volta das 10h, 11h, 12h e 13h (horário de Brasília).'],
        ['A que horas a PTAX é divulgada?', 'Boletins parciais saem após cada janela e o de fechamento, que vale como PTAX do dia, depois da quarta janela, em torno das 13h, nos dias úteis.'],
        ['Existe PTAX em fins de semana e feriados?', 'Não. A PTAX é calculada apenas em dias úteis; nos demais, vale a do último dia útil.'],
        ['Qual usar: PTAX de compra ou de venda?', 'Depende da regra do contrato ou da obrigação. Cada operação indica qual taxa e qual data usar; confirme no contrato, com seu contador ou na norma aplicável.'],
        ['A PTAX é igual ao dólar comercial?', 'Não. O dólar comercial oscila durante todo o pregão; a PTAX é uma média calculada em horários definidos. Costumam ser próximas, mas raramente idênticas.'],
        ['Onde consultar a PTAX oficial?', 'No site do Banco Central do Brasil, na página de cotações e boletins.'],
    ]);
    layout('Dólar PTAX hoje: o que é, como é calculada e cotação', 'Dólar PTAX: veja a cotação de compra e venda, entenda como o Banco Central calcula a taxa, quando é divulgada e para que serve.', '/dolar-ptax/',
        '<div class="grid">' . $cards . '</div>' . $tab
        . '<h2>O que é a PTAX</h2><p>A PTAX é a taxa de câmbio de referência calculada pelo Banco Central do Brasil. Em quatro janelas ao longo da manhã, o BC consulta instituições financeiras autorizadas, apura as taxas de compra e de venda de cada janela e calcula a média. A PTAX do dia é a média dessas quatro apurações, e existe uma PTAX de compra e outra de venda.</p>'
        . '<h2>Para que serve</h2><p>A PTAX é usada como referência em contratos e financiamentos indexados ao dólar, na liquidação de derivativos de câmbio e em rotinas contábeis e fiscais de conversão de valores em moeda estrangeira para reais. Qual taxa (compra ou venda) e qual data aplicar depende da regra de cada operação.</p>'
        . '<h2>PTAX x dólar comercial x dólar turismo</h2><p>O <a href="/dolar-comercial/">dólar comercial</a> varia continuamente durante o pregão, a PTAX é uma média apurada em horários fixos e o <a href="/dolar-turismo/">dólar turismo</a> é o valor praticado na venda ao viajante, com spread. Por isso os três números diferem, mesmo no mesmo dia.</p>'
        . '<h2>Perguntas frequentes</h2>' . $fh
        . '<p class="note">Fonte da PTAX: Banco Central do Brasil (<a href="https://www.bcb.gov.br/estabilidadefinanceira/historicocotacoes" rel="noopener">cotações e boletins</a>). Dados com caráter informativo; para fins contratuais ou fiscais, use sempre a fonte oficial.</p>',
        hero('Dólar PTAX', 'A taxa de câmbio de referência do Banco Central, explicada.'), $fld);
}

function pgParalelo(): void {
    $c = cotacoes(); $cards = '';
    if (isset($c['usd'])) $cards = card('Dólar comercial', fmt($c['usd']['v']), 'em ' . h($c['usd']['data']), '/dolar-comercial/') . card('Dólar turismo (estimado)', fmt(turismo('usd', $c['usd']['v'])), 'comercial + ' . number_format(spread('usd') * 100, 1, ',', '') . '%', '/dolar-turismo/', true);
    [$fh, $fld] = faq([
        ['O que é o dólar paralelo?', 'É o dólar negociado fora do mercado oficial de câmbio, sem passar por bancos e casas de câmbio autorizados pelo Banco Central. A cotação é informal e varia de negociação para negociação.'],
        ['Qual a cotação do dólar paralelo hoje?', 'Não existe cotação oficial do paralelo, por isso não publicamos um valor. Como referência, use o dólar comercial e o dólar turismo estimado desta página.'],
        ['Comprar dólar no paralelo é seguro?', 'Não é recomendado. Além do risco de cédulas falsas e golpes, a operação foge do mercado regulado e não oferece comprovante nem proteção ao consumidor.'],
        ['Qual a diferença entre dólar paralelo e dólar blue?', 'O dólar blue é o nome do dólar paralelo na Argentina. No Brasil, o termo costuma se referir ao dólar informal, em um mercado onde o câmbio é livre e as operações regulares ocorrem em instituições autorizadas.'],
        ['Qual a alternativa segura para comprar dólar?', 'Instituições autorizadas pelo Banco Central: bancos, corretoras e casas de câmbio, em espécie, cartão pré-pago ou conta global. Compare o valor final em reais, com IOF e taxas.'],
    ]);
    layout('Dólar paralelo hoje: o que é e quais as alternativas seguras', 'Dólar paralelo: entenda o que é, por que não existe cotação oficial, os riscos e como comparar com o dólar comercial e o turismo.', '/dolar-paralelo/',
        '<div class="grid">' . $cards . '</div>'
        . '<h2>O que é o dólar paralelo</h2><p>Dólar paralelo é o nome dado à negociação de moeda estrangeira fora do mercado regulado, sem intermediação de instituições autorizadas pelo Banco Central. Como não há registro oficial, não existe uma cotação única: o preço depende de quem vende, de quem compra e do momento.</p>'
        . '<h2>Por que não mostramos uma cotação</h2><p>Um número "oficial" do paralelo seria apenas um palpite. Em vez disso, mostramos o <a href="/dolar-comercial/">dólar comercial</a>, que é a referência de mercado, e o <a href="/dolar-turismo/">dólar turismo estimado</a>, que reflete o custo de comprar cédulas em uma casa de câmbio, com spread.</p>'
        . '<h2>Riscos</h2><p>Quem compra fora do sistema formal assume risco de cédulas falsas, golpes e abordagens oportunistas, sem comprovante e sem recurso em caso de problema. O ganho aparente em relação ao turismo costuma não compensar o risco.</p>'
        . '<h2>Como pagar menos de forma segura</h2><p>Compare o valor final em reais em pelo menos três instituições, lembrando que o IOF e as taxas entram na conta, e considere conta global ou cartão pré-pago em moeda estrangeira. Comprar aos poucos também reduz o efeito da oscilação. Use o <a href="/conversor-de-moedas/">conversor</a> para simular valores.</p>'
        . '<h2>Perguntas frequentes</h2>' . $fh . '<p>Veja também: <a href="/dolar-ptax/">Dólar PTAX</a> · <a href="/dolar-grafico/">Gráfico do dólar</a>.</p>',
        hero('Dólar paralelo', 'O que é, por que não tem cotação oficial e quais as alternativas seguras.'), $fld);
}

function pgConversor(): void {
    $c = cotacoes(); $rt = ['BRL' => 1.0]; $op = '<option value="BRL">BRL — Real</option>';
    foreach ($c as $n => $r) { $rt[MOEDAS[$n][0]] = $r['v']; $op .= '<option value="' . MOEDAS[$n][0] . '">' . MOEDAS[$n][0] . ' — ' . h(MOEDAS[$n][1]) . '</option>'; }
    $ini = isset($c['usd']) ? fmt($c['usd']['v']) : '';
    layout('Conversor de moedas: dólar, euro e outras para real', 'Converta dólar, euro, libra e mais moedas para real (BRL) e entre si, com cotações atualizadas.', '/conversor-de-moedas/',
        '<form id="cv" class="cv" onsubmit="return false"><label>Valor<input id="v" type="number" step="any" value="1" inputmode="decimal"></label>'
        . '<label>De<select id="de">' . str_replace('value="USD"', 'value="USD" selected', $op) . '</select></label><label>Para<select id="pa">' . $op . '</select></label><output aria-live="polite">' . $ini . '</output></form>'
        . '<script id="rt" type="application/json">' . json_encode($rt) . '</script><p class="note">Conversão feita pelas cotações de referência em reais; sem spread, IOF ou taxas.</p>',
        hero('Conversor de moedas', 'Converta entre real, dólar, euro e outras moedas.'));
}

function sitemap(): void {
    header('Content-Type: application/xml; charset=utf-8'); header('Cache-Control: public, max-age=3600');
    $c = cotacoes(); $u = ['/' => 1.0, '/dolar-comercial/' => 0.9, '/dolar-turismo/' => 0.9, '/euro/' => 0.8, '/euro-turismo/' => 0.8, '/dolar-real/' => 0.7, '/dolar-grafico/' => 0.7, '/dolar-ptax/' => 0.7, '/dolar-paralelo/' => 0.6, '/conversor-de-moedas/' => 0.7];
    foreach ($c as $n => $_) if (!in_array($n, ['usd', 'euro'], true)) $u[url($n)] = 0.5;
    foreach ($c as $n => $_) { $b = cbase($n); $u["/real-para-$b/"] = 0.6; if ($n !== 'usd') $u["/$b-para-real/"] = 0.6; }
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($u as $p => $pr) echo '<url><loc>' . h(base() . $p) . '</loc><lastmod>' . date('Y-m-d') . '</lastmod><priority>' . $pr . '</priority></url>';
    echo '</urlset>';
}

/* ---------- roteamento ---------- */
try {
    $raw = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $p = trim($raw, '/');
    if (($_GET['action'] ?? '') === 'cotacoes') {
        header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: public, max-age=300');
        echo json_encode(array_map(fn($r) => ['valor' => $r['v'], 'data' => $r['data']], cotacoes())); exit;
    }
    if ($p === 'sitemap.xml') { sitemap(); exit; }
    if ($p === '') { pgHome(); exit; }
    if (isset(LEGADO[$p])) irPara(LEGADO[$p]);
    if ($p === 'fetch-cotacoes.php' || $p === 'query.php') {   // cron por URL: exige CRON_KEY (16+ caracteres) no config.php
        $k = (string)cfg('CRON_KEY');
        if (strlen($k) < 16 || !hash_equals($k, (string)($_GET['key'] ?? ''))) erro(404, 'Página não encontrada');
        header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
        set_time_limit(600); ignore_user_abort(true);
        ob_start(); $r = cron(); http_response_code($r === 0 ? 200 : 500); echo ob_get_clean(); exit;
    }
    $dir = str_ends_with($raw, '/');
    if (!$dir && str_ends_with($p, '.php')) {   // qualquer "/x.php" antigo vira a URL amigável (301)
        $b = substr($p, 0, -4);
        if (isset(MOEDAS[$b])) irPara(url($b));
        if (isset(PAGINAS[$b]) || isset(ESPECIAIS[$b]) || isset(slugs()[$b])) irPara("/$b/");
    }
    if (isset(PAGINAS[$p])) { if (!$dir) irPara("/$p/"); pgMoeda(...PAGINAS[$p]); exit; }
    if (isset(ESPECIAIS[$p])) { if (!$dir) irPara("/$p/"); (ESPECIAIS[$p])(); exit; }
    if (isset(slugs()[$p])) { if (!$dir) irPara("/$p/"); pgMoeda(slugs()[$p], 'comercial'); exit; }
    if (preg_match('/^(?:(real)-para-(.+)|(.+)-para-real)$/', $p, $mm)) {   // /dolar-canadense-para-real/ e /real-para-dolar-canadense/
        $inv = $mm[1] === 'real'; $base = $inv ? $mm[2] : $mm[3];
        if (isset(convs()[$base])) {
            if (!$dir) irPara("/$p/");
            if (!$inv && $base === 'dolar') irPara('/dolar-real/');   // já existe a página principal
            pgConv(convs()[$base], $inv); exit;
        }
    }
    erro(404, 'Página não encontrada');
} catch (PDOException $e) {
    error_log('dolarhoje: ' . $e->getMessage());
    erro(503, 'Serviço temporariamente indisponível');
}
