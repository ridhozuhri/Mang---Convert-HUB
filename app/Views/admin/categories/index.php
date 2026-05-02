<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <h1 style="margin:0;">Kategori</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('/admin/categories/create')) ?>">Tambah Kategori</a>
    </div>

    <div style="margin-top:16px;overflow:auto;">
        <table class="table-admin" style="width:100%;border-collapse:collapse;">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Urutan</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($categories === []): ?>
                <tr><td colspan="6">Belum ada data kategori.</td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $category): ?>
                <?php $isActive = (int) ($category['is_active'] ?? 0) === 1; ?>
                <tr>
                    <td class="mono"><?= esc((string) $category['id']) ?></td>
                    <td><?= esc($category['name']) ?></td>
                    <td class="mono"><?= esc($category['slug']) ?></td>
                    <td>
                        <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
                            <?= esc($isActive ? 'Active' : 'Inactive') ?>
                        </span>
                    </td>
                    <td class="mono"><?= esc((string) $category['sort_order']) ?></td>
                    <td>
                        <a href="<?= esc(site_url('/admin/categories/' . $category['id'] . '/edit')) ?>">Edit</a>
                        <form method="post" action="<?= esc(site_url('/admin/categories/' . $category['id'] . '/delete')) ?>" style="display:inline-block;margin-left:10px;">
                            <?= csrf_field() ?>
                            <button class="btn" type="submit" onclick="return confirm('Hapus kategori ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
