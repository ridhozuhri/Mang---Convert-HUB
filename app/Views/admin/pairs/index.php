<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <?php
    $precisionByCode = ['IDR' => 0, 'USD' => 2, 'USDT' => 2, 'EUR' => 2, 'SGD' => 2];
    $formatDecimal = static function ($value, int $precision = 8): string {
        $raw = trim((string) $value);
        if ($raw === '' || ! is_numeric($raw)) {
            return '0';
        }
        $number = (float) $raw;
        $formatted = number_format($number, $precision, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    };
    $totalPairs = count($pairs ?? []);
    $activePairs = count(array_filter($pairs ?? [], static fn(array $item): bool => (string) ($item['status'] ?? '') === 'active'));
    ?>

    <div class="page-head">
        <h1>Pair Manager</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('/admin/pairs/create')) ?>">Tambah Pair</a>
    </div>

    <div class="grid-2" style="margin-top:12px;">
        <div class="card panel-box">
            <div class="text-muted">Total Pair</div>
            <div class="mono" style="font-size:28px;"><?= esc((string) $totalPairs) ?></div>
        </div>
        <div class="card panel-box">
            <div class="text-muted">Pair Aktif</div>
            <div class="mono" style="font-size:28px;"><?= esc((string) $activePairs) ?></div>
        </div>
    </div>

    <form method="get" action="<?= esc(site_url('/admin/pairs')) ?>" class="card panel-box" style="margin-top:12px;">
        <div class="grid-2">
            <div class="form-group">
                <label for="q">Cari Pair / Asset</label>
                <input id="q" class="form-input" type="text" name="q" value="<?= esc((string) ($filters['q'] ?? '')) ?>" placeholder="contoh: pulsa, idr, paypal">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <?php $statusFilter = (string) ($filters['status'] ?? ''); ?>
                <select id="status" class="form-input" name="status">
                    <option value="">Semua</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>inactive</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn" href="<?= esc(site_url('/admin/pairs')) ?>">Reset</a>
        </div>
    </form>

    <div style="margin-top:14px;display:grid;gap:10px;">
        <?php if (($pairs ?? []) === []): ?>
            <div class="card panel-box">Belum ada pair yang sesuai filter.</div>
        <?php endif; ?>
        <?php foreach (($pairs ?? []) as $pair): ?>
            <?php
            $fromCode = strtoupper((string) ($pair['from_code'] ?? ''));
            $fromPrecision = $precisionByCode[$fromCode] ?? 8;
            $rateMode = (string) ($pair['rate_mode'] ?? 'manual');
            $ratePrecision = $rateMode === 'inverse' ? 2 : 8;
            $rateDisplay = $formatDecimal($pair['last_rate'] ?? '0', $ratePrecision);
            $feeDisplay = $formatDecimal($pair['fee_value'] ?? '0', 8);
            $minDisplay = $formatDecimal($pair['min_amount'] ?? '0', $fromPrecision);
            $maxDisplay = $formatDecimal($pair['max_amount'] ?? '0', $fromPrecision);
            $isActive = (string) ($pair['status'] ?? '') === 'active';
            ?>
            <details class="card panel-box" style="padding:0;">
                <summary style="list-style:none;cursor:pointer;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <div>
                        <div style="font-weight:700;">
                            <span class="mono"><?= esc((string) ($pair['from_code'] ?? '-')) ?></span> →
                            <span class="mono"><?= esc((string) ($pair['to_code'] ?? '-')) ?></span>
                        </div>
                        <div class="text-muted">Rate: <span class="mono"><?= esc($rateDisplay) ?></span> · Min/Max: <span class="mono"><?= esc($minDisplay) ?>/<?= esc($maxDisplay) ?></span></div>
                    </div>
                    <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>"><?= esc((string) ($pair['status'] ?? '-')) ?></span>
                </summary>
                <div style="padding:0 16px 14px;">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Fee</label>
                            <div class="mono"><?= esc($feeDisplay) ?> (<?= esc((string) ($pair['fee_type'] ?? '-')) ?>)</div>
                        </div>
                        <div class="form-group">
                            <label>Spread</label>
                            <div class="mono"><?= esc($formatDecimal($pair['spread_value'] ?? '0', 8)) ?> (<?= esc((string) ($pair['spread_type'] ?? '-')) ?>)</div>
                        </div>
                        <div class="form-group">
                            <label>Rounding</label>
                            <div class="mono"><?= esc((string) ($pair['rounding_mode'] ?? '-')) ?> / <?= esc((string) ($pair['rounding_precision'] ?? '-')) ?></div>
                        </div>
                        <div class="form-group">
                            <label>Rate Mode</label>
                            <div class="mono"><?= esc($rateMode) ?></div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a class="btn" href="<?= esc(site_url('/admin/pairs/' . $pair['id'] . '/edit')) ?>">Edit Pair</a>
                        <a class="btn" href="<?= esc(site_url('/admin/rates/' . $pair['id'] . '/history')) ?>">Lihat History Rate</a>
                        <form method="post" action="<?= esc(site_url('/admin/pairs/' . $pair['id'] . '/delete')) ?>" style="display:inline-block;">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Hapus pair ini?')">Hapus</button>
                        </form>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
