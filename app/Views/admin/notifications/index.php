<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <h1>Notifikasi Transaksi</h1>
            <p class="text-muted">Daftar notifikasi transaksi yang masih unread (global).</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="badge notif-badge">Unread <?= esc((string) ($unreadCount ?? 0)) ?></span>
            <form method="post" action="<?= esc(site_url('/admin/notifications/read-all')) ?>">
                <?= csrf_field() ?>
                <button class="btn" type="submit">Baca Semua</button>
            </form>
            <a class="btn" href="<?= esc(site_url('/admin/notifications/blast')) ?>">Blast Notifikasi</a>
        </div>
    </div>

    <form method="get" action="<?= esc(site_url('/admin/notifications')) ?>" class="card panel-box" style="margin-top:16px;">
        <div class="form-group">
            <label for="limit">Limit Data</label>
            <input id="limit" class="form-input" type="number" min="1" max="1000" name="limit" value="<?= esc((string) $limit) ?>">
        </div>
        <button class="btn btn-primary" type="submit">Terapkan</button>
    </form>

    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Body</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6">Belum ada notifikasi transaksi.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="mono"><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                        <td><?= esc((string) (($row['user_name'] ?? '-') . ' <' . ($row['user_email'] ?? '-') . '>')) ?></td>
                        <td class="mono"><?= esc((string) ($row['type'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['title'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['body'] ?? '-')) ?></td>
                        <td class="mono"><?= esc((string) ($row['data_json'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
