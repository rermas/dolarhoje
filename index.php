<?php
declare(strict_types=1);
/**
 * Dólar Hoje — arquivo único.
 *  Web: roteia, consulta o banco (PDO + prepared statements) e renderiza.
 *  CLI: `php index.php cron` atualiza as cotações (Wise) em moedas / moedahistorico.
 * Segredos ficam em config.php (fora do Git). Veja README.md.
 */
date_default_timezone_set('America/Sao_Paulo');
if (is_file(__DIR__ . '/config.php')) require __DIR__ . '/config.php';

function cfg(string $k, mixed $d = ''): mixed { return defined($k) ? constant($k) : $d; }
function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* nome no banco => [ISO, rótulo] */
const MOEDAS = [
    'usd' => ['USD', 'Dólar Americano'], 'euro' => ['EUR', 'Euro'], 'libra' => ['GBP', 'Libra Esterlina'],
    'usdaus' => ['AUD', 'Dólar Australiano'], 'usdcan' => ['CAD', 'Dólar Canadense'], 'sgd' => ['SGD', 'Dólar de Singapura'],
    'suico' => ['CHF', 'Franco Suíço'], 'china' => ['CNY', 'Yuan Chinês'], 'japao' => ['JPY', 'Iene Japonês'],
    'arg' => ['ARS', 'Peso Argentino'], 'chile' => ['CLP', 'Peso Chileno'], 'uru' => ['UYU', 'Peso Uruguaio'],
    'mxn' => ['MXN', 'Peso Mexicano'], 'cop' => ['COP', 'Peso Colombiano'], 'par' => ['PYG', 'Guarani Paraguaio'],
    'rub' => ['RUB', 'Rublo Russo'], 'inr' => ['INR', 'Rupia Indiana'], 'idr' => ['IDR', 'Rupia Indonésia'],
    'pkr' => ['PKR', 'Rupia Paquistanesa'], 'aed' => ['AED', 'Dirham dos Emirados'], 'mad' => ['MAD', 'Dirham Marroquino'],
    'qar' => ['QAR', 'Rial do Catar'], 'try' => ['TRY', 'Lira Turca'], 'hrk' => ['HRK', 'Kuna Croata'],
    'iqd' => ['IQD', 'Dinar Iraquiano'], 'dkk' => ['DKK', 'Coroa Dinamarquesa'], 'sek' => ['SEK', 'Coroa Sueca'],
];
/* URLs de diretório que já existiam no site: caminho => [moeda, modo] */
const PAGINAS = [
    'dolar-comercial' => ['usd', 'comercial'], 'dolar-real' => ['usd', 'real'], 'dolar-turismo' => ['usd', 'turismo'],
    'euro' => ['euro', 'comercial'], 'euro-turismo' => ['euro', 'turismo'],
];
/* URLs antigas sem página equivalente: caminho => destino (301). Acrescente as suas aqui. */
const LEGADO = [
    'index.php' => '/', 'conversor-de-moedas.php' => '/conversor-de-moedas/', 'bolivarvenezuelano.php' => '/',
    'euro-comercial' => '/euro/', 'dolar.php' => '/dolar-comercial/', 'euro.php' => '/euro/',
];

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
function pct(float $v): string { return ($v > 0 ? '+' : '') . number_format($v, 2, ',', '.') . '%'; }
function historico(string $m, int $n = 30): array {
    try { return q('SELECT data, valor FROM moedahistorico WHERE moeda = ? ORDER BY data DESC LIMIT ' . $n, [$m]); } catch (Throwable) { return []; }
}
function variacao(string $m): ?float {
    $r = historico($m, 2);
    return count($r) === 2 && (float)$r[1]['valor'] > 0 ? ((float)$r[0]['valor'] / (float)$r[1]['valor'] - 1) * 100 : null;
}
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
function cron(): int {
    $lk = @fopen(sys_get_temp_dir() . '/dolarhoje-cron.lock', 'c');
    if ($lk && !flock($lk, LOCK_EX | LOCK_NB)) { echo "já em execução\n"; return 0; }
    $hoje = date('d/m/Y'); $iso = date('Y-m-d'); $ok = 0; $falha = 0;
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS moedahistorico (cod INT AUTO_INCREMENT PRIMARY KEY, data DATE NOT NULL, moeda VARCHAR(10) NOT NULL, valor FLOAT NOT NULL)');
    } catch (Throwable $e) { echo 'aviso moedahistorico: ' . $e->getMessage() . "\n"; }
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
            q1('SELECT 1 AS e FROM moedahistorico WHERE data = ? AND moeda = ?', [$iso, $nome])
                ? x('UPDATE moedahistorico SET valor = ? WHERE data = ? AND moeda = ?', [$v, $iso, $nome])
                : x('INSERT INTO moedahistorico (data, moeda, valor) VALUES (?, ?, ?)', [$iso, $nome, $v]);
            echo "OK $cod = $v\n"; $ok++;
        }
        usleep(1200000);
    }
    echo "$ok atualizadas, $falha com falha\n";
    return $ok > 0 ? 0 : 1;
}

