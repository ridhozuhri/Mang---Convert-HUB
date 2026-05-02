<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Rate Manager</h1>
    <p class="text-muted">Update rate per pair. Gunakan mode <span class="mono">manual</span> atau <span class="mono">inverse</span> sesuai kebutuhan margin.</p>
    <?php
    $formatRate = static function ($value, string $mode): string {
        $raw = trim((string) $value);
        if ($raw === '' || ! is_numeric($raw)) {
            return '0';
        }
        $precision = $mode === 'inverse' ? 2 : 8;
        $formatted = number_format((float) $raw, $precision, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };
    ?>

    <div class="table-wrap" data-rate-manager data-update-base="<?= esc(site_url('/admin/rates')) ?>">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Pair</th>
                    <th>Mode</th>
                    <th>Rate Input</th>
                    <th>Updated At</th>
                    <th>Updated By</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pairs === []): ?>
                    <tr><td colspan="6">Belum ada data pair.</td></tr>
                <?php endif; ?>
                <?php foreach ($pairs as $pair): ?>
                    <?php
                    $mode = (string) ($pair['rate_mode'] ?? 'manual');
                    $fromCode = (string) ($pair['from_code'] ?? '-');
                    $toCode = (string) ($pair['to_code'] ?? '-');
                    $rateDisplay = $formatRate($pair['last_rate'] ?? '0', $mode);
                    $modeLabel = $mode === 'inverse' ? 'inverse (1 ' . $toCode . ' = x ' . $fromCode . ')' : 'manual (1 ' . $fromCode . ' = x ' . $toCode . ')';
                    ?>
                    <tr id="pair-row-<?= esc((string) $pair['id']) ?>">
                        <td class="mono"><?= esc($fromCode . ' -> ' . $toCode) ?></td>
                        <td class="mono js-rate-mode"><?= esc($mode) ?></td>
                        <td class="mono js-rate"><?= esc($rateDisplay) ?></td>
                        <td class="mono js-updated-at"><?= esc((string) ($pair['last_rate_updated_at'] ?? '-')) ?></td>
                        <td class="js-updated-by"><?= esc((string) ($pair['updated_by_name'] ?? '-')) ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-rate-open
                                data-id="<?= esc((string) $pair['id']) ?>"
                                data-pair="<?= esc($fromCode . ' -> ' . $toCode) ?>"
                                data-rate="<?= esc($rateDisplay) ?>"
                                data-mode="<?= esc($mode) ?>"
                                data-from="<?= esc($fromCode) ?>"
                                data-to="<?= esc($toCode) ?>"
                                data-mode-label="<?= esc($modeLabel) ?>"
                            >Update Rate</button>
                            <a class="btn" href="<?= esc(site_url('/admin/rates/' . $pair['id'] . '/history')) ?>">History</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="modal-overlay" data-rate-modal hidden>
            <div class="card" style="max-width:560px;margin:72px auto;">
                <h3>Update Rate</h3>
                <p class="mono" data-rate-pair></p>
                <p class="text-muted">Mode: <span data-rate-mode-label></span></p>
                <p class="text-muted">Rate saat ini: <span class="mono" data-rate-current></span></p>
                <p class="text-muted" data-rate-hint></p>

                <form data-rate-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="pair_id" value="">
                    <div class="form-group">
                        <label for="new_rate">New Rate</label>
                        <input id="new_rate" class="form-input mono" type="number" step="0.000000000001" name="new_rate" required>
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason</label>
                        <textarea id="reason" class="form-input" rows="4" name="reason" minlength="10" maxlength="500" required></textarea>
                        <small class="text-muted">Minimal 10 karakter.</small>
                    </div>
                    <div class="alert-flash alert-danger" data-rate-error hidden></div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary" data-rate-submit>Simpan</button>
                        <button type="button" class="btn" data-rate-close>Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script defer src="<?= esc(base_url('assets/js/admin-rate-manager.js')) ?>"></script>
<?= $this->endSection() ?>
