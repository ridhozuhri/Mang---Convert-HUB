<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Maintenance</h1>
    <p class="text-muted"><?= esc($message ?? 'Sistem sedang dalam pemeliharaan.') ?></p>
</section>
<?= $this->endSection() ?>
