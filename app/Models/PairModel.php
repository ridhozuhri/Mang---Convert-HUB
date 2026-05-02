<?php

namespace App\Models;

use CodeIgniter\Model;

class PairModel extends Model
{
    protected $table            = 'pairs';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'from_asset_id',
        'to_asset_id',
        'status',
        'sort_order',
        'min_amount',
        'max_amount',
        'fee_type',
        'fee_value',
        'spread_type',
        'spread_value',
        'rounding_mode',
        'rounding_precision',
        'rate_mode',
        'last_rate',
        'last_rate_updated_at',
        'last_rate_updated_by',
        'notes',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = true;
    protected $dateFormat             = 'datetime';
    protected $createdField           = 'created_at';
    protected $updatedField           = 'updated_at';

    public function allWithAssets(): array
    {
        return $this->select('pairs.*, fa.code as from_code, fa.name as from_name, ta.code as to_code, ta.name as to_name, fc.slug as from_category_slug, u.name as updated_by_name, ta.capacity_limit as to_capacity_limit, ta.hide_threshold_percent as to_hide_threshold_percent, ta.is_auto_hide_enabled as to_is_auto_hide_enabled, ta.destination_account_type as to_destination_account_type, ta.destination_account_number as to_destination_account_number, ta.destination_account_name as to_destination_account_name, ta.checkout_instruction as to_checkout_instruction')
            ->join('assets fa', 'fa.id = pairs.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = pairs.to_asset_id', 'left')
            ->join('categories fc', 'fc.id = fa.category_id', 'left')
            ->join('users u', 'u.id = pairs.last_rate_updated_by', 'left')
            ->orderBy('pairs.sort_order', 'ASC')
            ->orderBy('pairs.id', 'ASC')
            ->findAll();
    }

    public function activeForConverter(): array
    {
        return $this->select('pairs.*, fa.code as from_code, fa.name as from_name, ta.code as to_code, ta.name as to_name, fc.slug as from_category_slug, u.name as updated_by_name, ta.capacity_limit as to_capacity_limit, ta.hide_threshold_percent as to_hide_threshold_percent, ta.is_auto_hide_enabled as to_is_auto_hide_enabled, ta.destination_account_type as to_destination_account_type, ta.destination_account_number as to_destination_account_number, ta.destination_account_name as to_destination_account_name, ta.checkout_instruction as to_checkout_instruction')
            ->join('assets fa', 'fa.id = pairs.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = pairs.to_asset_id', 'left')
            ->join('categories fc', 'fc.id = fa.category_id', 'left')
            ->join('users u', 'u.id = pairs.last_rate_updated_by', 'left')
            ->where('pairs.status', 'active')
            ->where('fa.status', 'active')
            ->where('ta.status', 'active')
            ->orderBy('pairs.sort_order', 'ASC')
            ->orderBy('pairs.id', 'ASC')
            ->findAll();
    }

    public function findWithAssets(int $id): ?array
    {
        $row = $this->select('pairs.*, fa.code as from_code, fa.name as from_name, ta.code as to_code, ta.name as to_name, fc.slug as from_category_slug, u.name as updated_by_name, ta.capacity_limit as to_capacity_limit, ta.hide_threshold_percent as to_hide_threshold_percent, ta.is_auto_hide_enabled as to_is_auto_hide_enabled, ta.destination_account_type as to_destination_account_type, ta.destination_account_number as to_destination_account_number, ta.destination_account_name as to_destination_account_name, ta.checkout_instruction as to_checkout_instruction')
            ->join('assets fa', 'fa.id = pairs.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = pairs.to_asset_id', 'left')
            ->join('categories fc', 'fc.id = fa.category_id', 'left')
            ->join('users u', 'u.id = pairs.last_rate_updated_by', 'left')
            ->where('pairs.id', $id)
            ->first();

        return is_array($row) ? $row : null;
    }
}
