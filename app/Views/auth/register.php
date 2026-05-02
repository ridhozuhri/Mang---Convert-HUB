<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="card auth-card">
    <h1>Daftar</h1>
    <p class="text-muted">Buat akun baru untuk mulai membuat order.</p>

    <form method="post" action="<?= esc(site_url('/register')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Nama</label>
            <input class="form-input" id="name" type="text" name="name" value="<?= esc(old('name') ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input class="form-input" id="email" type="email" name="email" value="<?= esc(old('email') ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input class="form-input" id="password" type="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="password_confirm">Konfirmasi Password</label>
            <input class="form-input" id="password_confirm" type="password" name="password_confirm" required>
        </div>
        <button class="btn btn-primary" type="submit">Register</button>
    </form>

    <p class="text-muted mt-16">
        Sudah punya akun? <a href="<?= esc(site_url('/login')) ?>">Masuk di sini</a>
    </p>
</section>
<?= $this->endSection() ?>
