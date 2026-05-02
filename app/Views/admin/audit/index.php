<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <?php
    $prettyData = static function ($value): string {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-' || $raw === 'null') {
            return '-';
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $raw;
    };
    ?>
    <h1>Audit Log</h1>
    <p class="text-muted">Lacak semua perubahan penting yang dilakukan oleh admin/staff.</p>

    <form method="get" action="<?= esc(site_url('/admin/audit')) ?>" class="card panel-box audit-filter-panel">
        <div class="audit-filter-grid">
            <div class="form-group">
                <label for="action">Action</label>
                <select class="form-input" id="action" name="action">
                    <option value="">Semua</option>
                    <?php foreach ($actions as $item): ?>
                        <option value="<?= esc((string) $item) ?>" <?= (string) ($filters['action'] ?? '') === (string) $item ? 'selected' : '' ?>>
                            <?= esc((string) $item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <select class="form-input" id="model" name="model">
                    <option value="">Semua</option>
                    <?php foreach ($models as $item): ?>
                        <option value="<?= esc((string) $item) ?>" <?= (string) ($filters['model'] ?? '') === (string) $item ? 'selected' : '' ?>>
                            <?= esc((string) $item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="audit-filter-grid">
            <div class="form-group">
                <label for="user_id">User</label>
                <select class="form-input" id="user_id" name="user_id">
                    <option value="0">Semua</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= esc((string) $user['id']) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= esc((string) $user['name']) ?> (<?= esc((string) $user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Rentang Tanggal</label>
                <div class="audit-date-grid">
                    <input class="form-input" type="date" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
                    <input class="form-input" type="date" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
                </div>
            </div>
        </div>

        <div class="audit-filter-actions">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn" href="<?= esc(site_url('/admin/audit')) ?>">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="table-admin audit-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Model</th>
                    <th>Data Lama</th>
                    <th>Data Baru</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7">Tidak ada data audit.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $action = (string) ($row['action'] ?? '-');
                    $actionClass = 'audit-action-default';
                    if (str_contains($action, 'create') || str_contains($action, 'login')) {
                        $actionClass = 'audit-action-success';
                    } elseif (str_contains($action, 'update') || str_contains($action, 'toggle')) {
                        $actionClass = 'audit-action-info';
                    } elseif (str_contains($action, 'delete') || str_contains($action, 'failed') || str_contains($action, 'cancel')) {
                        $actionClass = 'audit-action-danger';
                    }
                    $oldData = $prettyData((string) ($row['old_values'] ?? ''));
                    $newData = $prettyData((string) ($row['new_values'] ?? ''));
                    ?>
                    <tr>
                        <td class="mono"><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                        <td>
                            <?= esc((string) ($row['user_name'] ?? 'System')) ?>
                            <div class="text-muted mono"><?= esc((string) ($row['user_email'] ?? '-')) ?></div>
                        </td>
                        <td><span class="audit-action-badge <?= esc($actionClass) ?> mono"><?= esc($action) ?></span></td>
                        <td class="mono"><?= esc((string) ($row['model'] ?? '-')) ?> #<?= esc((string) ($row['model_id'] ?? '-')) ?></td>
                        <td class="mono audit-data-cell">
                            <details class="audit-data-box">
                                <summary>Lihat</summary>
                                <pre><?= esc($oldData) ?></pre>
                            </details>
                        </td>
                        <td class="mono audit-data-cell">
                            <details class="audit-data-box" open>
                                <summary>Lihat</summary>
                                <pre><?= esc($newData) ?></pre>
                            </details>
                        </td>
                        <td class="mono"><?= esc((string) ($row['ip_address'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="audit-pagination">
        <?= $pager->links() ?>
    </div>
</section>
<?= $this->endSection() ?>
