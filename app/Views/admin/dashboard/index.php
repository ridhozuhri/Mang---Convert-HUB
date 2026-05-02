<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Overview Operasional</h1>
        <a class="btn" href="<?= esc(site_url('/admin/orders')) ?>">Buka Orders</a>
    </div>

    <div class="grid-2" style="margin-top:12px;">
        <div class="card panel-box">
            <h3>Asset Warning</h3>
            <p class="mono" style="font-size:28px;margin:0;"><?= esc((string) ($widgets['asset_warning_count'] ?? 0)) ?></p>
            <p class="text-muted">Usage 80% - 99.99%</p>
        </div>
        <div class="card panel-box">
            <h3>Asset Stock Limit</h3>
            <p class="mono" style="font-size:28px;margin:0;"><?= esc((string) ($widgets['asset_stock_limit_count'] ?? 0)) ?></p>
            <p class="text-muted">Usage >= 100% (terblokir di publik)</p>
        </div>
        <div class="card panel-box">
            <h3>Pair Terdampak</h3>
            <p class="mono" style="font-size:28px;margin:0;"><?= esc((string) ($widgets['impacted_pairs_count'] ?? 0)) ?></p>
            <p class="text-muted">Pair aktif dengan asset tujuan stock-limit</p>
        </div>
        <div class="card panel-box">
            <h3>Total Orders</h3>
            <p class="mono" style="font-size:28px;margin:0;"><?= esc((string) ($widgets['total_orders'] ?? 0)) ?></p>
            <p class="text-muted">Semua data order</p>
        </div>
    </div>
</section>

<section class="card" style="margin-top:12px;">
    <div class="page-head">
        <h2 style="margin:0;">Ringkasan Asset</h2>
        <a class="btn" href="<?= esc(site_url('/admin/assets')) ?>">Kelola Asset</a>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr>
                <th>Asset</th>
                <th>Usage</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (($assets ?? []) === []): ?>
                <tr><td colspan="3">Belum ada data asset.</td></tr>
            <?php endif; ?>
            <?php foreach (($assets ?? []) as $asset): ?>
                <?php
                $percent = isset($asset['usage_percent']) ? (float) $asset['usage_percent'] : null;
                $badge = 'badge-success';
                $label = 'OK';
                if ($percent !== null && $percent >= 100) {
                    $badge = 'badge-danger';
                    $label = 'Stock Limit';
                } elseif ($percent !== null && $percent >= 80) {
                    $badge = 'badge-warning';
                    $label = 'Warning';
                }
                ?>
                <tr>
                    <td><?= esc((string) ($asset['code'] ?? '-')) ?> - <?= esc((string) ($asset['name'] ?? '-')) ?></td>
                    <td>
                        <?php if ($percent === null): ?>
                            <span class="text-muted">Tidak dibatasi</span>
                        <?php else: ?>
                            <span class="mono"><?= esc(number_format($percent, 2, '.', ',')) ?>%</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= esc($badge) ?>"><?= esc($label) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
