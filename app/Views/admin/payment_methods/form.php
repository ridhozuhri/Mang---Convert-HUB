<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1><?= esc($item ? 'Edit' : 'Tambah') ?> Service</h1>
    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <div class="form-group">
                <label for="name">Name</label>
                <input id="name" class="form-input" type="text" name="name" required value="<?= esc(old('name', $item['name'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <?php $type = (string) old('type', $item['type'] ?? 'manual'); ?>
                <select id="type" class="form-input" name="type">
                    <option value="manual" <?= $type === 'manual' ? 'selected' : '' ?>>manual</option>
                    <option value="gateway" <?= $type === 'gateway' ? 'selected' : '' ?>>gateway</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="instructions">Instructions</label>
            <textarea id="instructions" class="form-input" name="instructions" rows="5"><?= esc(old('instructions', $item['instructions'] ?? '')) ?></textarea>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="status">Status</label>
                <?php $status = (string) old('status', $item['status'] ?? 'active'); ?>
                <select id="status" class="form-input" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input id="sort_order" class="form-input mono" type="number" name="sort_order" value="<?= esc((string) old('sort_order', $item['sort_order'] ?? 0)) ?>">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= esc(site_url('/admin/payment-methods')) ?>">Kembali</a>
    </form>

    <?php if ($item): ?>
        <hr style="border-color:var(--border-subtle);margin:20px 0;">
        <h3>Assign Pair + Rules</h3>
        <form method="post" action="<?= esc(site_url('/admin/payment-methods/' . $item['id'] . '/pairs')) ?>">
            <?= csrf_field() ?>
            <div class="table-wrap">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Aktif</th>
                            <th>Pair</th>
                            <th>Limit Min</th>
                            <th>Limit Max</th>
                            <th>Tujuan Asset (Default)</th>
                            <th>Override Tujuan Service</th>
                            <th>Minta No Pengirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($pairs ?? []) as $pair): ?>
                            <?php
                            $pairId = (int) $pair['id'];
                            $rules = $pairRules[$pairId] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="pair_ids[]" value="<?= esc((string) $pairId) ?>" <?= in_array($pairId, $activePairIds ?? [], true) ? 'checked' : '' ?>>
                                </td>
                                <td class="mono"><?= esc(($pair['from_code'] ?? '-') . ' -> ' . ($pair['to_code'] ?? '-')) ?></td>
                                <td><input class="form-input mono" type="number" step="0.00000001" name="pair_rules[<?= esc((string) $pairId) ?>][tx_min]" value="<?= esc((string) ($rules['tx_min'] ?? '')) ?>"></td>
                                <td><input class="form-input mono" type="number" step="0.00000001" name="pair_rules[<?= esc((string) $pairId) ?>][tx_max]" value="<?= esc((string) ($rules['tx_max'] ?? '')) ?>"></td>
                                <td>
                                    <div class="mono"><?= esc((string) ($pair['to_destination_account_number'] ?? '-')) ?></div>
                                    <div class="text-muted"><?= esc((string) ($pair['to_destination_account_name'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?php $useAssetDestination = array_key_exists('use_asset_destination', $rules) ? ! empty($rules['use_asset_destination']) : true; ?>
                                    <label><input type="checkbox" name="pair_rules[<?= esc((string) $pairId) ?>][use_asset_destination]" value="1" <?= $useAssetDestination ? 'checked' : '' ?>> Gunakan tujuan dari Asset</label>
                                    <input class="form-input" style="margin-top:6px" type="text" placeholder="No override (opsional)" name="pair_rules[<?= esc((string) $pairId) ?>][destination_number]" value="<?= esc((string) ($rules['destination_number'] ?? '')) ?>">
                                    <input class="form-input" style="margin-top:6px" type="text" placeholder="Nama override (opsional)" name="pair_rules[<?= esc((string) $pairId) ?>][destination_name]" value="<?= esc((string) ($rules['destination_name'] ?? '')) ?>">
                                </td>
                                <td>
                                    <label><input type="checkbox" name="pair_rules[<?= esc((string) $pairId) ?>][require_sender_account]" value="1" <?= ! empty($rules['require_sender_account']) ? 'checked' : '' ?>> Ya</label>
                                    <input class="form-input" style="margin-top:6px" type="text" placeholder="Label input customer" name="pair_rules[<?= esc((string) $pairId) ?>][sender_label]" value="<?= esc((string) ($rules['sender_label'] ?? 'Nomor Pengirim')) ?>">
                                    <textarea class="form-input" style="margin-top:6px" rows="2" placeholder="Catatan untuk customer" name="pair_rules[<?= esc((string) $pairId) ?>][note_to_customer]"><?= esc((string) ($rules['note_to_customer'] ?? '')) ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary" type="submit">Simpan Pair + Rules</button>
        </form>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
