<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = '/' . trim($path, '/');

// Tệp tĩnh khi chạy bằng `php -S` (Apache tự phục vụ public/assets).
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

$perPage = 24;

switch (true) {
    case $path === '/':
        $hot = db()->query('SELECT * FROM products WHERE hot = 1 ORDER BY clicks DESC, id DESC LIMIT 10')->fetchAll();
        $latest = db()->query("SELECT * FROM products ORDER BY id DESC LIMIT $perPage")->fetchAll();
        render('home', ['hot' => $hot, 'items' => $latest, 'title' => t('latest')]);
        break;

    case $path === '/tim' || $path === '/search':
        $q = trim((string) ($_GET['q'] ?? ''));
        $st = db()->prepare('SELECT * FROM products WHERE name LIKE :q OR category LIKE :q OR description LIKE :q ORDER BY hot DESC, clicks DESC LIMIT 60');
        $st->execute([':q' => '%' . $q . '%']);
        render('list', ['items' => $st->fetchAll(), 'heading' => t('results') . ' “' . $q . '”', 'q' => $q, 'noindex' => true]);
        break;

    case (bool) preg_match('~^/c/(.+)$~', $path, $m):
        $cat = $m[1];
        $st = db()->prepare('SELECT * FROM products WHERE category = ? ORDER BY hot DESC, clicks DESC, id DESC');
        $st->execute([$cat]);
        $items = $st->fetchAll();
        if (!$items) {
            http_response_code(404);
        }
        render('list', ['items' => $items, 'heading' => $cat, 'activeCat' => $cat, 'pageTitle' => $cat]);
        break;

    case (bool) preg_match('~^/p/([a-z0-9-]+)$~', $path, $m):
        $st = db()->prepare('SELECT * FROM products WHERE slug = ?');
        $st->execute([$m[1]]);
        $p = $st->fetch();
        if (!$p) {
            http_response_code(404);
            render('list', ['items' => [], 'heading' => '404']);
            break;
        }
        $rel = db()->prepare('SELECT * FROM products WHERE category = ? AND id != ? ORDER BY RANDOM() LIMIT 4');
        $rel->execute([$p['category'], $p['id']]);
        render('product', ['p' => $p, 'related' => $rel->fetchAll(), 'pageTitle' => $p['name']]);
        break;

    case (bool) preg_match('~^/go/(\d+)$~', $path, $m):
        $st = db()->prepare('SELECT * FROM products WHERE id = ?');
        $st->execute([(int) $m[1]]);
        $p = $st->fetch();
        if (!$p) {
            redirect('/');
        }
        $bot = preg_match('/bot|crawl|spider|preview/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
        if (!$bot) {
            db()->prepare('UPDATE products SET clicks = clicks + 1 WHERE id = ?')->execute([$p['id']]);
        }
        header('X-Robots-Tag: noindex, nofollow');
        redirect(affiliate_link($p));

    case $path === '/sitemap.xml':
        header('Content-Type: application/xml; charset=utf-8');
        $b = base_url();
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo '<url><loc>' . e($b) . '/</loc></url>';
        foreach (categories() as $c) {
            echo '<url><loc>' . e($b . '/c/' . rawurlencode($c['category'])) . '</loc></url>';
        }
        foreach (db()->query('SELECT slug FROM products') as $r) {
            echo '<url><loc>' . e($b . '/p/' . $r['slug']) . '</loc></url>';
        }
        echo '</urlset>';
        break;

    case $path === '/robots.txt':
        header('Content-Type: text/plain');
        echo "User-agent: *\nDisallow: /go/\nDisallow: /admin\nSitemap: " . base_url() . "/sitemap.xml\n";
        break;

    case str_starts_with($path, '/admin'):
        require ROOT . '/src/admin.php';
        break;

    default:
        http_response_code(404);
        render('list', ['items' => [], 'heading' => '404']);
}
