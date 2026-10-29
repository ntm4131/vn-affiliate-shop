<?php /** @var array $p */ $off = discount($p); ?>
<article class="card">
  <a class="card-img" href="/p/<?= e($p['slug']) ?>">
    <?php if ($p['image']): ?>
      <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" decoding="async" width="400" height="400">
    <?php else: ?>
      <span class="ph" aria-hidden="true"><?= e($p['emoji'] ?: '🛍️') ?></span>
    <?php endif; ?>
    <?php if ($off >= 5): ?><span class="badge">-<?= $off ?>%</span><?php endif; ?>
  </a>
  <div class="card-body">
    <h3 class="card-name"><a href="/p/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
    <div class="price">
      <strong><?= money((int) $p['price']) ?></strong>
      <?php if ($off >= 5): ?><s><?= money((int) $p['old_price']) ?></s><?php endif; ?>
    </div>
    <div class="meta">
      <?php if ($p['rating'] > 0): ?><span class="star">★ <?= number_format((float) $p['rating'], 1) ?></span><?php endif; ?>
      <?php if ($p['sold'] > 0): ?><span><?= $p['sold'] >= 1000 ? number_format($p['sold'] / 1000, 1, ',', '') . 'k' : $p['sold'] ?> <?= e(t('sold')) ?></span><?php endif; ?>
    </div>
    <a class="btn btn-sm" href="/go/<?= (int) $p['id'] ?>" rel="sponsored nofollow noopener" target="_blank"><?= e(t('buy')) ?> <?= e($p['shop']) ?></a>
  </div>
</article>
