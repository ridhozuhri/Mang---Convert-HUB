<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="card auth-card">
    <h1>Masuk</h1>
    <p class="text-muted">Akses akun <?= esc($siteName ?? 'ConvertHub') ?> Anda.</p>

    <form method="post" action="<?= esc(site_url('/login')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input class="form-input" id="email" type="email" name="email" value="<?= esc(old('email') ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input class="form-input" id="password" type="password" name="password" required>
        </div>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>

    <p class="text-muted mt-16">
        Belum punya akun? <a href="<?= esc(site_url('/register')) ?>">Daftar di sini</a>
    </p>
</section>
<?= $this->endSection() ?>
