<?= $this->extend('layouts/user') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Order Saya</h1>
    <p class="text-muted">Daftar order yang sudah Anda buat.</p>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="4">Belum ada order.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="mono"><?= esc($row['code']) ?></td>
                        <td class="mono"><?= esc((string) $row['amount_sent']) ?> / <?= esc((string) $row['amount_received']) ?></td>
                        <td><?= esc($row['status']) ?></td>
                        <td><a href="<?= esc(site_url('/user/orders/' . $row['code'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
