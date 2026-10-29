<?php
/*
 * Trang quản trị /admin — đăng nhập, sản phẩm, nhập CSV, cài đặt.
 * Lần đầu chạy (chưa có ADMIN_PASSWORD) sẽ hiện màn tạo mật khẩu.
 */
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow');
$action = trim(substr($path, strlen('/admin')), '/');
$msg = '';

$envPass = (string) getenv('ADMIN_PASSWORD');
$hasPass = $envPass !== '' || setting('admin_hash') !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

/* --- lần đầu: tạo mật khẩu --- */
if (!$hasPass) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strlen((string) ($_POST['pass'] ?? '')) >= 8) {
        save_setting('admin_hash', password_hash((string) $_POST['pass'], PASSWORD_DEFAULT));
        if (!empty($_POST['site_name'])) {
            save_setting('site_name', trim((string) $_POST['site_name']));
        }
        $_SESSION['admin'] = true;
        redirect('/admin');
    }
    admin_page('Cài đặt lần đầu', function () { ?>
        <form method="post" class="panel">
          <h1>Chào mừng 👋</h1>
          <p>Đặt tên web và mật khẩu quản trị (ít nhất 8 ký tự). Làm ngay sau khi cài để không ai khác vào trước.</p>
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <label>Tên web<input name="site_name" placeholder="Ví dụ: Deal Mẹ Bỉm"></label>
          <label>Mật khẩu<input name="pass" type="password" minlength="8" required></label>
          <button class="btn">Bắt đầu</button>
        </form>
    <?php });
    return;
}

/* --- đăng nhập --- */
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/');
}
if (!is_admin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
        $pass = (string) $_POST['pass'];
        $ok = $envPass !== '' ? hash_equals($envPass, $pass) : password_verify($pass, setting('admin_hash'));
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('/admin');
        }
        usleep(800000);
        $msg = 'Sai mật khẩu.';
    }
    admin_page('Đăng nhập', function () use ($msg) { ?>
        <form method="post" class="panel">
          <h1>Quản trị</h1>
          <?php if ($msg): ?><p class="alert"><?= e($msg) ?></p><?php endif; ?>
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <label>Mật khẩu<input name="pass" type="password" required autofocus></label>
          <button class="btn">Vào</button>
        </form>
    <?php });
    return;
}