if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') === 'cron') exit(cron());
    fwrite(STDERR, "uso: php index.php cron\n"); exit(2);
}

/* ---------- render ---------- */
function base(): string { return rtrim((string)cfg('SITE_URL', 'https://www.dolarhoje.net.br'), '/'); }
function loader(string $src, string $extra = ''): string {
    return "<script>addEventListener('load',function(){var s=document.createElement('script');s.async=1;s.src='$src';document.head.appendChild(s);$extra})</script>";
}
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
 . '@media(max-width:640px){header{position:static}details.o div{position:static;box-shadow:none;width:auto}}';

function layout(string $title, string $desc, string $path, string $main, string $hero = '', string $ld = '', int $status = 200, bool $index = true): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    $url = base() . $path;
    $outros = '';
    foreach (MOEDAS as $n => [$c, $l]) if (!in_array($n, ['usd', 'euro'], true)) $outros .= '<a href="/' . $n . '.php">' . h($l) . '</a>';
    $ga = (string)cfg('GA_ID', 'UA-6425016-24'); $ad = (string)cfg('ADSENSE_ID');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . h($title) . '</title><meta name="description" content="' . h($desc) . '">'
       . ($index ? '<meta name="robots" content="index,follow,max-image-preview:large">' : '<meta name="robots" content="noindex">')
       . '<link rel="canonical" href="' . h($url) . '"><meta name="theme-color" content="#fd7e14">'
       . '<meta property="og:type" content="website"><meta property="og:locale" content="pt_BR"><meta property="og:site_name" content="Dólar Hoje">'
       . '<meta property="og:title" content="' . h($title) . '"><meta property="og:description" content="' . h($desc) . '"><meta property="og:url" content="' . h($url) . '">'
       . '<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Ccircle cx=%2716%27 cy=%2716%27 r=%2716%27 fill=%27%23fd7e14%27/%3E%3Ctext x=%2716%27 y=%2722%27 font-size=%2718%27 text-anchor=%27middle%27 fill=%27white%27 font-family=%27Arial%27 font-weight=%27bold%27%3E$%3C/text%3E%3C/svg%3E">'
       . '<style>' . CSS . '</style>' . gtagHead($ga) . ($ld ? '<script type="application/ld+json">' . $ld . '</script>' : '')
       . '</head><body><header><div class="w"><a class="logo" href="/">Dólar Hoje</a><nav aria-label="Principal">'
       . '<a href="/dolar-comercial/">Dólar</a><a href="/dolar-turismo/">Dólar Turismo</a><a href="/euro/">Euro</a><a href="/euro-turismo/">Euro Turismo</a><a href="/conversor-de-moedas/">Conversor</a>'
       . '<details class="o"><summary>+ Outros</summary><div>' . $outros . '</div></details></nav></div></header>'
       . $hero . '<main class="w">' . $main . '</main>'
       . '<footer><div class="w"><p>Cotações de referência, atualizadas várias vezes ao dia a partir de fontes públicas. Caráter informativo, sem oferta de compra ou venda. Valores de turismo são estimativas e variam entre casas de câmbio.</p><p>&copy; ' . date('Y') . ' Dólar Hoje</p></div></footer>'
       . '<script>document.querySelectorAll(".calc").forEach(function(f){var r=+f.dataset.r,a=f.querySelector("[data-k=f]"),b=f.querySelector("[data-k=b]");a.oninput=function(){b.value=(a.value*r).toFixed(2)};b.oninput=function(){a.value=(b.value/r).toFixed(4)}});'
       . 'var cv=document.getElementById("cv");if(cv){var R=JSON.parse(document.getElementById("rt").textContent),g=function(i){return cv.querySelector(i)},u=function(){var o=g("#v").value*R[g("#de").value]/R[g("#pa").value];g("output").textContent=isFinite(o)?o.toLocaleString("pt-BR",{maximumFractionDigits:o<1?6:4}):"-"};cv.oninput=u;u()}</script>'
       . (preg_match('/^ca-pub-\d+$/', $ad) ? loader("https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=$ad") : '')
       . '</body></html>';
}
function gtagHead(string $id): string {   // snippet padrão do Google (gtag.js), aceita G-, UA- ou AW-
    if (!preg_match('/^(G|UA|AW)-[A-Z0-9-]+$/', $id)) return '';
    return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . $id . '");</script>';
}
function hero(string $h1, string $p): string { return '<div class="hero"><div class="w"><h1>' . h($h1) . '</h1><p>' . h($p) . '</p></div></div>'; }
function card(string $label, string $valor, string $sub, string $href, bool $t = false): string {
    return '<a class="card' . ($t ? ' t' : '') . '" href="' . h($href) . '"><small>' . h($label) . '</small><b>R$ ' . h($valor) . '</b><small>' . $sub . '</small></a>';
}
function varhtml(?float $v): string { return $v === null ? '' : '<span class="' . ($v >= 0 ? 'up' : 'dn') . '">' . ($v >= 0 ? '▲ ' : '▼ ') . pct($v) . '</span> · '; }
function calc(string $iso, float $rate): string {
    return '<form class="calc" data-r="' . $rate . '" onsubmit="return false"><label>' . h($iso) . '<input type="number" step="any" inputmode="decimal" data-k="f" value="1"></label>'
         . '<label>Reais (BRL)<input type="number" step="any" inputmode="decimal" data-k="b" value="' . number_format($rate, 2, '.', '') . '"></label></form>';
}
function histTabela(string $m, bool $tur): string {
    $hist = historico($m, 30);
    if (!$hist) return '';
    $t = '<h2>Histórico dos últimos dias</h2><table><thead><tr><th>Data</th><th>Comercial</th>' . ($tur ? '<th>Turismo (estimado)</th>' : '') . '</tr></thead><tbody>';
    foreach ($hist as $r) {
        $v = (float)$r['valor'];
        $t .= '<tr><td>' . date('d/m/Y', strtotime((string)$r['data'])) . '</td><td>R$ ' . fmt($v) . '</td>' . ($tur ? '<td>R$ ' . fmt(turismo($m, $v)) . '</td>' : '') . '</tr>';
    }
    return $t . '</tbody></table>';
}
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

