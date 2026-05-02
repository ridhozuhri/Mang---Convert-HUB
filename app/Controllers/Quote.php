<?php

namespace App\Controllers;

use App\Libraries\RateCalculator;
use App\Models\AssetModel;
use App\Models\PairModel;
use App\Models\SettingModel;

class Quote extends BaseController
{
    public function calculate()
    {
        $settingModel = new SettingModel();
        $secret = (string) $settingModel->getValue('converter', 'quote_token_secret', '');
        $expiry = (int) $settingModel->getValue('converter', 'quote_token_expiry', 300);

        if ($secret === '') {
            $secret = 'converthub-default-quote-secret';
        }

        if (! $this->verifyToken((string) $this->request->getHeaderLine('X-Quote-Token'), $secret, $expiry)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Token quote tidak valid atau kadaluarsa.',
            ]);
        }

        $rules = [
            'pair_id'     => 'required|integer',
            'amount_sent' => 'required|decimal|greater_than[0]',
        ];
        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Input quote tidak valid.',
                'errors'  => $this->validator->getErrors(),
                'next_token' => $this->generateToken($secret),
            ]);
        }

        $pairId = (int) $this->request->getPost('pair_id');
        $amountSent = (float) $this->request->getPost('amount_sent');
        $pairModel = new PairModel();
        $pair = $pairModel->findWithAssets($pairId);

        if (! is_array($pair) || ($pair['status'] ?? '') !== 'active') {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Pair tidak ditemukan atau tidak aktif.',
                'next_token' => $this->generateToken($secret),
            ]);
        }
        $blockedAssetIds = (new AssetModel())->unavailableAssetIdsByLimit();
        if (in_array((int) ($pair['to_asset_id'] ?? 0), $blockedAssetIds, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Asset tujuan sedang mencapai batas stok.',
                'next_token' => $this->generateToken($secret),
            ]);
        }

        $calculation = RateCalculator::calculate($amountSent, $pair);
        $effectiveRate = RateCalculator::resolveRate($pair);
        $inputRate = (float) ($pair['last_rate'] ?? 0);
        $rateMode = (string) ($pair['rate_mode'] ?? 'manual');
        if (($calculation['error'] ?? null) !== null) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => (string) $calculation['error'],
                'quote'   => [
                    'pair_id'      => $pairId,
                    'amount_sent'  => $amountSent,
                    'rate'         => $effectiveRate,
                    'rate_input'   => $inputRate,
                    'rate_mode'    => $rateMode,
                    'min_amount'   => (float) $pair['min_amount'],
                    'max_amount'   => (float) $pair['max_amount'],
                    'fee_type'     => $pair['fee_type'],
                    'fee_value'    => (float) $pair['fee_value'],
                    'spread_type'  => $pair['spread_type'],
                    'spread_value' => (float) $pair['spread_value'],
                    'notes'        => (string) ($pair['notes'] ?? ''),
                    'from_code'    => (string) ($pair['from_code'] ?? ''),
                    'to_code'      => (string) ($pair['to_code'] ?? ''),
                ],
                'next_token' => $this->generateToken($secret),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'quote' => [
                'pair_id'          => $pairId,
                'amount_sent'      => $amountSent,
                'amount_received'  => (float) $calculation['net'],
                'gross'            => (float) $calculation['gross'],
                'fee'              => (float) $calculation['fee'],
                'spread'           => (float) $calculation['spread'],
                'rate'             => $effectiveRate,
                'rate_input'       => $inputRate,
                'rate_mode'        => $rateMode,
                'min_amount'       => (float) $pair['min_amount'],
                'max_amount'       => (float) $pair['max_amount'],
                'fee_type'         => (string) $pair['fee_type'],
                'fee_value'        => (float) $pair['fee_value'],
                'spread_type'      => (string) $pair['spread_type'],
                'spread_value'     => (float) $pair['spread_value'],
                'notes'            => (string) ($pair['notes'] ?? ''),
                'rounding_mode'    => (string) ($pair['rounding_mode'] ?? 'floor'),
                'rounding_precision' => (int) ($pair['rounding_precision'] ?? 2),
                'from_code'        => (string) ($pair['from_code'] ?? ''),
                'to_code'          => (string) ($pair['to_code'] ?? ''),
            ],
            'next_token' => $this->generateToken($secret),
        ]);
    }

    private function generateToken(string $secret): string
    {
        $timestamp = time();
        $hmac = hash_hmac('sha256', (string) $timestamp, $secret);

        return $timestamp . '.' . $hmac;
    }

    private function verifyToken(string $token, string $secret, int $expiry): bool
    {
        if ($token === '' || ! str_contains($token, '.')) {
            return false;
        }

        [$timestamp, $hmac] = explode('.', $token, 2);
        if (! ctype_digit($timestamp) || $hmac === '') {
            return false;
        }

        $timestampInt = (int) $timestamp;
        $now = time();
        if ($timestampInt > ($now + 60)) {
            return false;
        }
        if (($now - $timestampInt) > $expiry) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp, $secret);
        return hash_equals($expected, $hmac);
    }
}
