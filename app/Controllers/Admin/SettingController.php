<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    public function index(): string
    {
        $model = new SettingModel();
        $rows  = $model->orderBy('group', 'ASC')->orderBy('key', 'ASC')->findAll();
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['group']][] = $row;
        }

        return view('admin/settings/index', [
            'pageTitle' => 'Settings',
            'groups'    => $groups,
        ]);
    }

    public function update()
    {
        $posted = $this->request->getPost('settings');
        if (! is_array($posted)) {
            return redirect()->back()->with('error', 'Data settings tidak valid.');
        }

        $model = new SettingModel();
        $errors = [];
        foreach ($posted as $group => $pairs) {
            if (! is_array($pairs)) {
                continue;
            }
            foreach ($pairs as $key => $value) {
                $normalized = $this->normalizeSettingValue((string) $group, (string) $key, $value);
                if ($normalized === null) {
                    $errors[] = "Nilai {$group}.{$key} tidak valid.";
                    continue;
                }
                $model->setValue((string) $group, (string) $key, $normalized);
            }
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', $errors);
        }

        AuditLogger::log([
            'action'     => 'settings_update',
            'model'      => 'settings',
            'new_values' => $posted,
        ]);

        return redirect()->back()->with('success', 'Settings berhasil diperbarui.');
    }

    public function toggleMaintenance()
    {
        $enable = (string) ($this->request->getPost('enable') ?? '0');
        $model  = new SettingModel();
        $model->setValue('system', 'maintenance_mode', $enable === '1' ? '1' : '0');

        AuditLogger::log([
            'action'     => 'maintenance_toggle',
            'model'      => 'settings',
            'new_values' => ['maintenance_mode' => $enable === '1' ? '1' : '0'],
        ]);

        return redirect()->back()->with('success', 'Maintenance mode diperbarui.');
    }

    private function normalizeSettingValue(string $group, string $key, mixed $value): mixed
    {
        $stringValue = trim((string) $value);

        if ($group === 'security') {
            return match ($key) {
                'max_login_attempts' => $this->normalizeIntInRange($stringValue, 1, 20),
                'lockout_minutes'    => $this->normalizeIntInRange($stringValue, 1, 240),
                'session_timeout'    => $this->normalizeIntInRange($stringValue, 5, 1440),
                'two_factor_admin'   => $this->normalizeBoolFlag($stringValue),
                default              => $stringValue,
            };
        }

        if ($group === 'system') {
            return match ($key) {
                'maintenance_mode'    => $this->normalizeBoolFlag($stringValue),
                'maintenance_message' => $this->normalizeText($stringValue, 5, 500),
                default               => $stringValue,
            };
        }

        return $stringValue;
    }

    private function normalizeIntInRange(string $value, int $min, int $max): ?string
    {
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }
        $number = (int) $value;
        if ($number < $min || $number > $max) {
            return null;
        }
        return (string) $number;
    }

    private function normalizeBoolFlag(string $value): ?string
    {
        return in_array($value, ['0', '1'], true) ? $value : null;
    }

    private function normalizeText(string $value, int $minLength, int $maxLength): ?string
    {
        $length = mb_strlen($value);
        if ($length < $minLength || $length > $maxLength) {
            return null;
        }
        return $value;
    }
}
