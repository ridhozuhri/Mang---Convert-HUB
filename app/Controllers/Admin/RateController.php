<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Libraries\NotificationService;
use App\Models\PairModel;
use App\Models\PairRateHistoryModel;
use Config\Database;

class RateController extends BaseController
{
    public function index(): string
    {
        $pairModel = new PairModel();

        return view('admin/rates/index', [
            'pageTitle' => 'Rate Manager',
            'pairs'     => $pairModel->allWithAssets(),
        ]);
    }

    public function update(int $id)
    {
        $rules = [
            'new_rate' => 'required|decimal|greater_than[0]',
            'reason'   => 'required|min_length[10]|max_length[500]',
        ];
        $messages = [
            'new_rate' => [
                'required'     => 'New Rate wajib diisi.',
                'decimal'      => 'New Rate harus berupa angka desimal yang valid.',
                'greater_than' => 'New Rate harus lebih besar dari 0.',
            ],
            'reason' => [
                'required'   => 'Reason wajib diisi.',
                'min_length' => 'Reason minimal 10 karakter.',
                'max_length' => 'Reason maksimal 500 karakter.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $this->validator->getErrors(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $pairModel = new PairModel();
        $historyModel = new PairRateHistoryModel();
        $pair = $pairModel->findWithAssets($id);
        if (! is_array($pair)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Pair tidak ditemukan.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $newRate = (string) $this->request->getPost('new_rate');
        $reason  = trim((string) $this->request->getPost('reason'));
        $now     = date('Y-m-d H:i:s');
        $userId  = (int) (session()->get('user_id') ?? 0);
        $rateMode = (string) ($pair['rate_mode'] ?? 'manual');
        $fromCode = (string) ($pair['from_code'] ?? '-');
        $toCode = (string) ($pair['to_code'] ?? '-');

        $db = Database::connect();
        $db->transBegin();

        $pairModel->update($id, [
            'last_rate'            => $newRate,
            'last_rate_updated_at' => $now,
            'last_rate_updated_by' => $userId,
        ]);

        $historyModel->insert([
            'pair_id'        => $id,
            'rate'           => $newRate,
            'changed_by'     => $userId,
            'change_reason'  => $reason,
            'created_at'     => $now,
        ]);

        AuditLogger::log([
            'action'     => 'rate_update',
            'model'      => 'pairs',
            'model_id'   => $id,
            'old_values' => ['last_rate' => $pair['last_rate']],
            'new_values' => ['last_rate' => $newRate, 'reason' => $reason, 'rate_mode' => $rateMode],
        ]);

        NotificationService::sendToRoles(
            ['admin', 'staff'],
            'rate_update',
            'Rate diupdate: ' . $fromCode . ' -> ' . $toCode,
            'Rate baru: ' . $newRate . ' (' . $rateMode . ') oleh ' . (session()->get('name') ?? 'System') . '. Alasan: ' . $reason,
            [
                'pair_id'   => $id,
                'new_rate'  => $newRate,
                'reason'    => $reason,
                'rate_mode' => $rateMode,
            ]
        );

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal memperbarui rate.',
                'csrfHash' => csrf_hash(),
            ]);
        }
        $db->transCommit();

        return $this->response->setJSON([
            'success' => true,
            'new_rate' => (float) $newRate,
            'new_rate_display' => $this->formatRateForDisplay($newRate, $rateMode, $fromCode, $toCode),
            'rate_mode' => $rateMode,
            'updated_at' => $now,
            'updated_by' => (string) (session()->get('name') ?? ''),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function history(int $id): string
    {
        $range = (int) ($this->request->getGet('range') ?? 30);
        if (! in_array($range, [0, 7, 30, 90], true)) {
            $range = 30;
        }

        $pairModel = new PairModel();
        $historyModel = new PairRateHistoryModel();
        $pair = $pairModel->findWithAssets($id);

        if (! is_array($pair)) {
            return redirect()->to('/admin/rates')->with('error', 'Pair tidak ditemukan.');
        }

        $historyRows = $historyModel->getByPair($id, $range === 0 ? null : $range);

        return view('admin/rates/history', [
            'pageTitle' => 'Riwayat Rate',
            'pair'      => $pair,
            'rows'      => $historyRows,
            'range'     => $range,
        ]);
    }

    public function chartData(int $id)
    {
        $range = (int) ($this->request->getGet('range') ?? 30);
        if (! in_array($range, [0, 7, 30, 90], true)) {
            $range = 30;
        }

        $pairModel = new PairModel();
        $historyModel = new PairRateHistoryModel();
        $pair = $pairModel->findWithAssets($id);

        if (! is_array($pair)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Pair tidak ditemukan.',
            ]);
        }

        $historyRows = $historyModel->getByPair($id, $range === 0 ? null : $range);
        $historyRows = array_reverse($historyRows);

        $labels = [];
        $data = [];
        foreach ($historyRows as $row) {
            $labels[] = date('Y-m-d', strtotime((string) $row['created_at']));
            $data[] = (float) $row['rate'];
        }

        return $this->response->setJSON([
            'labels'       => $labels,
            'data'         => $data,
            'pair'         => ($pair['from_code'] ?? '-') . ' -> ' . ($pair['to_code'] ?? '-'),
            'current_rate' => (float) $pair['last_rate'],
        ]);
    }

    private function formatRateForDisplay(string $rate, string $rateMode, string $fromCode, string $toCode): string
    {
        $raw = trim($rate);
        if ($raw === '' || ! is_numeric($raw)) {
            $raw = '0';
        }

        $precision = $rateMode === 'inverse' ? 2 : 8;
        $formatted = number_format((float) $raw, $precision, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        if ($formatted === '') {
            $formatted = '0';
        }

        return $formatted;
    }
}
