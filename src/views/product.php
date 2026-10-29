<?php
$main = $p;
$off = discount($p);
$schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        array_filter([
            '@type' => 'Product',
            'name' => $p['name'],
            'image' => $p['image'] ?: null,
            'description' => $p['description'] ?: $p['name'],
            'category' => $p['category'] ?: null,
            'offers' => $p['price'] > 0 ? [
                '@type' => 'Offer', 'price' => $p['price'], 'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock', 'url' => base_url() . '/p/' . $p['slug'],
            ] : null,
            'aggregateRating' => ($p['rating'] > 0 && $p['sold'] > 0) ? [
                '@type' => 'AggregateRating', 'ratingValue' => $p['rating'], 'reviewCount' => max(1, (int) ($p['sold'] / 10)),
            ] : null,
        ]),
        ['@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => setting('site_name', 'Deal Hay'), 'item' => base_url() . '/'],
            $p['category'] ? ['@type' => 'ListItem', 'position' => 2, 'name' => $p['category'], 'item' => base_url() . '/c/' . rawurlencode($p['category'])] : null,
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['name']],
        ]))],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<nav class="crumbs"><a href="/"><?= e(setting('site_name', 'Deal Hay')) ?></a>
  <?php if ($p['category']): ?> › <a href="/c/<?= rawurlencode($p['category']) ?>"><?= e($p['category']) ?></a><?php endif; ?></nav>

<article class="pd">
  <div class="pd-img">
    <?php if ($p['image']): ?>
      <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" width="600" height="600" fetchpriority="high">
    <?php else: ?>
      <span class="ph ph-lg" aria-hidden="true"><?= e($p['emoji'] ?: '🛍️') ?></span>
    <?php endif; ?>
    <?php if ($off >= 5): ?><span class="badge badge-lg">-<?= $off ?>%</span><?php endif; ?>
  </div>
  <div class="pd-info">
    <h1><?= e($p['name']) ?></h1>
    <div class="meta">
      <?php if ($p['rating'] > 0): ?><span class="star">★ <?= number_format((float) $p['rating'], 1) ?></span><?php endif; ?>
      <?php if ($p['sold'] > 0): ?><span><?= number_format((int) $p['sold'], 0, ',', '.') ?> <?= e(t('sold')) ?></span><?php endif; ?>
      <span class="shop-tag"><?= e($p['shop']) ?></span>
    </div>
    <div class="price price-lg">
      <strong><?= money((int) $p['price']) ?></strong>
      <?php if ($off >= 5): ?><s><?= money((int) $p['old_price']) ?></s><span class="save"><?= e(t('off')) ?> <?= $off ?>%</span><?php endif; ?>
    </div>
    <a class="btn btn-lg" href="/go/<?= (int) $p['id'] ?>" rel="sponsored nofollow noopener" target="_blank"><?= e(t('buy')) ?> <?= e($p['shop']) ?> →</a>
    <ul class="why">
      <li>✅ <?= e(t('why1')) ?></li>
      <li>🏷️ <?= e(t('why2')) ?></li>
      <li>🛡️ <?= e(t('why3')) ?></li>
    </ul>
    <?php if ($p['description']): ?>
      <h2><?= e(t('detail')) ?></h2>
      <div class="desc"><?= nl2br(e($p['description'])) ?></div>
    <?php endif; ?>
  </div>
</article>

<?php if ($related): ?>
<section class="block">
  <div class="block-head"><h2><?= e(t('related')) ?></h2></div>
  <div class="grid">
    <?php foreach ($related as $p) { require __DIR__ . '/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

<div class="sticky-buy"><a class="btn btn-lg" href="/go/<?= (int) $main['id'] ?>" rel="sponsored nofollow noopener" target="_blank"><?= e(t('buy')) ?> <?= e($main['shop']) ?> · <?= money((int) $main['price']) ?></a></div>