/* --- xử lý thao tác --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = (string) ($_POST['op'] ?? '');
    if ($op === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = product_row($_POST + ['id' => $id]);
        if ($row[':name'] === '' || $row[':link'] === '') {
            $msg = 'Cần tên và link sản phẩm (bắt đầu bằng https://).';
        } elseif ($id) {
            $row[':id'] = $id;
            db()->prepare('UPDATE products SET slug=:slug,name=:name,category=:category,price=:price,old_price=:old_price,rating=:rating,sold=:sold,image=:image,emoji=:emoji,link=:link,shop=:shop,description=:description,hot=:hot WHERE id=:id')->execute($row);
            $msg = 'Đã lưu.';
        } else {
            db()->prepare('INSERT INTO products (slug,name,category,price,old_price,rating,sold,image,emoji,link,shop,description,hot) VALUES (:slug,:name,:category,:price,:old_price,:rating,:sold,:image,:emoji,:link,:shop,:description,:hot)')->execute($row);
            $msg = 'Đã thêm sản phẩm.';
        }
    } elseif ($op === 'delete') {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $_POST['id']]);
        $msg = 'Đã xoá.';
    } elseif ($op === 'clear_demo') {
        db()->exec('DELETE FROM products');
        $msg = 'Đã xoá toàn bộ sản phẩm mẫu.';
    } elseif ($op === 'import' && !empty($_FILES['csv']['tmp_name'])) {
        $n = import_csv((string) $_FILES['csv']['tmp_name']);
        $msg = "Đã nhập $n sản phẩm.";
    } elseif ($op === 'settings') {
        foreach (['site_name', 'site_tagline', 'brand_color', 'site_lang', 'affiliate_template', 'sub_id', 'site_url', 'head_code'] as $k) {
            save_setting($k, trim((string) ($_POST[$k] ?? '')));
        }
        redirect('/admin/settings?ok=1');
    }
}

if ($action === 'settings') {
    admin_page('Cài đặt', function () { ?>
        <form method="post" class="panel wide">
          <h1>Cài đặt</h1>
          <?php if (isset($_GET['ok'])): ?><p class="ok">Đã lưu cài đặt.</p><?php endif; ?>
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="op" value="settings">
          <label>Tên web<input name="site_name" value="<?= e(setting('site_name')) ?>"></label>
          <label>Khẩu hiệu<input name="site_tagline" value="<?= e(setting('site_tagline')) ?>"></label>
          <label>Màu thương hiệu<input name="brand_color" type="color" value="<?= e(setting('brand_color', '#ee4d2d')) ?>"></label>
          <label>Ngôn ngữ
            <select name="site_lang"><option value="vi">Tiếng Việt</option><option value="en"<?= lang() === 'en' ? ' selected' : '' ?>>English</option></select></label>
          <label>Mẫu link affiliate của bạn
            <input name="affiliate_template" value="<?= e(setting('affiliate_template')) ?>" placeholder="https://s.shopee.vn/an_redir?origin_link={url}&affiliate_id=MÃ_CỦA_BẠN&sub_id={sub}">
            <small>{url} = link sản phẩm đã mã hoá, {raw} = link gốc, {sub} = sub_id. Để trống nếu link sản phẩm đã là link affiliate.</small></label>
          <label>sub_id<input name="sub_id" value="<?= e(setting('sub_id')) ?>" placeholder="ten-web"></label>
          <label>Địa chỉ web (để sitemap đúng tên miền)<input name="site_url" value="<?= e(setting('site_url')) ?>" placeholder="https://tenmien.vn"></label>
          <label>Mã gắn vào &lt;head&gt; (Google Analytics, Pixel…)<textarea name="head_code" rows="4"><?= e(setting('head_code')) ?></textarea></label>
          <button class="btn">Lưu</button>
        </form>
    <?php });
    return;
}

$edit = null;
if (preg_match('~^edit/(\d+)$~', $action, $m)) {
    $st = db()->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([(int) $m[1]]);
    $edit = $st->fetch() ?: null;
}
$items = db()->query('SELECT * FROM products ORDER BY id DESC LIMIT 500')->fetchAll();
$clicks = (int) db()->query('SELECT SUM(clicks) FROM products')->fetchColumn();

admin_page('Sản phẩm', function () use ($items, $edit, $msg, $clicks) {
    $f = $edit ?? ['id' => 0, 'name' => '', 'category' => '', 'price' => '', 'old_price' => '', 'rating' => '', 'sold' => '', 'image' => '', 'emoji' => '', 'link' => '', 'shop' => 'Shopee', 'description' => '', 'hot' => 0]; ?>
    <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
    <div class="stats">
      <div><b><?= count($items) ?></b><span>sản phẩm</span></div>
      <div><b><?= number_format($clicks, 0, ',', '.') ?></b><span>lượt bấm mua</span></div>
      <div><b><?= setting('affiliate_template') ? '✅' : '⚠️' ?></b><span><?= setting('affiliate_template') ? 'đã gắn mã affiliate' : '<a href="/admin/settings">chưa gắn mã affiliate</a>' ?></span></div>
    </div>

    <div class="admin-cols">
      <form method="post" class="panel">
        <h2><?= $edit ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ?></h2>
        <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="op" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
        <label>Tên *<input name="name" value="<?= e((string) $f['name']) ?>" required></label>
        <label>Link sản phẩm *<input name="link" value="<?= e((string) $f['link']) ?>" placeholder="https://shopee.vn/..." required></label>
        <div class="row2">
          <label>Giá<input name="price" inputmode="numeric" value="<?= e((string) $f['price']) ?>"></label>
          <label>Giá gốc<input name="old_price" inputmode="numeric" value="<?= e((string) $f['old_price']) ?>"></label>
        </div>
        <div class="row2">
          <label>Danh mục<input name="category" value="<?= e((string) $f['category']) ?>"></label>
          <label>Sàn<input name="shop" value="<?= e((string) $f['shop']) ?>"></label>
        </div>
        <div class="row2">
          <label>Sao (0–5)<input name="rating" value="<?= e((string) $f['rating']) ?>"></label>
          <label>Đã bán<input name="sold" inputmode="numeric" value="<?= e((string) $f['sold']) ?>"></label>
        </div>
        <label>Link ảnh<input name="image" value="<?= e((string) $f['image']) ?>" placeholder="https://..."></label>
        <label>Biểu tượng (khi chưa có ảnh)<input name="emoji" value="<?= e((string) $f['emoji']) ?>" placeholder="🎧"></label>
        <label>Mô tả<textarea name="description" rows="4"><?= e((string) $f['description']) ?></textarea></label>
        <label class="check"><input type="checkbox" name="hot" value="1"<?= $f['hot'] ? ' checked' : '' ?>> Deal nóng (lên đầu trang chủ)</label>
        <button class="btn"><?= $edit ? 'Lưu thay đổi' : 'Thêm' ?></button>
        <?php if ($edit): ?><a href="/admin" class="link">Huỷ</a><?php endif; ?>
      </form>

      <div>
        <form method="post" enctype="multipart/form-data" class="panel">
          <h2>Nhập hàng loạt (CSV)</h2>
          <p class="hint">Cột: <code>name,link,price,old_price,category,image,rating,sold,shop,description,hot</code>. Dòng đầu là tên cột.</p>
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="op" value="import">
          <input type="file" name="csv" accept=".csv,text/csv" required>
          <button class="btn">Nhập</button>
        </form>
        <div class="panel pro">
          <h2>⚡ Bản Pro</h2>
          <p>Máy tự lấy hàng nghìn sản phẩm Shopee đúng ngách mỗi đêm, nhiều giao diện, bài viết SEO, nhiều sàn (Lazada, TikTok Shop…).</p>
          <a class="btn" href="<?= APP_PRO ?>" target="_blank" rel="noopener">Xem bản Pro</a>
        </div>
        <form method="post" class="panel" onsubmit="return confirm('Xoá toàn bộ sản phẩm?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="op" value="clear_demo">
          <button class="btn btn-ghost">Xoá toàn bộ sản phẩm (kể cả hàng mẫu)</button>
        </form>
      </div>
    </div>

    <div class="panel wide">
      <h2>Danh sách</h2>
      <table class="tbl">
        <thead><tr><th>Sản phẩm</th><th>Giá</th><th>Bấm</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $p): ?>
          <tr>
            <td><a href="/p/<?= e($p['slug']) ?>" target="_blank"><?= e($p['name']) ?></a><?= $p['hot'] ? ' 🔥' : '' ?><br><small><?= e($p['category']) ?></small></td>
            <td><?= money((int) $p['price']) ?></td>
            <td><?= (int) $p['clicks'] ?></td>
            <td class="act">
              <a href="/admin/edit/<?= (int) $p['id'] ?>">Sửa</a>
              <form method="post" onsubmit="return confirm('Xoá?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button>Xoá</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php });

/* ---------- hàm riêng của trang quản trị ---------- */

