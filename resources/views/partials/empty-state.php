<?php

declare(strict_types=1);

$emptyIcon = $icon ?? 'book-open';
$emptyTitle = $title ?? 'Belum ada apa pun di sini';
$emptyMessage = $message ?? 'Coba kembali lagi nanti.';
?>
<div class="empty-state">
    <span class="empty-state__icon"><?= icon($emptyIcon) ?></span>
    <h3><?= e($emptyTitle) ?></h3>
    <p><?= e($emptyMessage) ?></p>
    <?php if (!empty($actionUrl) && !empty($actionLabel)): ?>
        <a class="btn btn--soft" href="<?= e($actionUrl) ?>"><?= e($actionLabel) ?></a>
    <?php endif; ?>
</div>
