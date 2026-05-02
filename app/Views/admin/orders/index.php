<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Orders</h1>
        <a class="btn" href="<?= esc(site_url('/admin/orders/export-csv?' . http_build_query($filters))) ?>">Export CSV</a>
    </div>

    <form method="get" action="<?= esc(site_url('/admin/orders')) ?>" class="card panel-box" style="margin-top:16px;">
        <div class="grid-2">
            <div class="form-group">
                <label for="status">Status</label>
                <?php $status = (string) ($filters['status'] ?? ''); ?>
                <select id="status" class="form-input" name="status">
                    <option value="">Semua</option>
                    <?php foreach (['pending','paid','processing','completed','cancelled','failed'] as $item): ?>
                        <option value="<?= esc($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="code">Order Code</label>
                <input id="code" class="form-input mono" type="text" name="code" value="<?= esc((string) ($filters['code'] ?? '')) ?>">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="user_email">User Email</label>
                <input id="user_email" class="form-input" type="text" name="user_email" value="<?= esc((string) ($filters['user_email'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="proof_status">Proof Status</label>
                <?php $proofStatus = (string) ($filters['proof_status'] ?? ''); ?>
                <select id="proof_status" class="form-input" name="proof_status">
                    <option value="">Semua</option>
                    <?php foreach (['none','pending_review','verified','rejected'] as $item): ?>
                        <option value="<?= esc($item) ?>" <?= $proofStatus === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="sla_state">SLA State</label>
                <?php $slaState = (string) ($filters['sla_state'] ?? ''); ?>
                <select id="sla_state" class="form-input" name="sla_state">
                    <option value="">Semua</option>
                    <option value="ontrack" <?= $slaState === 'ontrack' ? 'selected' : '' ?>>On Track</option>
                    <option value="warning" <?= $slaState === 'warning' ? 'selected' : '' ?>>Warning (&lt;=5m)</option>
                    <option value="overdue" <?= $slaState === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="none" <?= $slaState === 'none' ? 'selected' : '' ?>>No SLA</option>
                </select>
            </div>
            <div class="form-group">
                <label>Range Tanggal</label>
                <div style="display:flex;gap:8px;">
                    <input class="form-input" type="date" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
                    <input class="form-input" type="date" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>

    <form method="post" action="<?= esc(site_url('/admin/orders/bulk-action')) ?>" class="table-wrap" data-once-submit>
        <?= csrf_field() ?>
        <div class="orders-bulkbar">
            <select class="form-input" name="bulk_action" style="max-width:220px;">
                <option value="">Bulk Action</option>
                <option value="approve">Approve (paid)</option>
                <option value="process">Process</option>
                <option value="complete">Complete</option>
                <option value="reject">Reject (failed)</option>
                <option value="lock">Lock</option>
                <option value="unlock">Unlock</option>
            </select>
            <input class="form-input" type="text" name="admin_note" placeholder="Admin note bulk (opsional)">
            <button class="btn btn-primary" type="submit">Jalankan</button>
        </div>

        <table class="table-admin">
            <thead>
                <tr>
                    <th><input type="checkbox" data-select-all-orders></th>
                    <th>Code</th>
                    <th>User</th>
                    <th>Pair</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>SLA</th>
                    <th>Lock</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="9">Belum ada order.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $rowStatus = (string) ($row['status'] ?? 'pending');
                    $statusClass = match ($rowStatus) {
                        'pending'    => 'badge-warning',
                        'paid'       => 'badge-info',
                        'processing' => 'badge-info',
                        'completed'  => 'badge-success',
                        'cancelled'  => 'badge-danger',
                        'failed'     => 'badge-danger',
                        default      => '',
                    };
                    $slaDueAt = (string) ($row['sla_due_at'] ?? '');
                    $slaSeconds = $slaDueAt !== '' ? strtotime($slaDueAt) - time() : null;
                    $slaClass = 'badge-info';
                    if ($slaSeconds !== null && $slaSeconds < 0) {
                        $slaClass = 'badge-danger';
                    } elseif ($slaSeconds !== null && $slaSeconds <= 300) {
                        $slaClass = 'badge-warning';
                    }
                    $lockedBy = (int) ($row['locked_by'] ?? 0);
                    $lockedByName = (string) ($row['locked_by_name'] ?? '');
                    $proofStatusRow = (string) ($row['proof_status'] ?? 'none');
                    $proofClass = match ($proofStatusRow) {
                        'pending_review' => 'badge-warning',
                        'verified' => 'badge-success',
                        'rejected' => 'badge-danger',
                        default => 'badge-info',
                    };
                    ?>
                    <tr>
                        <td><input class="order-checkbox" type="checkbox" name="codes[]" value="<?= esc((string) $row['code']) ?>"></td>
                        <td class="mono"><?= esc((string) $row['code']) ?></td>
                        <td><?= esc((string) (($row['user_name'] ?? '-') . ' (' . ($row['user_email'] ?? '-') . ')')) ?></td>
                        <td class="mono orders-pair-cell"><?= esc((string) (($row['from_code'] ?? '-') . ' -> ' . ($row['to_code'] ?? '-'))) ?></td>
                        <td class="mono orders-amount-cell"><?= esc((string) $row['amount_sent']) ?> / <?= esc((string) $row['amount_received']) ?></td>
                        <td>
                            <span class="badge <?= esc($statusClass) ?>"><?= esc($rowStatus) ?></span>
                            <span class="badge <?= esc($proofClass) ?>" style="margin-left:6px;"><?= esc($proofStatusRow) ?></span>
                        </td>
                        <td>
                            <?php if ($slaSeconds === null): ?>
                                <span class="text-muted">-</span>
                            <?php else: ?>
                                <span class="badge <?= esc($slaClass) ?> orders-sla-badge" data-sla-seconds="<?= esc((string) $slaSeconds) ?>">--:--</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($lockedBy > 0): ?>
                                <span class="badge badge-warning"><?= esc($lockedByName !== '' ? $lockedByName : 'Locked') ?></span>
                                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/unlock')) ?>" style="display:inline-block;margin-left:6px;" data-once-submit>
                                    <?= csrf_field() ?>
                                    <button class="btn" type="submit">Unlock</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/lock')) ?>" style="display:inline-block;" data-once-submit>
                                    <?= csrf_field() ?>
                                    <button class="btn" type="submit">Lock</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="orders-action-cell">
                            <a href="<?= esc(site_url('/admin/orders/' . $row['code'])) ?>">Detail</a>
                            <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" class="orders-inline-form" data-once-submit>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <button class="btn" type="submit">Approve</button>
                            </form>
                            <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" class="orders-inline-form" data-once-submit>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="process">
                                <button class="btn" type="submit">Process</button>
                            </form>
                            <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" class="orders-inline-form" data-once-submit>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="complete">
                                <button class="btn" type="submit">Complete</button>
                            </form>
                            <form method="post" action="<?= esc(site_url('/admin/orders/' . $row['code'] . '/quick-action')) ?>" class="orders-inline-form" data-once-submit>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="admin_note" value="Ditolak via quick action">
                                <button class="btn btn-danger" type="submit">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</section>
<script>
document.querySelectorAll('[data-sla-seconds]').forEach((element) => {
    let remaining = Number(element.getAttribute('data-sla-seconds') || 0);
    const render = () => {
        const sign = remaining < 0 ? '-' : '';
        const abs = Math.abs(remaining);
        const minutes = Math.floor(abs / 60).toString().padStart(2, '0');
        const seconds = Math.floor(abs % 60).toString().padStart(2, '0');
        element.textContent = sign + minutes + ':' + seconds;
        remaining -= 1;
    };
    render();
    setInterval(render, 1000);
});
</script>
<?= $this->endSection() ?>