function import_csv(string $file): int
{
    $fh = fopen($file, 'r');
    if (!$fh) {
        return 0;
    }
    $first = (string) fgets($fh);
    $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
    $sep = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
    $head = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv((string) $first, $sep));
    $st = db()->prepare('INSERT INTO products (slug,name,category,price,old_price,rating,sold,image,emoji,link,shop,description,hot) VALUES (:slug,:name,:category,:price,:old_price,:rating,:sold,:image,:emoji,:link,:shop,:description,:hot)');
    $n = 0;
    db()->beginTransaction();
    while (($cols = fgetcsv($fh, 0, $sep)) !== false) {
        if (count($cols) < 2) {
            continue;
        }
        $row = product_row(array_combine($head, array_pad(array_slice($cols, 0, count($head)), count($head), '')));
        if ($row[':name'] !== '' && $row[':link'] !== '') {
            $st->execute($row);
            $n++;
        }
    }
    db()->commit();
    fclose($fh);
    return $n;
}

function admin_page(string $title, callable $body): void
{ ?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Quản trị</title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/app.css?v=<?= APP_VERSION ?>">
<style>:root{--brand:<?= e(setting('brand_color', '#ee4d2d')) ?>}</style></head>
<body class="admin">
<header class="top"><div class="wrap top-row">
  <a class="logo" href="/admin"><span class="logo-mark">⚙️</span><span><?= e(setting('site_name', 'Deal Hay')) ?></span></a>
  <?php if (is_admin()): ?><nav class="admin-nav"><a href="/admin">Sản phẩm</a><a href="/admin/settings">Cài đặt</a><a href="/" target="_blank">Xem web</a><a href="/admin/logout">Thoát</a></nav><?php endif; ?>
</div></header>
<main class="wrap"><?php $body(); ?></main>
<footer class="foot"><div class="wrap"><p class="credit">VN Affiliate Shop <?= APP_VERSION ?> · <a href="<?= APP_REPO ?>">GitHub</a></p></div></footer>
</body></html>
<?php }
