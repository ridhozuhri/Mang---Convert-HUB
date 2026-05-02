<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1><?= esc($row ? 'Edit User' : 'Tambah User') ?></h1>

    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="form-group">
                <label for="name">Nama</label>
                <input id="name" class="form-input" type="text" name="name" value="<?= esc((string) old('name', $row['name'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" class="form-input mono" type="email" name="email" value="<?= esc((string) old('email', $row['email'] ?? '')) ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="role">Role</label>
                <?php $role = (string) old('role', $row['role'] ?? 'user'); ?>
                <select id="role" class="form-input" name="role" required>
                    <?php foreach (['admin','staff','viewer','user'] as $item): ?>
                        <option value="<?= esc($item) ?>" <?= $role === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <?php $isActive = (string) old('is_active', (string) ((int) ($row['is_active'] ?? 1))); ?>
                <select id="is_active" class="form-input" name="is_active" required>
                    <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>active</option>
                    <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>inactive</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="password"><?= esc($row ? 'Password Baru (opsional)' : 'Password') ?></label>
            <input id="password" class="form-input" type="password" name="password" <?= $row ? '' : 'required' ?>>
            <?php if ($row): ?>
                <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
            <?php endif; ?>
        </div>

        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="<?= esc(site_url('/admin/users')) ?>">Kembali</a>
    </form>
</section>
<?= $this->endSection() ?>
