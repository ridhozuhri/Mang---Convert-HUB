<?php

namespace App\Controllers;

use App\Libraries\ServiceInstructionResolver;
use App\Models\CategoryModel;
use App\Models\AssetModel;
use App\Models\PairModel;
use App\Models\PairPaymentMethodModel;
use App\Models\PaymentMethodModel;
use App\Models\SettingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $categoryModel = new CategoryModel();
        $pairModel     = new PairModel();
        $assetModel    = new AssetModel();
        $paymentMethodModel = new PaymentMethodModel();
        $pairPaymentMethodModel = new PairPaymentMethodModel();
        $settingModel  = new SettingModel();

        $secret = (string) $settingModel->getValue('converter', 'quote_token_secret', '');
        if ($secret === '') {
            $secret = 'converthub-default-quote-secret';
        }
        $expiry = (int) $settingModel->getValue('converter', 'quote_token_expiry', 300);
        $token  = $this->generateQuoteToken($secret);

        $categories = $categoryModel
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        $pairs = $pairModel->activeForConverter();
        $blockedAssetIds = $assetModel->unavailableAssetIdsByLimit();
        if ($blockedAssetIds !== []) {
            $pairs = array_values(array_filter($pairs, static function (array $pair) use ($blockedAssetIds): bool {
                return ! in_array((int) ($pair['to_asset_id'] ?? 0), $blockedAssetIds, true);
            }));
        }
        $paymentMethods = $paymentMethodModel->active();
        $pairMethodRows = $pairPaymentMethodModel
            ->where('is_active', 1)
            ->findAll();

        $pairMethodMap = [];
        $pairMethodRules = [];
        foreach ($pairMethodRows as $row) {
            $pairId = (int) ($row['pair_id'] ?? 0);
            $methodId = (int) ($row['payment_method_id'] ?? 0);
            if ($pairId <= 0 || $methodId <= 0) {
                continue;
            }
            if (! isset($pairMethodMap[$pairId])) {
                $pairMethodMap[$pairId] = [];
            }
            $pairMethodMap[$pairId][] = $methodId;

            $decodedRules = json_decode((string) ($row['rules_json'] ?? ''), true);
            $pairRow = null;
            foreach ($pairs as $pairCandidate) {
                if ((int) ($pairCandidate['id'] ?? 0) === $pairId) {
                    $pairRow = $pairCandidate;
                    break;
                }
            }
            if (! isset($pairMethodRules[$pairId])) {
                $pairMethodRules[$pairId] = [];
            }
            $pairMethodRules[$pairId][$methodId] = is_array($decodedRules)
                ? ServiceInstructionResolver::resolve(is_array($pairRow) ? $pairRow : [], $decodedRules)
                : [];
        }

        return view('home/index', [
            'pageTitle' => (string) $settingModel->getValue('general', 'site_name', 'ConvertHub'),
            'categories' => $categories,
            'pairs'      => $pairs,
            'paymentMethods' => $paymentMethods,
            'pairMethodMap' => $pairMethodMap,
            'pairMethodRules' => $pairMethodRules,
            'quoteToken' => $token,
            'quoteTokenExpiry' => $expiry,
            'stats' => [
                'active_pairs' => count($pairs),
                'categories'   => count($categories),
                'quote_expiry' => $expiry,
            ],
        ]);
    }

    public function maintenance(): string
    {
        $settingModel = new SettingModel();
        $message      = (string) $settingModel->getValue(
            'system',
            'maintenance_message',
            'Sistem sedang dalam pemeliharaan. Coba lagi nanti.'
        );

        return view('home/maintenance', [
            'pageTitle' => 'Maintenance',
            'message'   => $message,
        ]);
    }

    private function generateQuoteToken(string $secret): string
    {
        $timestamp = time();
        $hmac      = hash_hmac('sha256', (string) $timestamp, $secret);

        return $timestamp . '.' . $hmac;
    }
}
