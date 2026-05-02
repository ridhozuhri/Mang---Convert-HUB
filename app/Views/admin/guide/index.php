<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <h1>Guide Admin</h1>
    <p class="text-muted">Petunjuk fungsi menu admin dan contoh alur penggunaan fitur utama.</p>

    <div class="card panel-box">
        <h3>Fungsi Tiap Menu</h3>
        <div class="table-wrap">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>URL</th>
                        <th>Fungsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menus as $menu): ?>
                        <tr>
                            <td><strong><?= esc((string) $menu['name']) ?></strong></td>
                            <td class="mono"><?= esc((string) $menu['path']) ?></td>
                            <td><?= esc((string) $menu['function']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid-2">
        <?php foreach ($examples as $example): ?>
            <div class="card panel-box">
                <h3><?= esc((string) $example['title']) ?></h3>
                <ol class="guide-steps">
                    <?php foreach ($example['steps'] as $step): ?>
                        <li><?= esc((string) $step) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card panel-box">
        <h3>Contoh Pengisian Form per Menu</h3>
        <p class="text-muted">Gunakan contoh ini sebagai acuan awal saat input data di dashboard admin.</p>
        <div class="grid-2">
            <?php foreach ($fillSamples as $sample): ?>
                <div class="guide-sample-card">
                    <div class="page-head">
                        <strong><?= esc((string) $sample['title']) ?></strong>
                        <span class="mono"><?= esc((string) $sample['menu']) ?></span>
                    </div>
                    <div class="table-wrap">
                        <table class="table-admin">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Contoh Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sample['fields'] as $field => $value): ?>
                                    <tr>
                                        <td class="mono"><?= esc((string) $field) ?></td>
                                        <td><?= esc((string) $value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card panel-box">
        <h3>Penjelasan Field Pair (Service)</h3>
        <div class="table-wrap">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Penjelasan</th>
                        <th>Contoh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pairFieldGuide as $item): ?>
                        <tr>
                            <td class="mono"><?= esc((string) $item['field']) ?></td>
                            <td><?= esc((string) $item['description']) ?></td>
                            <td><?= esc((string) $item['example']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
