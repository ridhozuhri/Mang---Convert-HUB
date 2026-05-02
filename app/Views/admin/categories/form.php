<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1><?= esc($category ? 'Edit Kategori' : 'Tambah Kategori') ?></h1>

    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Nama</label>
            <input class="form-input" id="name" name="name" type="text" value="<?= esc(old('name', $category['name'] ?? '')) ?>" required>
        </div>
        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input class="form-input mono" id="sort_order" name="sort_order" type="number" value="<?= esc((string) old('sort_order', $category['sort_order'] ?? 0)) ?>">
        </div>
        <div class="form-group">
            <label for="is_active">Status</label>
            <select class="form-input" id="is_active" name="is_active">
                <?php $isActive = (string) old('is_active', (string) ($category['is_active'] ?? 1)); ?>
                <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= esc(site_url('/admin/categories')) ?>">Kembali</a>
    </form>
</section>
<?= $this->endSection() ?>
