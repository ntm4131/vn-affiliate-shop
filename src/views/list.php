<section class="block">
  <div class="block-head">
    <h1><?= e($heading) ?></h1>
    <?php if ($items): ?><span class="count"><?= count($items) ?></span><?php endif; ?>
  </div>
  <?php if ($items): ?>
    <div class="grid">
      <?php foreach ($items as $p) { require __DIR__ . '/_card.php'; } ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <p><?= e(t('empty')) ?></p>
      <a class="btn" href="/"><?= e(t('back')) ?></a>
    </div>
  <?php endif; ?>
</section>
