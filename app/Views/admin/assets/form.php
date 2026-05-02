<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1><?= esc($asset ? 'Edit Asset' : 'Tambah Asset') ?></h1>
        <a class="btn" href="<?= esc(site_url('/admin/assets')) ?>">Kembali</a>
    </div>
    <?php if ($asset): ?>
        <div class="card panel-box" style="margin-top:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                <div>
                    <div class="text-muted">Asset yang sedang diedit</div>
                    <div style="font-weight:700;">
                        <span class="mono"><?= esc((string) ($asset['code'] ?? '-')) ?></span> - <?= esc((string) ($asset['name'] ?? '-')) ?>
                    </div>
                </div>
                <span class="badge <?= ((string) ($asset['status'] ?? '') === 'active') ? 'badge-success' : 'badge-danger' ?>">
                    <?= esc((string) ($asset['status'] ?? '-')) ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= esc($action) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="grid-2">
            <div class="card panel-box">
                <h3>Info Asset</h3>
                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select class="form-input" id="category_id" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php $selectedCategory = (string) old('category_id', $asset['category_id'] ?? ''); ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= esc((string) $category['id']) ?>" <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                                <?= esc($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="code">Code</label>
                    <input class="form-input mono" id="code" name="code" type="text" value="<?= esc(old('code', $asset['code'] ?? '')) ?>" placeholder="contoh: TELKOMSEL_PULSA" required>
                    <small class="text-muted">Gunakan huruf besar + underscore agar konsisten.</small>
                </div>
                <div class="form-group">
                    <label for="name">Nama</label>
                    <input class="form-input" id="name" name="name" type="text" value="<?= esc(old('name', $asset['name'] ?? '')) ?>" placeholder="contoh: Pulsa Telkomsel" required>
                </div>
                <div class="form-group">
                    <label for="symbol">Symbol</label>
                    <input class="form-input" id="symbol" name="symbol" type="text" value="<?= esc(old('symbol', $asset['symbol'] ?? '')) ?>" placeholder="contoh: TSEL">
                </div>
                <div class="form-group">
                    <label for="decimals">Decimals</label>
                    <input class="form-input mono" id="decimals" name="decimals" type="number" min="0" max="18" value="<?= esc((string) old('decimals', $asset['decimals'] ?? 2)) ?>" required>
                    <small class="text-muted">IDR/Pulsa umumnya `0`, mata uang asing biasanya `2`.</small>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <?php $status = (string) old('status', $asset['status'] ?? 'active'); ?>
                    <select class="form-input" id="status" name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input class="form-input mono" id="sort_order" name="sort_order" type="number" value="<?= esc((string) old('sort_order', $asset['sort_order'] ?? 0)) ?>">
                    <small class="text-muted">Semakin kecil nilainya, semakin atas urutan tampil.</small>
                </div>
                <div class="form-group">
                    <label for="logo">Logo</label>
                    <input class="form-input" id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.svg">
                    <?php if (! empty($asset['logo_path'])): ?>
                        <div style="margin-top:10px;">
                            <small class="text-muted">Logo saat ini:</small><br>
                            <img src="<?= esc(base_url($asset['logo_path'])) ?>" alt="logo preview" style="width:80px;height:80px;object-fit:contain;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card panel-box">
                <h3>Aturan Transaksi</h3>
                <div class="form-group">
                    <label for="capacity_limit">Limit Kapasitas Asset</label>
                    <input class="form-input mono" id="capacity_limit" name="capacity_limit" type="number" min="0" step="0.00000001" value="<?= esc((string) old('capacity_limit', $asset['capacity_limit'] ?? '')) ?>">
                    <small class="text-muted">Contoh: saldo/stok operasional asset tujuan yang tersedia.</small>
                </div>
                <div class="form-group">
                    <label for="hide_threshold_percent">Hide Threshold (%)</label>
                    <input class="form-input mono" id="hide_threshold_percent" name="hide_threshold_percent" type="number" min="1" max="100" value="<?= esc((string) old('hide_threshold_percent', $asset['hide_threshold_percent'] ?? 80)) ?>" required>
                </div>
                <div class="form-group">
                    <label for="is_auto_hide_enabled">Auto Hide Saat Limit Tercapai</label>
                    <?php $isAutoHide = (string) old('is_auto_hide_enabled', (string) ($asset['is_auto_hide_enabled'] ?? 1)); ?>
                    <select class="form-input" id="is_auto_hide_enabled" name="is_auto_hide_enabled">
                        <option value="1" <?= $isAutoHide === '1' ? 'selected' : '' ?>>Ya</option>
                        <option value="0" <?= $isAutoHide === '0' ? 'selected' : '' ?>>Tidak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="destination_account_type">Tipe Tujuan Checkout</label>
                    <?php $destinationType = (string) old('destination_account_type', $asset['destination_account_type'] ?? ''); ?>
                    <select class="form-input" id="destination_account_type" name="destination_account_type">
                        <option value="">- Pilih Tipe -</option>
                        <option value="bank_account" <?= $destinationType === 'bank_account' ? 'selected' : '' ?>>Nomor Rekening</option>
                        <option value="phone_number" <?= $destinationType === 'phone_number' ? 'selected' : '' ?>>Nomor HP</option>
                        <option value="other" <?= $destinationType === 'other' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="destination_account_number">Nomor Tujuan (Rekening / HP) <span style="color:#ef4444">*</span></label>
                    <input class="form-input mono" id="destination_account_number" name="destination_account_number" type="text" value="<?= esc(old('destination_account_number', $asset['destination_account_number'] ?? '')) ?>" placeholder="contoh: 081290001234 / 1234567890">
                    <small class="text-muted">Wajib diisi jika tipe tujuan = Nomor Rekening atau Nomor HP.</small>
                </div>
                <div class="form-group">
                    <label for="destination_account_name">Nama Pemilik / Label Tujuan</label>
                    <input class="form-input" id="destination_account_name" name="destination_account_name" type="text" value="<?= esc(old('destination_account_name', $asset['destination_account_name'] ?? '')) ?>" placeholder="contoh: CS Pulsa Telkomsel">
                </div>
                <div class="form-group">
                    <label for="checkout_instruction">Catatan Checkout untuk User</label>
                    <textarea class="form-input" id="checkout_instruction" name="checkout_instruction" rows="4" placeholder="contoh: Transfer pulsa ke nomor tujuan, lalu upload bukti agar diproses admin."><?= esc(old('checkout_instruction', $asset['checkout_instruction'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:8px;align-items:center;margin-top:12px;">
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
            <a class="btn" href="<?= esc(site_url('/admin/assets')) ?>">Batal</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
