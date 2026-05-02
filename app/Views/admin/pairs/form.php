<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <?php
    $trimDecimal = static function ($value, string $fallback = '0'): string {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $fallback;
        }
        if (str_contains($raw, '.')) {
            $raw = rtrim(rtrim($raw, '0'), '.');
        }
        return $raw === '' ? $fallback : $raw;
    };
    ?>
    <h1><?= esc($pair ? 'Edit Pair' : 'Tambah Pair') ?></h1>

    <form method="post" action="<?= esc($action) ?>" data-pair-form>
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="form-group">
                <label for="from_asset_id">From Asset</label>
                <select class="form-input" id="from_asset_id" name="from_asset_id" data-asset-from required>
                    <option value="">Pilih</option>
                    <?php $fromAssetId = (string) old('from_asset_id', $pair['from_asset_id'] ?? ''); ?>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?= esc((string) $asset['id']) ?>" data-code="<?= esc((string) ($asset['code'] ?? '')) ?>" <?= $fromAssetId === (string) $asset['id'] ? 'selected' : '' ?>>
                            <?= esc($asset['code']) ?> - <?= esc($asset['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="to_asset_id">To Asset</label>
                <select class="form-input" id="to_asset_id" name="to_asset_id" data-asset-to required>
                    <option value="">Pilih</option>
                    <?php $toAssetId = (string) old('to_asset_id', $pair['to_asset_id'] ?? ''); ?>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?= esc((string) $asset['id']) ?>" data-code="<?= esc((string) ($asset['code'] ?? '')) ?>" <?= $toAssetId === (string) $asset['id'] ? 'selected' : '' ?>>
                            <?= esc($asset['code']) ?> - <?= esc($asset['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="status">Status</label>
                <?php $status = (string) old('status', $pair['status'] ?? 'active'); ?>
                <select class="form-input" id="status" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input class="form-input mono" id="sort_order" name="sort_order" type="number" value="<?= esc((string) old('sort_order', $pair['sort_order'] ?? 0)) ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="min_amount">Min Amount</label>
                <input class="form-input mono" id="min_amount" name="min_amount" type="number" step="0.00000001" value="<?= esc($trimDecimal(old('min_amount', $pair['min_amount'] ?? '0'), '0')) ?>" required>
                <small class="text-muted" data-hint-min>Contoh USD: 10.5 | Contoh IDR: 150000</small>
            </div>
            <div class="form-group">
                <label for="max_amount">Max Amount</label>
                <input class="form-input mono" id="max_amount" name="max_amount" type="number" step="0.00000001" value="<?= esc($trimDecimal(old('max_amount', $pair['max_amount'] ?? '0'), '0')) ?>" required>
                <small class="text-muted" data-hint-max>Contoh USD: 5000.75 | Contoh IDR: 50000000</small>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="fee_type">Fee Type</label>
                <?php $feeType = (string) old('fee_type', $pair['fee_type'] ?? 'fixed'); ?>
                <select class="form-input" id="fee_type" name="fee_type">
                    <option value="fixed" <?= $feeType === 'fixed' ? 'selected' : '' ?>>fixed</option>
                    <option value="percent" <?= $feeType === 'percent' ? 'selected' : '' ?>>percent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fee_value">Fee Value</label>
                <input class="form-input mono" id="fee_value" name="fee_value" type="number" step="0.00000001" value="<?= esc($trimDecimal(old('fee_value', $pair['fee_value'] ?? '0'), '0')) ?>" required>
                <small class="text-muted">Jika <span class="mono">percent</span> isi persen (mis: 1.5), jika <span class="mono">fixed</span> isi nominal.</small>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="spread_type">Spread Type</label>
                <?php $spreadType = (string) old('spread_type', $pair['spread_type'] ?? 'none'); ?>
                <select class="form-input" id="spread_type" name="spread_type">
                    <option value="none" <?= $spreadType === 'none' ? 'selected' : '' ?>>none</option>
                    <option value="fixed" <?= $spreadType === 'fixed' ? 'selected' : '' ?>>fixed</option>
                    <option value="percent" <?= $spreadType === 'percent' ? 'selected' : '' ?>>percent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="spread_value">Spread Value</label>
                <input class="form-input mono" id="spread_value" name="spread_value" type="number" step="0.00000001" value="<?= esc($trimDecimal(old('spread_value', $pair['spread_value'] ?? '0'), '0')) ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="rounding_mode">Rounding Mode</label>
                <?php $roundingMode = (string) old('rounding_mode', $pair['rounding_mode'] ?? 'floor'); ?>
                <select class="form-input" id="rounding_mode" name="rounding_mode">
                    <option value="floor" <?= $roundingMode === 'floor' ? 'selected' : '' ?>>floor</option>
                    <option value="ceil" <?= $roundingMode === 'ceil' ? 'selected' : '' ?>>ceil</option>
                    <option value="round" <?= $roundingMode === 'round' ? 'selected' : '' ?>>round</option>
                </select>
            </div>
            <div class="form-group">
                <label for="rounding_precision">Rounding Precision</label>
                <input class="form-input mono" id="rounding_precision" name="rounding_precision" type="number" min="0" max="18" value="<?= esc((string) old('rounding_precision', $pair['rounding_precision'] ?? 2)) ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="rate_mode">Rate Mode</label>
                <?php $rateMode = (string) old('rate_mode', $pair['rate_mode'] ?? 'manual'); ?>
                <select class="form-input" id="rate_mode" name="rate_mode" data-rate-mode>
                    <option value="manual" <?= $rateMode === 'manual' ? 'selected' : '' ?>>manual (direct)</option>
                    <option value="inverse" <?= $rateMode === 'inverse' ? 'selected' : '' ?>>inverse (1/to = x/from)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="last_rate">Last Rate</label>
                <input class="form-input mono" id="last_rate" name="last_rate" type="number" step="0.000000000001" value="<?= esc($trimDecimal(old('last_rate', $pair['last_rate'] ?? '0'), '0')) ?>" required>
                <small class="text-muted" data-hint-rate>Format contoh: USD -> IDR = 17400 (manual) | IDR -> USD = 18000 (inverse)</small>
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea class="form-input" id="notes" name="notes" rows="4"><?= esc(old('notes', $pair['notes'] ?? '')) ?></textarea>
        </div>

        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= esc(site_url('/admin/pairs')) ?>">Kembali</a>
    </form>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/admin-pairs-form.js')) ?>"></script>
<?= $this->endSection() ?>
