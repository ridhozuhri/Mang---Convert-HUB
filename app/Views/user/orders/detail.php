<?= $this->extend('layouts/user') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Detail Order <span class="mono"><?= esc($row['code']) ?></span></h1>
    <p>Status: <strong><?= esc($row['status']) ?></strong></p>
    <p>Jumlah kirim: <span class="mono"><?= esc((string) $row['amount_sent']) ?></span></p>
    <p>Jumlah terima: <span class="mono"><?= esc((string) $row['amount_received']) ?></span></p>
    <?php if (! empty($row['customer_sender_account'])): ?>
        <p>Nomor/Akun Pengirim: <span class="mono"><?= esc((string) $row['customer_sender_account']) ?></span></p>
    <?php endif; ?>

    <?php
    $serviceRules = json_decode((string) ($row['service_rules_snapshot'] ?? ''), true);
    $serviceRules = is_array($serviceRules) ? $serviceRules : [];
    ?>
    <?php $destinationNumber = (string) ($serviceRules['effective_destination_number'] ?? $serviceRules['destination_number'] ?? ''); ?>
    <?php $destinationName = (string) ($serviceRules['effective_destination_name'] ?? $serviceRules['destination_name'] ?? ''); ?>
    <?php if ($destinationNumber !== ''): ?>
        <p>Tujuan Transfer:
            <span class="mono"><?= esc($destinationNumber) ?></span>
            <?php if ($destinationName !== ''): ?>
                (<?= esc($destinationName) ?>)
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if (! empty($serviceRules['note_to_customer'])): ?>
        <p><?= esc((string) $serviceRules['note_to_customer']) ?></p>
    <?php endif; ?>

    <?php if (! empty($row['proof_path'])): ?>
        <p>Bukti: <a href="<?= esc(base_url($row['proof_path'])) ?>" target="_blank">Lihat Bukti</a></p>
    <?php endif; ?>

    <hr style="border-color:var(--border-subtle);margin:16px 0;">

    <h3>Upload Bukti Bayar</h3>
    <form method="post" action="<?= esc(site_url('/user/orders/' . $row['code'] . '/proof')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <input class="form-input" type="file" name="proof" accept=".png,.jpg,.jpeg,.pdf" required>
        </div>
        <button class="btn btn-primary" type="submit">Upload</button>
    </form>

    <?php if ((string) $row['status'] === 'pending'): ?>
        <hr style="border-color:var(--border-subtle);margin:16px 0;">
        <h3>Batalkan Order</h3>
        <form method="post" action="<?= esc(site_url('/user/orders/' . $row['code'] . '/cancel')) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="reason">Alasan</label>
                <textarea id="reason" class="form-input" name="reason" rows="3">Dibatalkan user</textarea>
            </div>
            <button class="btn btn-danger" type="submit">Batalkan</button>
        </form>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
