<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code',
        'user_id',
        'pair_id',
        'payment_method_id',
        'idempotency_key',
        'service_rules_snapshot',
        'customer_sender_account',
        'snap_rate',
        'snap_fee_type',
        'snap_fee_value',
        'snap_spread_type',
        'snap_spread_value',
        'snap_min_amount',
        'snap_max_amount',
        'amount_sent',
        'amount_received',
        'status',
        'proof_path',
        'proof_uploaded_at',
        'proof_status',
        'proof_reviewed_at',
        'proof_reviewed_by',
        'admin_note',
        'cancelled_reason',
        'expires_at',
        'completed_at',
        'locked_by',
        'locked_at',
        'sla_due_at',
        'sla_escalated_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = true;
    protected $dateFormat             = 'datetime';
    protected $createdField           = 'created_at';
    protected $updatedField           = 'updated_at';

    public function listWithRelations(array $filters = []): array
    {
        $builder = $this->select('orders.*, users.name as user_name, users.email as user_email, fa.code as from_code, ta.code as to_code, payment_methods.name as payment_method_name, locker.name as locked_by_name')
            ->join('users', 'users.id = orders.user_id', 'left')
            ->join('pairs', 'pairs.id = orders.pair_id', 'left')
            ->join('assets fa', 'fa.id = pairs.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = pairs.to_asset_id', 'left')
            ->join('payment_methods', 'payment_methods.id = orders.payment_method_id', 'left')
            ->join('users locker', 'locker.id = orders.locked_by', 'left')
            ->orderBy('orders.id', 'DESC');

        if (($filters['status'] ?? '') !== '') {
            $builder->where('orders.status', $filters['status']);
        }
        if (($filters['code'] ?? '') !== '') {
            $builder->like('orders.code', $filters['code']);
        }
        if (($filters['user_email'] ?? '') !== '') {
            $builder->like('users.email', $filters['user_email']);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $builder->where('orders.created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if (($filters['date_to'] ?? '') !== '') {
            $builder->where('orders.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        return $builder->findAll();
    }

    public function findByCode(string $code): ?array
    {
        $row = $this->select('orders.*, pairs.to_asset_id as to_asset_id, users.name as user_name, users.email as user_email, fa.code as from_code, ta.code as to_code, payment_methods.name as payment_method_name, locker.name as locked_by_name')
            ->join('users', 'users.id = orders.user_id', 'left')
            ->join('pairs', 'pairs.id = orders.pair_id', 'left')
            ->join('assets fa', 'fa.id = pairs.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = pairs.to_asset_id', 'left')
            ->join('payment_methods', 'payment_methods.id = orders.payment_method_id', 'left')
            ->join('users locker', 'locker.id = orders.locked_by', 'left')
            ->where('orders.code', $code)
            ->first();

        return is_array($row) ? $row : null;
    }
}
