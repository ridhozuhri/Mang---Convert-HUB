<?php

namespace App\Filters;

use App\Models\SettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $scope = is_array($arguments) && isset($arguments[0]) ? (string) $arguments[0] : 'default';
        $ip = (string) $request->getIPAddress();

        $settingModel = new SettingModel();
        $defaultLimit = $scope === 'quote' ? 60 : 25;
        $defaultWindow = $scope === 'quote' ? 60 : 900;

        $limit = (int) $settingModel->getValue('security', 'rate_limit_' . $scope . '_max', $defaultLimit);
        $window = (int) $settingModel->getValue('security', 'rate_limit_' . $scope . '_window', $defaultWindow);

        $identifier = $ip;
        if ($scope === 'login') {
            $email = strtolower(trim((string) ($request->getPost('email') ?? '')));
            if ($email !== '') {
                $identifier = $email . '|' . $ip;
            }
        }

        $cache = cache();
        $key = 'ratelimit_' . $scope . '_' . md5($identifier);
        $current = $cache->get($key);
        if (! is_array($current)) {
            $current = ['count' => 0, 'start' => time()];
        }

        if ((time() - (int) $current['start']) > $window) {
            $current = ['count' => 0, 'start' => time()];
        }

        $current['count']++;
        $cache->save($key, $current, $window);

        if ((int) $current['count'] > $limit) {
            return service('response')
                ->setStatusCode(429)
                ->setBody('Too Many Requests');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
