<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Management User</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('/admin/users/create')) ?>">Tambah User</a>
    </div>

    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7">Belum ada user.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="mono"><?= esc((string) $row['id']) ?></td>
                        <td><?= esc((string) ($row['name'] ?? '-')) ?></td>
                        <td class="mono"><?= esc((string) ($row['email'] ?? '-')) ?></td>
                        <td><span class="badge"><?= esc((string) ($row['role'] ?? 'user')) ?></span></td>
                        <td>
                            <?php if ((int) ($row['is_active'] ?? 0) === 1): ?>
                                <span class="badge badge-success">active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="mono"><?= esc((string) ($row['last_login_at'] ?? '-')) ?></td>
                        <td>
                            <a href="<?= esc(site_url('/admin/users/' . $row['id'] . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= esc(site_url('/admin/users/' . $row['id'] . '/toggle')) ?>" style="display:inline-block;margin-left:8px;">
                                <?= csrf_field() ?>
                                <button class="btn" type="submit"><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
