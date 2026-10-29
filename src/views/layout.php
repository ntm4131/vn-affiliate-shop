<?php
$siteName  = setting('site_name', 'Deal Hay');
$tagline   = setting('site_tagline', t('tagline'));
$brand     = preg_match('/^#[0-9a-f]{6}$/i', setting('brand_color', '#ee4d2d')) ? setting('brand_color', '#ee4d2d') : '#ee4d2d';
$docTitle  = isset($pageTitle) ? $pageTitle . ' · ' . $siteName : $siteName . ' – ' . $tagline;
$desc      = $metaDesc ?? $tagline;
$canonical = base_url() . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$cats      = categories();
$q         = $q ?? '';
?><!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(mb_strimwidth($docTitle, 0, 65, '…')) ?></title>
<meta name="description" content="<?= e(mb_strimwidth($desc, 0, 158, '…')) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<meta property="og:title" content="<?= e($docTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="website">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="theme-color" content="<?= e($brand) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
<link rel="stylesheet" href="/assets/app.css?v=<?= APP_VERSION ?>">
<style>:root{--brand:<?= e($brand) ?>}</style>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'WebSite', 'name' => $siteName, 'url' => base_url() . '/',
         'potentialAction' => ['@type' => 'SearchAction', 'target' => base_url() . '/tim?q={q}', 'query-input' => 'required name=q']],
        ['@type' => 'Organization', 'name' => $siteName, 'url' => base_url() . '/'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?= setting('head_code') /* mã đo GA, Facebook Pixel… do chủ web dán trong /admin */ ?>
</head>
<body>
<header class="top">
  <div class="wrap top-row">
    <a class="logo" href="/"><span class="logo-mark">🛍️</span><span><?= e($siteName) ?></span></a>
    <form class="search" action="/tim" role="search">
      <input name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search')) ?>" aria-label="<?= e(t('search')) ?>">
      <button aria-label="Tìm">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.3 12.9l5.4 5.4-1.4 1.4-5.4-5.4A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg>
      </button>
    </form>
  </div>
  <?php if ($cats): ?>
  <nav class="chips wrap" aria-label="Danh mục">
    <a href="/" class="chip<?= empty($activeCat) ? ' on' : '' ?>"><?= e(t('all')) ?></a>
    <?php foreach ($cats as $c): ?>
      <a href="/c/<?= rawurlencode($c['category']) ?>" class="chip<?= ($activeCat ?? '') === $c['category'] ? ' on' : '' ?>"><?= e($c['category']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
</header>

<main class="wrap"><?= $content ?></main>

<footer class="foot">
  <div class="wrap">
    <p class="disc"><?= e(t('disclosure')) ?></p>
    <p class="credit">© <?= date('Y') ?> <?= e($siteName) ?> ·
      <?= e(t('built')) ?> <a href="<?= APP_REPO ?>" rel="noopener">VN Affiliate Shop</a> ·
      <a href="<?= APP_PRO ?>" rel="noopener"><?= e(t('pro')) ?></a></p>
  </div>
</footer>
</body>
</html>
