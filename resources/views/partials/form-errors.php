<?php

declare(strict_types=1);

$validationErrors = flash('errors', []);
if (is_array($validationErrors) && $validationErrors !== []):
?>
    <div class="form-errors" role="alert">
        <strong><?= icon('alert', 'icon icon--sm') ?> Periksa kembali isian berikut</strong>
        <ul>
            <?php foreach ($validationErrors as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
