<?php

declare(strict_types=1);

$targetType = $targetType ?? 'novel';
$targetId = (int) ($targetId ?? 0);
$prefix = $targetType . '-' . $targetId;
?>
<details class="report-toggle">
    <summary><?= icon('flag', 'icon icon--xs') ?> Laporkan</summary>
    <form class="report-form" action="<?= e(url('lapor')) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="target_type" value="<?= e($targetType) ?>">
        <input type="hidden" name="target_id" value="<?= $targetId ?>">
        <div class="field">
            <label class="label" for="report-reason-<?= e($prefix) ?>">Alasan</label>
            <select class="select" id="report-reason-<?= e($prefix) ?>" name="reason" required>
                <option value="">Pilih alasan…</option>
                <option value="spam">Spam atau promosi</option>
                <option value="harassment">Pelecehan atau ujaran kebencian</option>
                <option value="copyright">Pelanggaran hak cipta</option>
                <option value="explicit">Konten tidak pantas</option>
                <option value="other">Lainnya</option>
            </select>
        </div>
        <div class="field">
            <label class="label" for="report-details-<?= e($prefix) ?>">Detail <span class="label__hint">opsional</span></label>
            <textarea class="textarea" id="report-details-<?= e($prefix) ?>" name="details" rows="2" maxlength="1000" placeholder="Jelaskan singkat masalahnya…"></textarea>
        </div>
        <button class="btn btn--soft btn--sm" type="submit">Kirim laporan</button>
    </form>
</details>
