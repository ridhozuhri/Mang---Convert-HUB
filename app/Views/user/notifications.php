<?= $this->extend('layouts/user') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Notifikasi</h1>
        <form method="post" action="<?= esc(site_url('/user/notifications/read')) ?>">
            <?= csrf_field() ?>
            <button class="btn" type="submit">Tandai semua dibaca</button>
        </form>
    </div>

    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Judul</th>
                    <th>Pesan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="4">Belum ada notifikasi.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="mono"><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['title'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['body'] ?? '-')) ?></td>
                        <td><?= esc(((int) ($row['is_read'] ?? 0)) === 1 ? 'Read' : 'Unread') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
