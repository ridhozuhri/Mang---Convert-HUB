<?php
$role = (string) session()->get('role');
$email = (string) session()->get('email');
$name = (string) session()->get('name');
$uriPath = trim((string) service('uri')->getPath(), '/');
$currentPath = $uriPath === '' ? '/' : '/' . $uriPath;
$rbac = new \App\Libraries\RBACLibrary();
$can = static fn (string $minimumRole): bool => $rbac->hasMinimumRole($role, $minimumRole);
$isActive = static function (string $prefix) use ($currentPath): bool {
    return $currentPath === $prefix || str_starts_with($currentPath, $prefix . '/');
};

$sections = [
    [
        'title' => 'Operasional',
        'items' => [
            ['label' => 'Guide', 'href' => '/admin/guide', 'minimum' => 'viewer'],
            ['label' => 'Overview', 'href' => '/admin', 'minimum' => 'viewer'],
            ['label' => 'Order Transaksi', 'href' => '/admin/orders', 'minimum' => 'staff'],
            ['label' => 'Kelola Rate', 'href' => '/admin/rates', 'minimum' => 'staff'],
            ['label' => 'Rekonsiliasi', 'href' => '/admin/reconciliation', 'minimum' => 'staff'],
            ['label' => 'Audit Aktivitas', 'href' => '/admin/audit', 'minimum' => 'viewer'],
        ],
    ],
    [
        'title' => 'Master Data',
        'items' => [
            ['label' => 'Kategori Aset', 'href' => '/admin/categories', 'minimum' => 'admin'],
            ['label' => 'Daftar Aset', 'href' => '/admin/assets', 'minimum' => 'admin'],
            ['label' => 'Pair Konversi', 'href' => '/admin/pairs', 'minimum' => 'admin'],
            ['label' => 'Service', 'href' => '/admin/payment-methods', 'minimum' => 'admin'],
        ],
    ],
    [
        'title' => 'Sistem',
        'items' => [
            ['label' => 'Management User', 'href' => '/admin/users', 'minimum' => 'admin'],
            ['label' => 'Pusat Notifikasi', 'href' => '/admin/notifications', 'minimum' => 'admin'],
            ['label' => 'Broadcast Notifikasi', 'href' => '/admin/notifications/blast', 'minimum' => 'admin'],
            ['label' => 'Pengaturan Platform', 'href' => '/admin/settings', 'minimum' => 'admin'],
        ],
    ],
];
?>

<aside class="admin-sidebar">
    <div class="admin-sidebar-head">
        <a href="<?= esc(site_url('/admin')) ?>" class="admin-brand"><?= esc($siteName ?? 'ConvertHub') ?> Admin</a>
        <p class="admin-brand-sub">Light Console</p>
    </div>

    <nav class="admin-menu">
        <?php foreach ($sections as $section): ?>
            <?php $visibleItems = array_filter($section['items'], static fn ($item) => $can($item['minimum'])); ?>
            <?php if ($visibleItems === []): ?>
                <?php continue; ?>
            <?php endif; ?>
            <p class="admin-menu-title"><?= esc($section['title']) ?></p>
            <?php foreach ($visibleItems as $item): ?>
                <?php $active = $isActive($item['href']); ?>
                <a class="admin-menu-item <?= $active ? 'active' : '' ?>" href="<?= esc(site_url($item['href'])) ?>">
                    <?= esc($item['label']) ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-foot">
        <div class="admin-user-card">
            <p class="admin-user-name"><?= esc($name !== '' ? $name : 'Admin User') ?></p>
            <p class="admin-user-email mono"><?= esc($email) ?></p>
            <span class="badge"><?= esc($role) ?></span>
        </div>
        <div class="admin-side-actions">
            <a class="btn" href="<?= esc(site_url('/')) ?>">Lihat Public Site</a>
            <a class="btn btn-danger" href="<?= esc(site_url('/logout')) ?>">Logout</a>
        </div>
    </div>
</aside>
