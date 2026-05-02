<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<section class="card auth-card">
    <h1>Verifikasi 2FA</h1>
    <p class="text-muted">Skeleton 2FA aktif untuk admin. Masukkan kode 6 digit.</p>
    <p class="text-muted">Akun: <span class="mono"><?= esc($email ?? '-') ?></span></p>
    <p class="text-muted">Kadaluarsa: <span class="mono"><?= esc($expiresAt ?? '-') ?></span></p>

    <form method="post" action="<?= esc(site_url('/login/2fa')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="code">Kode 2FA</label>
            <input class="form-input mono" id="code" type="text" name="code" maxlength="6" pattern="[0-9]{6}" value="<?= esc(old('code') ?? '') ?>" required>
        </div>
        <button class="btn btn-primary" type="submit">Verifikasi</button>
    </form>

    <p class="text-muted mt-16">Skeleton mode: gunakan kode <span class="mono">000000</span>.</p>
</section>
<?= $this->endSection() ?>
