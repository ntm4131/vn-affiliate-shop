<?php
declare(strict_types=1);

/*
 * VN Affiliate Shop — lõi ứng dụng (PHP 8 + SQLite, không cần framework).
 * Mọi cấu hình đọc từ biến môi trường hoặc bảng settings trong CSDL.
 */

const APP_VERSION = '1.0.0';
const APP_REPO    = 'https://github.com/ntm4131/vn-affiliate-shop';
const APP_PRO     = 'https://sitetot.com';

define('ROOT', dirname(__DIR__));
define('DATA_DIR', rtrim(getenv('DATA_DIR') ?: ROOT . '/data', '/'));

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0775, true);
}

session_name('vnas');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $pdo = new PDO('sqlite:' . DATA_DIR . '/shop.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE,
        name TEXT NOT NULL,
        category TEXT DEFAULT "",
        price INTEGER DEFAULT 0,
        old_price INTEGER DEFAULT 0,
        rating REAL DEFAULT 0,
        sold INTEGER DEFAULT 0,
        image TEXT DEFAULT "",
        emoji TEXT DEFAULT "",
        link TEXT NOT NULL,
        shop TEXT DEFAULT "Shopee",
        description TEXT DEFAULT "",
        hot INTEGER DEFAULT 0,
        clicks INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $seeded = $pdo->query("SELECT v FROM settings WHERE k = 'seeded'")->fetchColumn();
    if ($count === 0 && !$seeded) {
        seed($pdo);
    }
}

function seed(PDO $pdo): void
{
    $file = ROOT . '/data-mau/products.json';
    if (!is_file($file)) {
        return;
    }
    $items = json_decode((string) file_get_contents($file), true) ?: [];
    $st = $pdo->prepare('INSERT INTO products (slug,name,category,price,old_price,rating,sold,image,emoji,link,shop,description,hot)
        VALUES (:slug,:name,:category,:price,:old_price,:rating,:sold,:image,:emoji,:link,:shop,:description,:hot)');
    foreach ($items as $it) {
        $st->execute(product_row($it));
    }
    $pdo->exec("INSERT OR REPLACE INTO settings (k,v) VALUES ('seeded','1')");
}

function product_row(array $it): array
{
    $name = trim((string) ($it['name'] ?? ''));
    return [
        ':slug'        => unique_slug($it['slug'] ?? $name, (int) ($it['id'] ?? 0)),
        ':name'        => $name,
        ':category'    => trim((string) ($it['category'] ?? '')),
        ':price'       => (int) preg_replace('/\D/', '', (string) ($it['price'] ?? 0)),
        ':old_price'   => (int) preg_replace('/\D/', '', (string) ($it['old_price'] ?? 0)),
        ':rating'      => min(5, max(0, (float) str_replace(',', '.', (string) ($it['rating'] ?? 0)))),
        ':sold'        => (int) preg_replace('/\D/', '', (string) ($it['sold'] ?? 0)),
        ':image'       => safe_url((string) ($it['image'] ?? '')),
        ':emoji'       => mb_substr((string) ($it['emoji'] ?? ''), 0, 4),
        ':link'        => safe_url((string) ($it['link'] ?? '')),
        ':shop'        => trim((string) ($it['shop'] ?? 'Shopee')) ?: 'Shopee',
        ':description' => trim((string) ($it['description'] ?? '')),
        ':hot'         => !empty($it['hot']) ? 1 : 0,
    ];
}

/* ---------- cài đặt ---------- */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT k, v FROM settings') as $r) {
            $cache[$r['k']] = (string) $r['v'];
        }
    }
    if (isset($cache[$key]) && $cache[$key] !== '') {
        return $cache[$key];
    }
    $env = getenv(strtoupper($key));
    return $env !== false && $env !== '' ? $env : $default;
}

function save_setting(string $key, string $value): void
{
    db()->prepare('INSERT OR REPLACE INTO settings (k, v) VALUES (?, ?)')->execute([$key, $value]);
}

function lang(): string
{
    return setting('site_lang', 'vi') === 'en' ? 'en' : 'vi';
}

function t(string $key): string
{
    static $dict = null;
    $dict ??= require ROOT . '/src/lang.php';
    return $dict[lang()][$key] ?? $dict['vi'][$key] ?? $key;
}

/* ---------- tiện ích ---------- */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int $v): string
{
    if ($v <= 0) {
        return '';
    }
    if (lang() === 'en' && setting('currency', 'VND') === 'USD') {
        return '$' . number_format($v / 100, 2);
    }
    return number_format($v, 0, ',', '.') . 'đ';
}

function slugify(string $s): string
{
    $map = [
        'à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ' => 'a', 'è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ' => 'e',
        'ì|í|ị|ỉ|ĩ' => 'i', 'ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ' => 'o',
        'ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ' => 'u', 'ỳ|ý|ỵ|ỷ|ỹ' => 'y', 'đ' => 'd',
    ];
    $s = mb_strtolower($s, 'UTF-8');
    foreach ($map as $from => $to) {
        $s = (string) preg_replace('/' . $from . '/u', $to, $s);
    }
    $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'san-pham';
}

function unique_slug(string $base, int $ignoreId = 0): string
{
    $slug = slugify($base);
    $try = $slug;
    $i = 2;
    $st = db_raw()->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
    while (true) {
        $st->execute([$try, $ignoreId]);
        if (!$st->fetchColumn()) {
            return $try;
        }
        $try = $slug . '-' . $i++;
    }
}

/** PDO dùng được ngay cả khi đang migrate (tránh gọi đệ quy db()). */
function db_raw(): PDO
{
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('sqlite:' . DATA_DIR . '/shop.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

function safe_url(string $u): string
{
    $u = trim($u);
    return preg_match('~^https?://~i', $u) ? $u : '';
}

function base_url(): string
{
    $fixed = setting('site_url');
    if ($fixed) {
        return rtrim($fixed, '/');
    }
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** Gắn mã affiliate của chủ web vào link gốc theo mẫu AFFILIATE_TEMPLATE, ví dụ:
 *  https://s.shopee.vn/an_redir?origin_link={url}&affiliate_id=XXXX&sub_id={sub}  */
function affiliate_link(array $p): string
{
    $tpl = setting('affiliate_template');
    if (!$tpl || str_contains($p['link'], 'affiliate_id=') || str_contains($p['link'], 's.shopee.vn/')) {
        return $p['link'];
    }
    return strtr($tpl, [
        '{url}' => rawurlencode($p['link']),
        '{raw}' => $p['link'],
        '{sub}' => rawurlencode(setting('sub_id', 'vnas')),
    ]);
}

function discount(array $p): int
{
    return ($p['old_price'] > $p['price'] && $p['price'] > 0)
        ? (int) round(100 - $p['price'] * 100 / $p['old_price'])
        : 0;
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('CSRF');
    }
}

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function admin_hash(): string
{
    $env = getenv('ADMIN_PASSWORD');
    if ($env) {
        return password_hash($env, PASSWORD_DEFAULT);
    }
    return setting('admin_hash');
}

function categories(): array
{
    return db()->query("SELECT category, COUNT(*) n FROM products WHERE category != '' GROUP BY category ORDER BY n DESC")->fetchAll();
}

function render(string $view, array $vars = []): void
{
    extract($vars);
    ob_start();
    require ROOT . '/src/views/' . $view . '.php';
    $content = ob_get_clean();
    require ROOT . '/src/views/layout.php';
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}
