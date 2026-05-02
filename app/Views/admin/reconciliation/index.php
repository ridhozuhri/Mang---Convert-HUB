<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <h1>Rekonsiliasi Asset Ledger</h1>
    </div>
    <form method="get" action="<?= esc(site_url('/admin/reconciliation')) ?>" class="card panel-box" style="margin-top:10px;">
        <div class="grid-2">
            <div class="form-group">
                <label>Dari</label>
                <input class="form-input" type="date" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input class="form-input" type="date" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-admin">
            <thead><tr><th>Asset</th><th>Total Debit</th><th>Total Credit</th><th>Net</th></tr></thead>
            <tbody>
                <?php if (($rows ?? []) === []): ?><tr><td colspan="4">Belum ada data.</td></tr><?php endif; ?>
                <?php foreach (($rows ?? []) as $row): ?>
                    <?php $debit = (float) ($row['total_debit'] ?? 0); $credit = (float) ($row['total_credit'] ?? 0); ?>
                    <tr>
                        <td><span class="mono"><?= esc((string) ($row['asset_code'] ?? '-')) ?></span> - <?= esc((string) ($row['asset_name'] ?? '-')) ?></td>
                        <td class="mono"><?= esc(number_format($debit, 8, '.', ',')) ?></td>
                        <td class="mono"><?= esc(number_format($credit, 8, '.', ',')) ?></td>
                        <td class="mono"><?= esc(number_format($credit - $debit, 8, '.', ',')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
