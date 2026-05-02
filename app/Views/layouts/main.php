<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? ($siteName ?? 'ConvertHub')) ?></title>
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="apple-touch-icon" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <?php $assetVersion = (string) @filemtime(FCPATH . 'assets/css/app.css'); ?>
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>?v=<?= esc($assetVersion) ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="public-body">
    <?= $this->include('partials/nav_public') ?>

    <main class="container">
        <?= $this->include('partials/flash_messages') ?>
        <?= $this->renderSection('content') ?>
    </main>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.x.x/dist/cdn.min.js"></script>
    <?php $appJsVersion = (string) @filemtime(FCPATH . 'assets/js/app.js'); ?>
    <script src="<?= esc(base_url('assets/js/app.js')) ?>?v=<?= esc($appJsVersion) ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
