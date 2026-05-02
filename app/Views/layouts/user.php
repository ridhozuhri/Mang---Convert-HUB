<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'User Panel') ?> - <?= esc($siteName ?? 'ConvertHub') ?></title>
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="apple-touch-icon" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>">
</head>
<body>
    <?= $this->include('partials/nav_public') ?>
    <main class="container">
        <?= $this->include('partials/flash_messages') ?>
        <?= $this->include('partials/notifications_dropdown') ?>
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>
