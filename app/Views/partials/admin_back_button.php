<?php
$currentPath = trim((string) service('uri')->getPath(), '/');
$isAdminHome = $currentPath === 'admin' || $currentPath === 'admin/';
$fallbackUrl = site_url('/admin');
$backUrl = previous_url() ?: $fallbackUrl;
if (! str_contains((string) $backUrl, '/admin')) {
    $backUrl = $fallbackUrl;
}
?>

<div style="margin-bottom:10px;display:flex;justify-content:flex-start;">
    <?php if (! $isAdminHome): ?>
        <a class="btn" href="<?= esc($backUrl) ?>">← Kembali</a>
    <?php else: ?>
        <a class="btn" href="<?= esc(site_url('/')) ?>">← Ke Public Site</a>
    <?php endif; ?>
</div>
