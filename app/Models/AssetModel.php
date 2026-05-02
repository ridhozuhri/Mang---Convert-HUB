<?php

namespace App\Models;

use CodeIgniter\Model;

class AssetModel extends Model
{
    protected $table            = 'assets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_id',
        'code',
        'name',
        'symbol',
        'decimals',
        'logo_path',
        'status',
        'sort_order',
        'capacity_limit',
        'hide_threshold_percent',
        'is_auto_hide_enabled',
        'destination_account_type',
        'destination_account_number',
        'destination_account_name',
        'checkout_instruction',
        'metadata_json',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = true;
    protected $dateFormat             = 'datetime';
    protected $createdField           = 'created_at';
    protected $updatedField           = 'updated_at';

    protected array $casts = [
        'id'          => 'integer',
        'category_id' => 'integer',
        'decimals'    => 'integer',
        'sort_order'  => 'integer',
        'capacity_limit' => '?float',
        'hide_threshold_percent' => 'integer',
        'is_auto_hide_enabled' => 'integer',
    ];

    public function findAllWithCategory(): array
    {
        return $this->select('assets.*, categories.name as category_name')
            ->join('categories', 'categories.id = assets.category_id', 'left')
            ->orderBy('assets.sort_order', 'ASC')
            ->orderBy('assets.id', 'ASC')
            ->findAll();
    }

    public function findAllWithCategoryAndUsage(): array
    {
        $rows = $this->findAllWithCategory();
        if ($rows === []) {
            return [];
        }

        $usageMap = $this->usageMapForToAssets();
        foreach ($rows as &$row) {
            $assetId = (int) ($row['id'] ?? 0);
            $usedAmount = (float) ($usageMap[$assetId] ?? 0);
            $capacityLimit = isset($row['capacity_limit']) && is_numeric((string) $row['capacity_limit'])
                ? (float) $row['capacity_limit']
                : 0.0;
            $usagePercent = $capacityLimit > 0 ? round(($usedAmount / $capacityLimit) * 100, 2) : null;

            $row['used_amount'] = $usedAmount;
            $row['usage_percent'] = $usagePercent;
        }
        unset($row);

        return $rows;
    }

    public function usageMapForToAssets(): array
    {
        $builder = $this->db->table('orders o')
            ->select('p.to_asset_id as asset_id, SUM(o.amount_received) as used_amount')
            ->join('pairs p', 'p.id = o.pair_id', 'inner')
            ->whereIn('o.status', ['pending', 'paid', 'processing', 'completed'])
            ->groupBy('p.to_asset_id');

        $rows = $builder->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $assetId = (int) ($row['asset_id'] ?? 0);
            if ($assetId <= 0) {
                continue;
            }
            $map[$assetId] = (float) ($row['used_amount'] ?? 0);
        }

        return $map;
    }

    public function unavailableAssetIdsByLimit(): array
    {
        $rows = $this->where('status', 'active')->findAll();
        if ($rows === []) {
            return [];
        }

        $usageMap = $this->usageMapForToAssets();
        $blocked = [];

        foreach ($rows as $row) {
            $assetId = (int) ($row['id'] ?? 0);
            if ($assetId <= 0) {
                continue;
            }
            $isAutoHideEnabled = (int) ($row['is_auto_hide_enabled'] ?? 1) === 1;
            $capacityLimit = isset($row['capacity_limit']) && is_numeric((string) $row['capacity_limit'])
                ? (float) $row['capacity_limit']
                : 0.0;
            if (! $isAutoHideEnabled || $capacityLimit <= 0) {
                continue;
            }

            $threshold = (int) ($row['hide_threshold_percent'] ?? 80);
            if ($threshold < 1 || $threshold > 100) {
                $threshold = 80;
            }
            $usedAmount = (float) ($usageMap[$assetId] ?? 0);
            $usagePercent = ($usedAmount / $capacityLimit) * 100;
            if ($usagePercent >= $threshold) {
                $blocked[] = $assetId;
            }
        }

        return $blocked;
    }

    public function usageSummaryByAssetId(int $assetId): ?array
    {
        $asset = $this->find($assetId);
        if (! is_array($asset)) {
            return null;
        }

        $usageMap = $this->usageMapForToAssets();
        $usedAmount = (float) ($usageMap[$assetId] ?? 0);
        $capacityLimit = isset($asset['capacity_limit']) && is_numeric((string) $asset['capacity_limit'])
            ? (float) $asset['capacity_limit']
            : 0.0;
        $usagePercent = $capacityLimit > 0 ? round(($usedAmount / $capacityLimit) * 100, 2) : null;

        return [
            'asset_id' => $assetId,
            'used_amount' => $usedAmount,
            'capacity_limit' => $capacityLimit,
            'usage_percent' => $usagePercent,
        ];
    }
}
