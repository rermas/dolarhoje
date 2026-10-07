<?php
declare(strict_types=1);
/**
 * Webhook de deploy: o GitHub avisa a cada push e este arquivo atualiza o site.
 * Fluxo: valida a assinatura -> git fetch/reset no clone do servidor -> copia os arquivos para o site.
 * Configuração (somente no servidor): /deploy-secret.php (ou config.php), com define() das constantes:
 *   define('DEPLOY_WEBHOOK_SECRET', 'texto-aleatorio-com-16+-caracteres'); // o mesmo "Secret" do webhook no GitHub (DEPLOY_SECRET também vale)
 *   define('DEPLOY_REPO',   '/home/simul637/repositories/dolarhoje'); // pasta do clone (cPanel > Git Version Control)
 *   define('DEPLOY_BRANCH', 'main');                                  // opcional
 *   define('DEPLOY_PATH',   '/home/simul637/public_html');            // opcional; padrão = pasta deste arquivo
 */
foreach (['deploy-secret.php', 'config.php'] as $f) if (is_file(__DIR__ . "/$f")) require_once __DIR__ . "/$f";

const ARQUIVOS = ['index.php', 'deploy-webhook.php', 'robots.txt', '.htaccess'];

function resp(int $code, string $msg): never {
    http_response_code($code); header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); echo $msg . "\n"; exit;
}
function cfgd(string $k, string $d = ''): string { return defined($k) ? (string)constant($k) : $d; }
function git(string $repo, array $args, ?string &$out = null): int {
    $p = proc_open(['git', '-C', $repo, ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes,
        null, ['HOME' => getenv('HOME') ?: dirname($repo), 'PATH' => '/usr/local/bin:/usr/bin:/bin', 'GIT_TERMINAL_PROMPT' => '0']);
    if (!is_resource($p)) { $out = 'não foi possível executar o git'; return 1; }
    $out = trim(stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
    return proc_close($p);
}

$secret = cfgd('DEPLOY_WEBHOOK_SECRET') ?: cfgd('DEPLOY_SECRET'); $repo = rtrim(cfgd('DEPLOY_REPO'), '/'); $branch = cfgd('DEPLOY_BRANCH', 'main'); $dest = rtrim(cfgd('DEPLOY_PATH', __DIR__), '/');
if (strlen($secret) < 16 || $repo === '') resp(404, 'Not found');                 // desligado até configurar
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') resp(405, 'Use POST');

$body = (string)file_get_contents('php://input');
$sig = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if (!hash_equals('sha256=' . hash_hmac('sha256', $body, $secret), $sig)) resp(403, 'Assinatura inválida');

$evento = (string)($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '');
if ($evento === 'ping') resp(200, 'pong');
if ($evento !== 'push') resp(202, "evento ignorado: $evento");
$j = json_decode($body, true);
if (!is_array($j) && isset($_POST['payload'])) $j = json_decode((string)$_POST['payload'], true);   // webhook configurado como application/x-www-form-urlencoded
if (($j['ref'] ?? '') !== "refs/heads/$branch") resp(202, 'branch ignorada: ' . ($j['ref'] ?? '?'));

$lk = fopen(sys_get_temp_dir() . '/dolarhoje-deploy.lock', 'c');
if (!$lk || !flock($lk, LOCK_EX | LOCK_NB)) resp(409, 'deploy em andamento');

$log = [];
foreach ([['fetch', '--prune', 'origin', $branch], ['reset', '--hard', "origin/$branch"]] as $a) {
    $rc = git($repo, $a, $o); $log[] = '$ git ' . implode(' ', $a) . ($o !== '' ? "\n$o" : '');
    if ($rc !== 0) { error_log('deploy-webhook: ' . implode(' | ', $log)); resp(500, implode("\n", $log) . "\nFALHOU (código $rc)"); }
}
foreach (ARQUIVOS as $f) {
    $src = "$repo/$f";
    if (!is_file($src)) { $log[] = "pulado (não existe no repositório): $f"; continue; }
    $tmp = "$dest/.$f.new";
    if (!copy($src, $tmp) || !rename($tmp, "$dest/$f")) { @unlink($tmp); error_log("deploy-webhook: falha ao copiar $f"); resp(500, implode("\n", $log) . "\nFALHOU ao copiar $f"); }
    $log[] = "copiado: $f";
}
@mkdir("$dest/posts", 0755, true);   // artigos do blog
foreach (glob("$repo/posts/*.php") ?: [] as $src) {
    $f = 'posts/' . basename($src); $tmp = "$dest/.new-" . basename($src);
    if (!copy($src, $tmp) || !rename($tmp, "$dest/$f")) { @unlink($tmp); resp(500, implode("\n", $log) . "\nFALHOU ao copiar $f"); }
    $log[] = "copiado: $f";
}
if (function_exists('opcache_reset')) @opcache_reset();
$git = ''; git($repo, ['log', '-1', '--format=%h %s'], $git);
resp(200, implode("\n", $log) . "\nOK: $git");
