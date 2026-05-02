<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Libraries\AuditLogger;
use App\Models\LoginAttemptModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use DateTimeImmutable;

class LoginController extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn') && (int) session()->get('two_factor_pending') !== 1) {
            return redirect()->to($this->dashboardPathByRole((string) session()->get('role')));
        }

        return view('auth/login', [
            'pageTitle' => 'Masuk',
        ]);
    }

    public function process()
    {
        if (session()->get('isLoggedIn') && (int) session()->get('two_factor_pending') !== 1) {
            return redirect()->to($this->dashboardPathByRole((string) session()->get('role')));
        }

        $rules = [
            'email'    => 'required|valid_email|max_length[191]',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $email            = strtolower(trim((string) $this->request->getPost('email')));
        $password         = (string) $this->request->getPost('password');
        $ipAddress        = (string) $this->request->getIPAddress();
        $userAgent        = substr((string) $this->request->getUserAgent(), 0, 255);
        $loginAttemptModel = new LoginAttemptModel();
        $settingModel      = new SettingModel();
        $userModel         = new UserModel();

        $maxAttempts   = (int) $settingModel->getValue('security', 'max_login_attempts', 5);
        $baseLockout   = (int) $settingModel->getValue('security', 'lockout_minutes', 15);
        $recentFails   = $loginAttemptModel->countRecentFails($email, $ipAddress, 15);
        $totalFailsDay = $loginAttemptModel->countTotalFails($email, $ipAddress, 1440);

        if ($recentFails >= $maxAttempts) {
            $lastFailedAt = $loginAttemptModel->latestFailedAt($email, $ipAddress);
            $lockMinutes  = $this->determineLockMinutes($totalFailsDay, $baseLockout);

            if ($lastFailedAt instanceof DateTimeImmutable) {
                $remaining = $this->remainingLockMinutes($lastFailedAt, $lockMinutes);
                if ($remaining > 0) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        "Terlalu banyak percobaan login. Coba lagi dalam {$remaining} menit."
                    );
                }
            }
        }

        $user = $userModel->findByEmail($email);

        if (! is_array($user) || ! $this->isUserPasswordValid($password, $user)) {
            $loginAttemptModel->recordAttempt($email, $ipAddress, false, 'invalid_credentials', $userAgent);
            return redirect()->back()->withInput()->with('error', 'Email atau password tidak valid.');
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            $loginAttemptModel->recordAttempt($email, $ipAddress, false, 'inactive_account', $userAgent);
            return redirect()->back()->withInput()->with('error', 'Akun dinonaktifkan.');
        }

        $loginAttemptModel->recordAttempt($email, $ipAddress, true, null, $userAgent);

        $userModel->update((int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ipAddress,
        ]);

        session()->regenerate(true);
        session()->set([
            'isLoggedIn' => true,
            'user_id'    => (int) $user['id'],
            'name'       => (string) $user['name'],
            'email'      => (string) $user['email'],
            'role'       => (string) $user['role'],
            'two_factor_passed' => 1,
            'last_activity_ts' => time(),
        ]);

        if ($this->isTwoFactorSkeletonRequired($user, $settingModel)) {
            session()->set('two_factor_passed', 0);
            session()->set('two_factor_pending', 1);
            session()->set('two_factor_user_id', (int) $user['id']);
            session()->set('two_factor_email', (string) $user['email']);
            session()->set('two_factor_expires_at', date('Y-m-d H:i:s', strtotime('+10 minutes')));
            return redirect()->to('/login/2fa')->with('success', 'Masukkan kode 2FA untuk melanjutkan.');
        }

        AuditLogger::log([
            'action'   => 'auth_login',
            'model'    => 'users',
            'model_id' => (int) $user['id'],
        ]);

        return redirect()->to($this->dashboardPathByRole((string) $user['role']))->with('success', 'Login berhasil.');
    }

    public function twoFactor()
    {
        if (! session()->get('isLoggedIn') || (int) session()->get('two_factor_pending') !== 1) {
            return redirect()->to('/login');
        }

        $expiresAt = (string) (session()->get('two_factor_expires_at') ?? '');
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi 2FA kadaluarsa. Silakan login ulang.');
        }

        return view('auth/two_factor', [
            'pageTitle' => 'Verifikasi 2FA',
            'email'     => (string) session()->get('two_factor_email'),
            'expiresAt' => $expiresAt,
        ]);
    }

    public function verifyTwoFactor()
    {
        if (! session()->get('isLoggedIn') || (int) session()->get('two_factor_pending') !== 1) {
            return redirect()->to('/login');
        }

        $rules = [
            'code' => 'required|regex_match[/^[0-9]{6}$/]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $expiresAt = (string) (session()->get('two_factor_expires_at') ?? '');
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Sesi 2FA kadaluarsa. Silakan login ulang.');
        }

        $code = (string) $this->request->getPost('code');
        if (! $this->isTwoFactorCodeValid($code)) {
            return redirect()->back()->withInput()->with('error', 'Kode 2FA tidak valid.');
        }

        session()->set('two_factor_passed', 1);
        session()->set('two_factor_pending', 0);
        session()->remove('two_factor_email');
        session()->remove('two_factor_expires_at');
        session()->remove('two_factor_user_id');

        return redirect()->to($this->dashboardPathByRole((string) session()->get('role')))
            ->with('success', '2FA berhasil diverifikasi.');
    }

    public function logout()
    {
        if (session()->get('isLoggedIn')) {
            AuditLogger::log([
                'action'   => 'auth_logout',
                'model'    => 'users',
                'model_id' => (int) session()->get('user_id'),
            ]);
        }

        session()->destroy();

        return redirect()->to('/login')->with('success', 'Anda berhasil logout.');
    }

    private function determineLockMinutes(int $totalFails, int $baseLockout): int
    {
        return match (true) {
            $totalFails >= 15 => 60,
            $totalFails >= 10 => 30,
            default           => max(15, $baseLockout),
        };
    }

    private function remainingLockMinutes(DateTimeImmutable $lastFailedAt, int $lockMinutes): int
    {
        $unlockAt  = $lastFailedAt->modify("+{$lockMinutes} minutes")->getTimestamp();
        $remaining = $unlockAt - time();

        if ($remaining <= 0) {
            return 0;
        }

        return (int) ceil($remaining / 60);
    }

    private function isUserPasswordValid(string $submittedPassword, array $user): bool
    {
        $storedHash = (string) ($user['password'] ?? '');
        if ($storedHash === '') {
            return false;
        }

        return password_verify($submittedPassword, $storedHash);
    }

    private function isTwoFactorSkeletonRequired(array $user, SettingModel $settingModel): bool
    {
        $isAdmin = (string) ($user['role'] ?? '') === 'admin';
        if (! $isAdmin) {
            return false;
        }

        return (int) $settingModel->getValue('security', 'two_factor_admin', 0) === 1;
    }

    private function isTwoFactorCodeValid(string $code): bool
    {
        return $code === '000000';
    }

    private function dashboardPathByRole(string $role): string
    {
        return match ($role) {
            'admin', 'staff' => '/admin/orders',
            'viewer'         => '/admin/audit',
            default          => '/user/orders',
        };
    }
}
