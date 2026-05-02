<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<meta name="qt" content="<?= esc($quoteToken ?? '') ?>">
<meta name="qt-expiry" content="<?= esc((string) ($quoteTokenExpiry ?? 300)) ?>">
<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$categoriesJson = json_encode($categories ?? [], $jsonFlags);
$pairsJson = json_encode($pairs ?? [], $jsonFlags);
$paymentMethodsJson = json_encode($paymentMethods ?? [], $jsonFlags);
$pairMethodMapJson = json_encode($pairMethodMap ?? [], $jsonFlags);
$pairMethodRulesJson = json_encode($pairMethodRules ?? [], $jsonFlags);
$categoriesJson = $categoriesJson !== false ? $categoriesJson : '[]';
$pairsJson = $pairsJson !== false ? $pairsJson : '[]';
$paymentMethodsJson = $paymentMethodsJson !== false ? $paymentMethodsJson : '[]';
$pairMethodMapJson = $pairMethodMapJson !== false ? $pairMethodMapJson : '{}';
$pairMethodRulesJson = $pairMethodRulesJson !== false ? $pairMethodRulesJson : '{}';
?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section
    id="converter-root"
    class="public-shell"
    data-quote-url="<?= esc(site_url('/quote/calculate'), 'attr') ?>"
    data-categories="<?= esc($categoriesJson, 'attr') ?>"
    data-pairs="<?= esc($pairsJson, 'attr') ?>"
    data-payment-methods="<?= esc($paymentMethodsJson, 'attr') ?>"
    data-pair-method-map="<?= esc($pairMethodMapJson, 'attr') ?>"
    data-pair-method-rules="<?= esc($pairMethodRulesJson, 'attr') ?>"
    data-initial-token="<?= esc($quoteToken ?? '', 'attr') ?>"
    x-data="converterPublic"
    x-init="init()"
