<?php
$pageTitle = (string) ($pageTitle ?? 'Dashboard');
$role = (string) session()->get('role');
$name = (string) session()->get('name');
?>
<header class="admin-topbar">
    <div class="admin-topbar-left">
        <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Buka menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="mac-dots" aria-hidden="true">
            <span class="dot dot-red"></span>
            <span class="dot dot-yellow"></span>
            <span class="dot dot-green"></span>
        </div>
        <div>
            <p class="admin-topbar-label">Admin Dashboard</p>
            <h2 class="admin-topbar-title"><?= esc($pageTitle) ?></h2>
        </div>
    </div>
    <div class="admin-topbar-right">
        <a class="btn" href="<?= esc(site_url('/')) ?>">Public Site</a>
        <div class="admin-topbar-user">
            <span class="badge"><?= esc($role) ?></span>
            <span><?= esc($name !== '' ? $name : 'Admin User') ?></span>
        </div>
    </div>
</header>
