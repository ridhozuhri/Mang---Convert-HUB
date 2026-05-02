<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$groupKeys = array_keys($groups ?? []);
$defaultTab = (string) ($groupKeys[0] ?? '');
$groupLabels = [
    'converter' => 'CONVERTER',
    'general'   => 'GENERAL',
    'security'  => 'SECURITY',
    'smtp'      => 'SMTP',
    'system'    => 'SYSTEM',
];
?>
<section class="card">
    <h1>Settings</h1>

    <div class="settings-tabbar tab-bar" style="margin:14px 0;">
        <?php foreach ($groups as $group => $rows): ?>
            <?php $tabLabel = $groupLabels[(string) $group] ?? strtoupper((string) $group); ?>
            <button
                type="button"
                class="tab-item settings-tab-item <?= (string) $group === $defaultTab ? 'active' : '' ?>"
                data-tab="<?= esc((string) $group) ?>"
            ><?= esc($tabLabel) ?></button>
        <?php endforeach; ?>
    </div>

    <form method="post" action="<?= esc(site_url('/admin/settings')) ?>">
        <?= csrf_field() ?>
        <?php foreach ($groups as $group => $rows): ?>
            <?php $tabLabel = $groupLabels[(string) $group] ?? strtoupper((string) $group); ?>
            <div
                class="card panel-box settings-tab-panel"
                data-tab-panel="<?= esc((string) $group) ?>"
                <?= (string) $group === $defaultTab ? '' : 'hidden' ?>
            >
                <h3><?= esc($tabLabel) ?></h3>
                <?php foreach ($rows as $row): ?>
                    <div class="form-group">
                        <label><?= esc((string) ($row['label'] ?? $row['key'])) ?> <span class="mono">(<?= esc((string) $row['key']) ?>)</span></label>
                        <input class="form-input" name="settings[<?= esc((string) $group) ?>][<?= esc((string) $row['key']) ?>]" value="<?= esc((string) ($row['value'] ?? '')) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Simpan Semua</button>
    </form>

    <hr style="border-color:var(--border-subtle);margin:18px 0;">
    <h3>Maintenance Mode</h3>
    <form method="post" action="<?= esc(site_url('/admin/settings/maintenance')) ?>">
        <?= csrf_field() ?>
        <?php
        $isMaintenance = 0;
        foreach (($groups['system'] ?? []) as $systemRow) {
            if (($systemRow['key'] ?? '') === 'maintenance_mode') {
                $isMaintenance = ((string) ($systemRow['value'] ?? '0')) === '1' ? 1 : 0;
                break;
            }
        }
        ?>
        <input type="hidden" name="enable" value="<?= $isMaintenance === 1 ? '0' : '1' ?>">
        <button class="btn <?= $isMaintenance === 1 ? '' : 'btn-primary' ?>" type="submit">
            <?= $isMaintenance === 1 ? 'Matikan Maintenance' : 'Aktifkan Maintenance' ?>
        </button>
    </form>
</section>
<?= $this->endSection() ?>