>
    <section class="hero">
        <div class="hero-badge"><span class="dot"></span>Jam Operasional Senin - Jumaat 09 : 30 - 18 : 00 WIB</div>
        <h1>Tukar Aset dengan <span>Flow Jual ke Beli</span></h1>
        <p>Pilih mata uang di sisi kiri (Jual) dan sisi kanan (Beli), lalu lanjutkan transaksi.</p>
    </section>

    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-num"><?= esc((string) ($stats['active_pairs'] ?? 0)) ?></span>
            <span class="stat-label">Pasangan Aktif</span>
        </div>
        <div class="stat-item">
            <span class="stat-num"><?= esc((string) ($stats['categories'] ?? 0)) ?></span>
            <span class="stat-label">Kategori</span>
        </div>
        <div class="stat-item">
            <span class="stat-num secure-icon" aria-hidden="true">🛡︎</span>
            <span class="stat-label">Secure Payment</span>
        </div>
    </div>

    <div class="category-bar">
        <div class="category-scroll">
            <template x-for="category in categories" :key="category.id">
                <button
                    type="button"
                    class="cat-tab"
                    :class="{ 'active': activeCategory === category.slug }"
                    @click="changeCategory(category.slug)"
                    x-text="category.name"
                ></button>
            </template>
        </div>
    </div>

    <div class="converter-wrap">
        <div class="converter-card">
            <div class="converter-grid">
                <div class="panel panel-left">
                    <div class="panel-label">
                        <span class="panel-label-dot"></span>
                        Jual
                    </div>

                    <div class="asset-list">
                        <template x-for="asset in filteredSellAssets" :key="'sell-' + asset.code">
                            <button
                                type="button"
                                class="asset-list-item"
                                :class="{ 'selected': asset.code == selectedSellCode }"
                                @click="setSellAsset(asset.code)"
                            >
                                <div class="list-asset-info">
                                    <div class="list-asset-code" x-text="asset.code"></div>
                                    <div class="list-asset-name" x-text="asset.name"></div>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="amount-wrap">
                        <div class="amount-label-row">
                            <span>Jumlah Jual</span>
                        </div>
                        <div class="amount-input-row">
                            <input
                                class="amount-input mono"
                                type="number"
                                id="amountInput"
                                placeholder="0"
                                x-model="amountSent"
                                @input="scheduleQuote"
                            >
                            <span class="amount-suffix" x-text="selectedSellCode || '-'"></span>
                        </div>
                        <div class="amount-quick">
                            <button type="button" class="quick-btn" @click="setAmount(100000)">100K</button>
                            <button type="button" class="quick-btn" @click="setAmount(250000)">250K</button>
                            <button type="button" class="quick-btn" @click="setAmount(500000)">500K</button>
                            <button type="button" class="quick-btn" @click="setAmount(1000000)">1JT</button>
                        </div>
                    </div>

                    <div class="guide-card danger">
                        <h4>Penting</h4>
                        <p>Gunakan rekening/akun atas nama sendiri agar transaksi diproses lebih cepat.</p>
                    </div>
                </div>

                <div class="swap-divider">
                    <button class="swap-btn" type="button" @click="swapDirection()">&#8646;</button>
                </div>

                <div class="panel panel-right receive-panel">
                    <div class="panel-label">
                        <span class="panel-label-dot receive"></span>
                        Beli
                    </div>

                    <div class="asset-list">
                        <template x-for="asset in buyAssets" :key="'buy-' + asset.code">
                            <button
                                type="button"
                                class="asset-list-item"
                                :class="{ 'selected': asset.code == selectedBuyCode }"
                                @click="setBuyAsset(asset.code)"
                            >
                                <div class="list-asset-info">
                                    <div class="list-asset-code" x-text="asset.code"></div>
                                    <div class="list-asset-name" x-text="asset.name"></div>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="service-list-wrap">
                        <div class="service-list-head">
                            <h4>Service Pembayaran</h4>
                            <span class="text-muted" x-text="'Tersedia: ' + methodsForSelectedPair.length"></span>
                        </div>
                        <div class="service-list">
                            <template x-for="method in methodsForSelectedPair.slice(0, 8)" :key="'method-' + method.id">
                                <button
                                    type="button"
                                    class="service-item"
                                    :class="{ 'selected': method.id == selectedMethodId }"
                                    @click="selectMethod(method.id)"
                                >
                                    <div class="service-item-name" x-text="method.name"></div>
                                    <div class="service-item-type" x-text="method.type"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="receive-amount-box">
                        <div class="receive-label">Estimasi diterima</div>
                        <div class="receive-value mono" x-text="formatNumber(quote.amount_received)"></div>
                        <div class="receive-usd-equiv mono" x-text="selectedBuyCode || '-'"></div>
                    </div>
                </div>
            </div>

            <div class="summary-panel">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-item-label">Rate</div>
                        <div class="summary-item-value mono" x-text="formatNumber(quote.rate)"></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-item-label">Fee</div>
                        <div class="summary-item-value mono" x-text="formatNumber(quote.fee)"></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-item-label">Min</div>
                        <div class="summary-item-value mono" x-text="formatNumber(quote.min_amount)"></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-item-label">Max</div>
                        <div class="summary-item-value mono" x-text="formatNumber(quote.max_amount)"></div>
                    </div>
                </div>

                <div class="progress-bar-wrap">
                    <div class="progress-bar" :style="{ width: progressPercent() + '%' }"></div>
                </div>

                <template x-if="selectedPair">
                    <div class="summary-note">
                        Pair aktif: <strong x-text="selectedPair.from_code + ' -> ' + selectedPair.to_code"></strong>
                    </div>
                </template>
                <template x-if="selectedMethod">
                    <div class="summary-note">
                        <strong x-text="selectedMethod.name"></strong>
                        <div x-text="selectedMethod.instructions || 'Ikuti instruksi pembayaran sesuai detail order.'"></div>
                        <template x-if="effectiveDestinationNumber">
                            <div class="destination-row">
                                <span x-text="effectiveDestinationLabel"></span>:
                                <span class="mono" x-text="effectiveDestinationNumber"></span>
                                <button type="button" class="btn btn-copy-inline" @click="copyText(effectiveDestinationNumber)">Copy</button>
                                <span
                                    x-show="effectiveDestinationName"
                                    x-text="' (' + effectiveDestinationName + ')'"
                                ></span>
                            </div>
                        </template>
                        <template x-if="selectedPair.to_checkout_instruction">
                            <div style="margin-top:6px" x-text="selectedPair.to_checkout_instruction"></div>
                        </template>
                        <template x-if="selectedMethodRules.note_to_customer">
                            <div style="margin-top:6px" x-text="selectedMethodRules.note_to_customer"></div>
                        </template>
                    </div>
                </template>
                <template x-if="warning">
                    <div class="alert-flash alert-danger" x-text="warning"></div>
                </template>
                <template x-if="quote.notes">
                    <div class="summary-note" x-text="quote.notes"></div>
                </template>

                <div class="howto-box">
                    <h4>Alur transaksi</h4>
                    <ol>
                        <li>Pilih currency di sisi Jual dan Beli.</li>
                        <li>Input nominal Jual, cek rate dan estimasi terima.</li>
                        <li>Pilih service, buat order, lalu upload bukti pada detail order.</li>
                    </ol>
                </div>

                <?php if (! session()->get('isLoggedIn')): ?>
                    <a class="btn-cta" href="<?= esc(site_url('/login')) ?>">Masuk untuk Buat Order</a>
                <?php else: ?>
                    <form method="post" action="<?= esc(site_url('/user/orders')) ?>" data-once-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="pair_id" :value="selectedPairId">
                        <input type="hidden" name="amount_sent" :value="amountSent">
                        <input type="hidden" name="payment_method_id" :value="selectedMethodId">
                        <input type="hidden" name="idempotency_key" :value="idempotencyKey">
                        <template x-if="selectedMethodRules.require_sender_account">
                            <div class="form-group" style="margin-top:10px">
                                <label x-text="selectedMethodRules.sender_label || 'Nomor Pengirim'"></label>
                                <input class="form-input" type="text" x-model="senderAccount" name="customer_sender_account" required>
                            </div>
                        </template>
                        <button class="btn-cta" type="submit" :disabled="!selectedPairId || !amountSent || Number(amountSent) <= 0">Buat Order Sekarang</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
