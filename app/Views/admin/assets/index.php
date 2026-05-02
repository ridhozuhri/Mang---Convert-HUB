<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <h1 style="margin:0;">Assets</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('/admin/assets/create')) ?>">Tambah Asset</a>
    </div>

    <div style="margin-top:16px;overflow:auto;">
        <table class="table-admin" style="width:100%;border-collapse:collapse;">
            <thead>
            <tr>
                <th>ID</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Tujuan Checkout</th>
                <th>Usage</th>
                <th>Limit Status</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($assets === []): ?>
                <tr><td colspan="9">Belum ada data asset.</td></tr>
            <?php endif; ?>
            <?php foreach ($assets as $asset): ?>
                <?php $isActive = (string) ($asset['status'] ?? '') === 'active'; ?>
                <?php
                    $capacity = isset($asset['capacity_limit']) && is_numeric((string) $asset['capacity_limit']) ? (float) $asset['capacity_limit'] : 0.0;
                    $used = (float) ($asset['used_amount'] ?? 0);
                    $usagePercent = $capacity > 0 ? (float) ($asset['usage_percent'] ?? 0) : null;
                    $limitBadge = 'badge-success';
                    $limitText = 'OK';
                    if ($usagePercent !== null && $usagePercent >= 100) {
                        $limitBadge = 'badge-danger';
                        $limitText = 'Stock Limit';
                    } elseif ($usagePercent !== null && $usagePercent >= 80) {
                        $limitBadge = 'badge-warning';
                        $limitText = 'Warning';
                    }
                    $destinationNumber = trim((string) ($asset['destination_account_number'] ?? ''));
                    $destinationName = trim((string) ($asset['destination_account_name'] ?? ''));
                ?>
                <tr>
                    <td class="mono"><?= esc((string) $asset['id']) ?></td>
                    <td class="mono"><?= esc($asset['code']) ?></td>
                    <td><?= esc($asset['name']) ?></td>
                    <td><?= esc($asset['category_name'] ?? '-') ?></td>
                    <td>
                        <?php if ($destinationNumber !== ''): ?>
                            <span class="mono"><?= esc($destinationNumber) ?></span>
                            <?php if ($destinationName !== ''): ?>
                                <div class="text-muted"><?= esc($destinationName) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($capacity > 0): ?>
                            <div class="mono"><?= esc(number_format($used, 2, '.', ',')) ?> / <?= esc(number_format($capacity, 2, '.', ',')) ?></div>
                            <div><span class="badge badge-info"><?= esc(number_format($usagePercent ?? 0, 2, '.', ',')) ?>%</span></div>
                        <?php else: ?>
                            <span class="text-muted">Tidak dibatasi</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= esc($limitBadge) ?>"><?= esc($limitText) ?></span></td>
                    <td>
                        <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
                            <?= esc((string) ($asset['status'] ?? '-')) ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= esc(site_url('/admin/assets/' . $asset['id'] . '/edit')) ?>">Edit</a>
                        <form method="post" action="<?= esc(site_url('/admin/assets/' . $asset['id'] . '/delete')) ?>" style="display:inline-block;margin-left:10px;">
                            <?= csrf_field() ?>
                            <button class="btn" type="submit" onclick="return confirm('Hapus asset ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
