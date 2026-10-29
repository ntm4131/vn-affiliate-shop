<section class="hero">
  <div>
    <p class="eyebrow">🔥 <?= e(t('hot')) ?></p>
    <h1><?= e(setting('site_tagline', t('tagline'))) ?></h1>
    <p class="lead"><?= e(t('hot_sub')) ?></p>
  </div>
  <ul class="trust">
    <li>✅ <?= e(t('why1')) ?></li>
    <li>🏷️ <?= e(t('why2')) ?></li>
    <li>🛡️ <?= e(t('why3')) ?></li>
  </ul>
</section>

<?php if ($hot): ?>
<section class="block">
  <div class="block-head"><h2><?= e(t('hot')) ?></h2></div>
  <div class="grid">
    <?php foreach ($hot as $p) { require __DIR__ . '/_card.php'; } ?>
  </div>
</section>
<?php endif; ?>

<section class="block">
  <div class="block-head"><h2><?= e($title) ?></h2></div>
  <div class="grid">
    <?php foreach ($items as $p) { require __DIR__ . '/_card.php'; } ?>
  </div>
</section>

<section class="cta">
  <h2><?= e(t('cta_title')) ?></h2>
  <p><?= e(t('cta_text')) ?></p>
  <form action="/tim" class="search search-lg"><input name="q" placeholder="<?= e(t('search')) ?>"><button class="btn"><?= lang() === 'en' ? 'Search' : 'Tìm deal' ?></button></form>
</section>
