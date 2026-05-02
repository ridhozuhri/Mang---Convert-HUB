<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Admin Panel') ?> - <?= esc($siteName ?? 'ConvertHub') ?></title>
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="apple-touch-icon" href="<?= esc(base_url('assets/img/favicon-nc.png')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="admin-body">
    <div class="admin-shell">
        <?= $this->include('partials/nav_admin') ?>
        <button type="button" class="admin-overlay" id="admin-overlay" aria-label="Tutup menu"></button>
        <main class="admin-main">
            <div class="admin-main-inner">
                <?= $this->include('partials/topbar_admin') ?>
                <?= $this->include('partials/admin_back_button') ?>
                <?= $this->include('partials/flash_messages') ?>
                <?= $this->include('partials/notifications_dropdown') ?>
                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>
    <script defer src="<?= esc(base_url('assets/js/admin.js')) ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
