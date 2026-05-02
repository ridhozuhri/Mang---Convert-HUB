<?php
$unreadCount = 0;
$recentNotifications = [];
$notificationUrl = '/user/notifications';
$maxItems = 5;
if (session()->get('isLoggedIn')) {
    $userId = (int) session()->get('user_id');
    $role = (string) session()->get('role');
    $model = new \App\Models\NotificationModel();

    if (in_array($role, ['admin', 'staff', 'viewer'], true)) {
        $notificationUrl = '/admin/notifications';
        $maxItems = 3;
        $unreadCount = $model
            ->where('type', 'order_status')
            ->where('is_read', 0)
            ->countAllResults();
        $recentNotifications = $model
            ->where('type', 'order_status')
            ->where('is_read', 0)
            ->orderBy('id', 'DESC')
            ->findAll($maxItems);
    } else {
        $unreadCount = $model->unreadCount($userId);
        $recentNotifications = $model
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('id', 'DESC')
            ->findAll($maxItems);
    }
}
?>
<?php if (session()->get('isLoggedIn')): ?>
    <div class="card notif-widget">
        <div class="page-head notif-head">
            <div>
                <strong>Notifikasi</strong>
                <p class="text-muted notif-subtitle">Update terbaru sistem & order</p>
            </div>
            <a class="notif-link" href="<?= esc(site_url($notificationUrl)) ?>">Lihat semua</a>
        </div>
        <div class="notif-meta">
            <span class="badge notif-badge">Unread <?= esc((string) $unreadCount) ?></span>
        </div>
        <div class="notif-list">
            <?php if ($recentNotifications === []): ?>
                <div class="notif-empty">
                    <p class="text-muted">Belum ada notifikasi.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($recentNotifications as $item): ?>
                <div class="notif-item">
                    <div class="notif-dot <?= (int) ($item['is_read'] ?? 0) === 1 ? 'read' : '' ?>"></div>
                    <div>
                        <div class="notif-title"><?= esc((string) $item['title']) ?></div>
                        <div class="text-muted notif-body"><?= esc((string) $item['body']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
