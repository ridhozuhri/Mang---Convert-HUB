<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'ip_address',
        'user_agent',
        'is_success',
        'fail_reason',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = false;

    public function recordAttempt(
        string $email,
        string $ipAddress,
        bool $isSuccess,
        ?string $failReason = null,
        ?string $userAgent = null
    ): void {
        $this->insert([
            'email'      => strtolower(trim($email)),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_success' => $isSuccess ? 1 : 0,
            'fail_reason' => $failReason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function countRecentFails(string $email, string $ipAddress, int $minutes = 15): int
    {
        $threshold = (new DateTimeImmutable("-{$minutes} minutes"))->format('Y-m-d H:i:s');

        return $this->where('email', strtolower(trim($email)))
            ->where('ip_address', $ipAddress)
            ->where('is_success', 0)
            ->where('created_at >=', $threshold)
            ->countAllResults();
    }

    public function latestFailedAt(string $email, string $ipAddress): ?DateTimeImmutable
    {
        $row = $this->select('created_at')
            ->where('email', strtolower(trim($email)))
            ->where('ip_address', $ipAddress)
            ->where('is_success', 0)
            ->orderBy('id', 'DESC')
            ->first();

        if (! is_array($row) || empty($row['created_at'])) {
            return null;
        }

        return new DateTimeImmutable($row['created_at']);
    }

    public function countTotalFails(string $email, string $ipAddress, int $minutes = 1440): int
    {
        $threshold = (new DateTimeImmutable("-{$minutes} minutes"))->format('Y-m-d H:i:s');

        return $this->where('email', strtolower(trim($email)))
            ->where('ip_address', $ipAddress)
            ->where('is_success', 0)
            ->where('created_at >=', $threshold)
            ->countAllResults();
    }
}
