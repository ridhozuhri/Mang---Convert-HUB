<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Blast Notification</h1>
    <form method="post" action="<?= esc(site_url('/admin/notifications/blast')) ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="title">Judul</label>
            <input id="title" class="form-input" type="text" name="title" required value="<?= esc(old('title') ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="body">Pesan</label>
            <textarea id="body" class="form-input" name="body" rows="6" required><?= esc(old('body') ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Kirim Blast</button>
    </form>
</section>
<?= $this->endSection() ?>