function pgHome(): void {
    $c = cotacoes();
    $cards = '';
    foreach (['usd' => ['Dólar comercial', 'Dólar turismo', '/dolar-comercial/', '/dolar-turismo/'], 'euro' => ['Euro comercial', 'Euro turismo', '/euro/', '/euro-turismo/']] as $m => [$l1, $l2, $u1, $u2]) {
        if (!isset($c[$m])) continue;
        $cards .= card($l1, fmt($c[$m]['v']), varhtml(variacao($m)) . 'em ' . h($c[$m]['data']), $u1)
                . card($l2 . ' (estimado)', fmt(turismo($m, $c[$m]['v'])), 'comercial + ' . number_format(spread($m) * 100, 1, ',', '') . '%', $u2, true);
    }
    $ind = '';
    foreach (indicadores() as [$l, $v, $ref]) $ind .= '<div class="card t"><small>' . h($l) . '</small><b>' . h($v) . '</b><small>' . h($ref) . '</small></div>';
    $linhas = '';
    foreach ($c as $n => $r) {
        $url = $n === 'usd' ? '/dolar-comercial/' : ($n === 'euro' ? '/euro/' : "/$n.php");
        $linhas .= '<tr><td><a href="' . $url . '">' . h(MOEDAS[$n][1]) . '</a></td><td>' . h(MOEDAS[$n][0]) . '</td><td>R$ ' . fmt($r['v']) . '</td></tr>';
    }
    $ld = json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Dólar Hoje', 'url' => base() . '/', 'inLanguage' => 'pt-BR'], JSON_UNESCAPED_SLASHES);
    $usd = isset($c['usd']) ? 'R$ ' . fmt($c['usd']['v']) : '';
    layout('Dólar hoje: cotação do dólar, euro e outras moedas', "Dólar comercial $usd e turismo, euro e mais " . (count($c) - 2) . ' moedas em real, com histórico, conversor e Selic, IPCA e CDI.', '/',
        '<div class="grid">' . $cards . '</div><h2>Indicadores econômicos</h2><div class="grid">' . $ind . '</div>'
        . '<h2>Todas as moedas em real</h2><table><thead><tr><th>Moeda</th><th>Código</th><th>Valor em R$</th></tr></thead><tbody>' . $linhas . '</tbody></table>'
        . '<h2>Como ler as cotações</h2><p>O <b>dólar comercial</b> é a referência do mercado, usada em operações de comércio exterior e investimentos. O <b>dólar turismo</b> é o valor cobrado de quem compra moeda em espécie ou cartão pré-pago para viajar e fica acima do comercial por causa do spread das casas de câmbio. Aqui o turismo é <a href="/dolar-turismo/">estimado a partir do comercial</a>.</p>',
        hero('Dólar hoje', 'Cotação do dólar, euro e outras moedas em real, atualizada ao longo do dia.'), $ld);
}

