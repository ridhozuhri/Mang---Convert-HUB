<?php

namespace App\Models;

use CodeIgniter\Model;

class AssetLedgerEntryModel extends Model
{
    protected $table = 'asset_ledger_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'asset_id',
        'order_id',
        'entry_type',
        'amount',
        'notes',
        'created_by',
        'created_at',
    ];
    protected $useTimestamps = false;

    public function summaryByDateRange(string $dateFrom, string $dateTo): array
    {
        return $this->select('asset_ledger_entries.asset_id, assets.code as asset_code, assets.name as asset_name, SUM(CASE WHEN entry_type = "debit" THEN amount ELSE 0 END) as total_debit, SUM(CASE WHEN entry_type = "credit" THEN amount ELSE 0 END) as total_credit')
            ->join('assets', 'assets.id = asset_ledger_entries.asset_id', 'left')
            ->where('asset_ledger_entries.created_at >=', $dateFrom . ' 00:00:00')
            ->where('asset_ledger_entries.created_at <=', $dateTo . ' 23:59:59')
            ->groupBy('asset_ledger_entries.asset_id')
            ->orderBy('assets.code', 'ASC')
            ->findAll();
    }
}
