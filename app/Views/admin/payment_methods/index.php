<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Service Manager</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('/admin/payment-methods/create')) ?>">Tambah</a>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="6">Belum ada payment method.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <?php $isActive = (string) ($item['status'] ?? '') === 'active'; ?>
                    <tr>
                        <td class="mono"><?= esc((string) $item['id']) ?></td>
                        <td><?= esc($item['name']) ?></td>
                        <td class="mono"><?= esc($item['slug']) ?></td>
                        <td><?= esc($item['type']) ?></td>
                        <td>
                            <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
                                <?= esc((string) ($item['status'] ?? '-')) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= esc(site_url('/admin/payment-methods/' . $item['id'] . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= esc(site_url('/admin/payment-methods/' . $item['id'] . '/delete')) ?>" style="display:inline-block;margin-left:8px;">
                                <?= csrf_field() ?>
                                <button class="btn" type="submit" onclick="return confirm('Hapus data ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
