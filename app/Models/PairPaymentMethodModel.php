<?php

namespace App\Models;

use CodeIgniter\Model;

class PairPaymentMethodModel extends Model
{
    protected $table            = 'pair_payment_methods';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pair_id',
        'payment_method_id',
        'rules_json',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = false;

    public function activeMethodIdsByPair(int $pairId): array
    {
        $rows = $this->select('payment_method_id')
            ->where('pair_id', $pairId)
            ->where('is_active', 1)
            ->findAll();

        return array_map(static fn(array $row): int => (int) $row['payment_method_id'], $rows);
    }

    public function findActiveRule(int $pairId, int $paymentMethodId): ?array
    {
        $row = $this->where('pair_id', $pairId)
            ->where('payment_method_id', $paymentMethodId)
            ->where('is_active', 1)
            ->first();

        return is_array($row) ? $row : null;
    }
}
