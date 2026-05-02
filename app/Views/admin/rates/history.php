<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <?php
    $formatRate = static function ($value): string {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '0';
        }
        if (str_contains($raw, '.')) {
            $raw = rtrim(rtrim($raw, '0'), '.');
        }
        return $raw === '' ? '0' : $raw;
    };
    ?>
    <div class="page-head">
        <h1>Riwayat Rate: <span class="mono"><?= esc(($pair['from_code'] ?? '-') . ' → ' . ($pair['to_code'] ?? '-')) ?></span></h1>
        <a class="btn" href="<?= esc(site_url('/admin/rates')) ?>">Kembali</a>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 10px;">
        <?php foreach ([7, 30, 90, 0] as $item): ?>
            <a
                class="btn <?= (int) $range === $item ? 'btn-primary' : 'btn-ghost' ?>"
                href="<?= esc(site_url('/admin/rates/' . $pair['id'] . '/history?range=' . $item)) ?>"
            ><?= esc($item === 0 ? 'All' : (string) $item . ' hari') ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card panel-box">
        <canvas
            id="rateChart"
            height="100"
            data-chart-url="<?= esc(site_url('/admin/rates/' . $pair['id'] . '/chart-data?range=' . (int) $range)) ?>"
        ></canvas>
    </div>

    <div class="table-wrap">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Rate</th>
                    <th>Changed By</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="4">Belum ada riwayat rate.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="mono"><?= esc((string) $row['created_at']) ?></td>
                        <td class="mono"><?= esc($formatRate($row['rate'] ?? '0')) ?></td>
                        <td><?= esc((string) ($row['changed_by_name'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['change_reason'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.min.js"></script>
<script defer src="<?= esc(base_url('assets/js/admin-rate-history.js')) ?>"></script>
<?= $this->endSection() ?>
