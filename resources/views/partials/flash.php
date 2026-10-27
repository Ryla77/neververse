<?php

declare(strict_types=1);

$flashTypes = [
    'success' => ['icon' => 'check', 'label' => 'Berhasil'],
    'error' => ['icon' => 'alert', 'label' => 'Perhatian'],
    'info' => ['icon' => 'info', 'label' => 'Informasi'],
];
?>
<div class="flash-stack" aria-live="polite">
    <?php foreach ($flashTypes as $type => $meta): ?>
        <?php $message = flash($type); ?>
        <?php if (is_string($message) && $message !== ''): ?>
            <div class="alert alert--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>" data-alert>
                <span class="alert__icon"><?= icon($meta['icon']) ?></span>
                <div class="alert__body">
                    <span class="alert__label"><?= e($meta['label']) ?></span>
                    <p><?= e($message) ?></p>
                </div>
                <button class="alert__close" type="button" data-dismiss aria-label="Tutup pesan"><?= icon('x', 'icon icon--sm') ?></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