function pgMoeda(string $m, string $modo): void {
    $c = cotacoes();
    if (!isset($c[$m])) erro(503, 'Cotação indisponível no momento');
    [$iso, $nome] = MOEDAS[$m]; if ($m === 'usd') $nome = 'Dólar'; $com = $c[$m]['v']; $data = $c[$m]['data'];
    $rel = '<h2>Veja também</h2><p>' . ($m === 'usd' ? '<a href="/dolar-turismo/">Dólar turismo</a> · <a href="/dolar-real/">Dólar para real</a> · ' : '') . ($m === 'euro' ? '<a href="/euro-turismo/">Euro turismo</a> · ' : '') . '<a href="/conversor-de-moedas/">Conversor de moedas</a> · <a href="/">Todas as cotações</a></p>';
    if ($modo === 'turismo') {
        $tur = turismo($m, $com); $sp = number_format(spread($m) * 100, 1, ',', '');
        [$fh, $fld] = faq([
            ["Qual a diferença entre {$nome} comercial e turismo?", "O comercial é a cotação de referência do mercado. O turismo é o valor praticado na venda ao viajante, mais alto porque inclui a margem (spread) da casa de câmbio."],
            ["Como o {$nome} turismo é calculado aqui?", "Aplicamos um acréscimo de $sp% sobre a cotação comercial do dia. É uma estimativa: cada casa de câmbio define o próprio spread."],
            ['O valor inclui IOF?', 'Não. O IOF e eventuais taxas de entrega ou cartão pré-pago são cobrados à parte. Confirme o valor final com a instituição.'],
            ["Onde conseguir o melhor {$nome} turismo?", 'Compare ao menos três casas de câmbio e bancos, pergunte o valor final em reais com todos os custos e compre aos poucos para diluir a oscilação.'],
        ]);
        layout("$nome turismo hoje: cotação estimada R$ " . fmt($tur), "$nome turismo hoje: R$ " . fmt($tur) . " (estimativa a partir do comercial R$ " . fmt($com) . "). Calculadora e perguntas frequentes.", $m === 'usd' ? '/dolar-turismo/' : '/euro-turismo/',
            '<div class="grid">' . card("$nome turismo (estimado)", fmt($tur), 'comercial + ' . $sp . '%', '#', true) . card("$nome comercial", fmt($com), varhtml(variacao($m)) . 'em ' . h($data), $m === 'usd' ? '/dolar-comercial/' : '/euro/') . '</div>'
            . '<h2>Calculadora de ' . h($nome) . ' turismo</h2>' . calc($iso, $tur)
            . '<p class="note">Estimativa: cotação comercial multiplicada por ' . number_format(1 + spread($m), 3, ',', '') . '. Não inclui IOF nem taxas.</p>'
            . histTabela($m, true) . '<h2>Perguntas frequentes</h2>' . $fh . $rel,
            hero("$nome turismo hoje", 'Estimativa do valor para viajantes, calculada a partir da cotação comercial.'), $fld);
        return;
    }
    $real = $modo === 'real';
    $path = $m === 'usd' ? ($real ? '/dolar-real/' : '/dolar-comercial/') : ($m === 'euro' ? '/euro/' : "/$m.php");
    $h1 = $real ? 'Dólar para real: converta USD em BRL' : "$nome hoje";
    $title = $real ? 'Dólar para real hoje: conversor USD/BRL' : "$nome hoje: cotação em real ($iso/BRL)";
    layout($title, ($real ? 'Converta dólar em real' : "Cotação do $nome ($iso) hoje") . ': R$ ' . fmt($com) . ". Histórico recente e conversor $iso/BRL.", $path,
        '<div class="grid">' . card("$iso/BRL", fmt($com), varhtml(variacao($m)) . 'atualizado em ' . h($data), '#') . ($m === 'usd' || $m === 'euro' ? card("$nome turismo (estimado)", fmt(turismo($m, $com)), 'veja a página de turismo', $m === 'usd' ? '/dolar-turismo/' : '/euro-turismo/', true) : '') . '</div>'
        . '<h2>Conversor ' . h($iso) . ' para real</h2>' . calc($iso, $com) . histTabela($m, false)
        . '<h2>Sobre o ' . h($nome) . '</h2><p>Cotação de referência de 1 ' . h($iso) . ' em reais (BRL). Os valores são atualizados ao longo do dia e servem como referência; a cotação praticada em bancos e casas de câmbio é diferente por causa de spread, taxas e impostos.</p>' . $rel,
        hero($h1, "1 $iso = R$ " . fmt($com)));
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
    $c = cotacoes(); $u = ['/' => 1.0, '/dolar-comercial/' => 0.9, '/dolar-turismo/' => 0.9, '/euro/' => 0.8, '/euro-turismo/' => 0.8, '/dolar-real/' => 0.7, '/conversor-de-moedas/' => 0.7];
    foreach ($c as $n => $_) if (!in_array($n, ['usd', 'euro'], true)) $u["/$n.php"] = 0.5;
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
    if (isset(PAGINAS[$p])) { if (!$dir) irPara("/$p/"); pgMoeda(...PAGINAS[$p]); exit; }
    if ($p === 'conversor-de-moedas') { if (!$dir) irPara('/conversor-de-moedas/'); pgConversor(); exit; }
    if (!$dir && preg_match('/^([a-z]+)\.php$/', $p, $m) && isset(MOEDAS[$m[1]])) {
        if ($m[1] === 'usd') irPara('/dolar-comercial/');
        if ($m[1] === 'euro') irPara('/euro/');
        pgMoeda($m[1], 'comercial'); exit;
    }
    erro(404, 'Página não encontrada');
} catch (PDOException $e) {
    error_log('dolarhoje: ' . $e->getMessage());
    erro(503, 'Serviço temporariamente indisponível');
}
