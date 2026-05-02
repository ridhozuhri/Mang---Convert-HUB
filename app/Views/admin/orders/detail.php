<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Order <span class="mono"><?= esc($row['code']) ?></span></h1>
        <a class="btn" href="<?= esc(site_url('/admin/orders')) ?>">Kembali</a>
    </div>

    <div class="grid-2">
        <div class="card panel-box">
            <h3>Informasi Order</h3>
            <p>User: <?= esc(($row['user_name'] ?? '-') . ' (' . ($row['user_email'] ?? '-') . ')') ?></p>
            <p>Pair: <span class="mono"><?= esc(($row['from_code'] ?? '-') . '→' . ($row['to_code'] ?? '-')) ?></span></p>
            <p>Metode Pembayaran: <span class="mono"><?= esc((string) ($row['payment_method_name'] ?? '-')) ?></span></p>
            <p>Amount Sent: <span class="mono"><?= esc((string) $row['amount_sent']) ?></span></p>
            <p>Amount Received: <span class="mono"><?= esc((string) $row['amount_received']) ?></span></p>
            <p>Status: <strong><?= esc($row['status']) ?></strong></p>
            <?php if (! empty($row['sla_due_at'])): ?>
                <p>SLA Due: <span class="mono"><?= esc((string) $row['sla_due_at']) ?></span></p>
            <?php endif; ?>
            <?php if (! empty($row['locked_by'])): ?>
                <p>Locked By: <span class="mono"><?= esc((string) ($row['locked_by_name'] ?? $row['locked_by'])) ?></span></p>
            <?php endif; ?>
            <?php if (! empty($row['customer_sender_account'])): ?>
                <p>Akun/Nomor Pengirim Customer: <span class="mono"><?= esc((string) $row['customer_sender_account']) ?></span></p>
            <?php endif; ?>
            <?php
                $serviceRules = [];
                if (! empty($row['service_rules_snapshot'])) {
                    $decodedRules = json_decode((string) $row['service_rules_snapshot'], true);
                    if (is_array($decodedRules)) {
                        $serviceRules = $decodedRules;
                    }
                }
            ?>
            <?php $destinationNumber = (string) ($serviceRules['effective_destination_number'] ?? $serviceRules['destination_number'] ?? ''); ?>
            <?php $destinationName = (string) ($serviceRules['effective_destination_name'] ?? $serviceRules['destination_name'] ?? ''); ?>
            <?php if ($destinationNumber !== ''): ?>
                <p>Nomor Tujuan Service:
                    <span class="mono"><?= esc($destinationNumber) ?></span>
                    <?php if ($destinationName !== ''): ?>
                        (<?= esc($destinationName) ?>)
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if (! empty($serviceRules['note_to_customer'])): ?>
                <p>Catatan Service: <?= esc((string) $serviceRules['note_to_customer']) ?></p>
            <?php endif; ?>
            <p>Tanggal Order: <span class="mono"><?= esc((string) ($row['created_at'] ?? '-')) ?></span></p>
            <p>Terakhir Update: <span class="mono"><?= esc((string) ($row['updated_at'] ?? '-')) ?></span></p>
            <?php if (! empty($row['expires_at'])): ?>
                <p>Berlaku Hingga: <span class="mono"><?= esc((string) $row['expires_at']) ?></span></p>
            <?php endif; ?>
            <?php if (! empty($row['proof_uploaded_at'])): ?>
                <p>Waktu Upload Bukti: <span class="mono"><?= esc((string) $row['proof_uploaded_at']) ?></span></p>
            <?php endif; ?>
            <p>Proof Status: <strong><?= esc((string) ($row['proof_status'] ?? 'none')) ?></strong></p>
            <?php if (! empty($row['proof_reviewed_at'])): ?>
                <p>Proof Reviewed At: <span class="mono"><?= esc((string) $row['proof_reviewed_at']) ?></span></p>
            <?php endif; ?>
            <?php if (! empty($row['completed_at'])): ?>
                <p>Waktu Selesai: <span class="mono"><?= esc((string) $row['completed_at']) ?></span></p>
            <?php endif; ?>
            <?php if (! empty($row['proof_path'])): ?>
                <p>Proof: <a href="<?= esc(base_url($row['proof_path'])) ?>" target="_blank">Lihat Bukti</a></p>
            <?php endif; ?>
        </div>
        <div class="card panel-box">
            <h3>Update Status</h3>
            <div style="display:flex;gap:8px;margin-bottom:10px;">
                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/lock')) ?>" data-once-submit>
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">Lock</button>
                </form>
                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/unlock')) ?>" data-once-submit>
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">Unlock</button>
                </form>
                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" data-once-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="approve">
                    <button class="btn" type="submit">Approve</button>
                </form>
                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" data-once-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="process">
                    <button class="btn" type="submit">Process</button>
                </form>
            </div>
            <?php if (! empty($row['proof_path'])): ?>
                <div style="display:flex;gap:8px;margin-bottom:10px;">
                    <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/verify-proof')) ?>" data-once-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="decision" value="verify">
                        <button class="btn btn-primary" type="submit">Verify Proof</button>
                    </form>
                    <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/verify-proof')) ?>" data-once-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="decision" value="reject">
                        <input type="hidden" name="admin_note" value="Bukti pembayaran ditolak admin">
                        <button class="btn btn-danger" type="submit">Reject Proof</button>
                    </form>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/status')) ?>" data-once-submit>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" class="form-input" name="status">
                        <?php foreach (['pending','paid','processing','completed','cancelled','failed'] as $item): ?>
                            <option value="<?= esc($item) ?>" <?= (string) $row['status'] === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_note">Admin Note</label>
                    <textarea id="admin_note" class="form-input" name="admin_note" rows="5"><?= esc((string) ($row['admin_note'] ?? '')) ?></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Simpan</button>
            </form>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
