<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('quote/calculate', 'Quote::calculate', ['filter' => 'rate_limit:quote']);
$routes->get('maintenance', 'Home::maintenance');

$routes->get('login', 'Auth\LoginController::index', ['filter' => 'rate_limit:login']);
$routes->post('login', 'Auth\LoginController::process', ['filter' => 'rate_limit:login']);
$routes->get('login/2fa', 'Auth\LoginController::twoFactor', ['filter' => 'rate_limit:login']);
$routes->post('login/2fa', 'Auth\LoginController::verifyTwoFactor', ['filter' => 'rate_limit:login']);
$routes->get('register', 'Auth\RegisterController::index');
$routes->post('register', 'Auth\RegisterController::process');
$routes->get('logout', 'Auth\LoginController::logout', ['filter' => 'auth']);

$routes->group('user', ['filter' => 'auth:user'], static function ($routes) {
    $routes->get('orders', 'User\OrderController::index');
    $routes->post('orders', 'User\OrderController::create');
    $routes->get('orders/(:segment)', 'User\OrderController::detail/$1');
    $routes->post('orders/(:segment)/proof', 'User\OrderController::uploadProof/$1');
    $routes->post('orders/(:segment)/cancel', 'User\OrderController::cancel/$1');
    $routes->get('notifications', 'User\NotificationController::index');
    $routes->post('notifications/read', 'User\NotificationController::markRead');
});

$routes->group('admin', ['filter' => ['auth', 'admin']], static function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index');
    $routes->get('guide', 'Admin\GuideController::index', ['filter' => 'rbac:viewer']);

    $routes->group('categories', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\CategoryController::index');
        $routes->get('create', 'Admin\CategoryController::create');
        $routes->post('/', 'Admin\CategoryController::store');
        $routes->get('(:num)/edit', 'Admin\CategoryController::edit/$1');
        $routes->post('(:num)', 'Admin\CategoryController::update/$1');
        $routes->post('(:num)/delete', 'Admin\CategoryController::delete/$1');
    });

    $routes->group('assets', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\AssetController::index');
        $routes->get('create', 'Admin\AssetController::create');
        $routes->post('/', 'Admin\AssetController::store');
        $routes->get('(:num)/edit', 'Admin\AssetController::edit/$1');
        $routes->post('(:num)', 'Admin\AssetController::update/$1');
        $routes->post('(:num)/delete', 'Admin\AssetController::delete/$1');
    });

    $routes->group('pairs', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\PairController::index');
        $routes->get('create', 'Admin\PairController::create');
        $routes->post('/', 'Admin\PairController::store');
        $routes->get('(:num)/edit', 'Admin\PairController::edit/$1');
        $routes->post('(:num)', 'Admin\PairController::update/$1');
        $routes->post('(:num)/delete', 'Admin\PairController::delete/$1');
    });

    $routes->group('rates', ['filter' => 'rbac:staff'], static function ($routes) {
        $routes->get('/', 'Admin\RateController::index');
        $routes->post('(:num)/update', 'Admin\RateController::update/$1');
        $routes->get('(:num)/history', 'Admin\RateController::history/$1');
        $routes->get('(:num)/chart-data', 'Admin\RateController::chartData/$1');
    });

    $routes->group('payment-methods', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\PaymentMethodController::index');
        $routes->get('create', 'Admin\PaymentMethodController::create');
        $routes->post('/', 'Admin\PaymentMethodController::store');
        $routes->get('(:num)/edit', 'Admin\PaymentMethodController::edit/$1');
        $routes->post('(:num)', 'Admin\PaymentMethodController::update/$1');
        $routes->post('(:num)/delete', 'Admin\PaymentMethodController::delete/$1');
        $routes->post('(:num)/pairs', 'Admin\PaymentMethodController::updatePairs/$1');
    });

    $routes->group('users', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\UserController::index');
        $routes->get('create', 'Admin\UserController::create');
        $routes->post('/', 'Admin\UserController::store');
        $routes->get('(:num)/edit', 'Admin\UserController::edit/$1');
        $routes->post('(:num)', 'Admin\UserController::update/$1');
        $routes->post('(:num)/toggle', 'Admin\UserController::toggle/$1');
    });

    $routes->group('orders', ['filter' => 'rbac:staff'], static function ($routes) {
        $routes->get('/', 'Admin\OrderController::index');
        $routes->get('export-csv', 'Admin\OrderController::exportCsv');
        $routes->get('(:segment)', 'Admin\OrderController::detail/$1');
        $routes->post('bulk-action', 'Admin\OrderController::bulkAction');
        $routes->post('(:segment)/quick-action', 'Admin\OrderController::quickAction/$1');
        $routes->post('(:segment)/lock', 'Admin\OrderController::lock/$1');
        $routes->post('(:segment)/unlock', 'Admin\OrderController::unlock/$1');
        $routes->post('(:segment)/verify-proof', 'Admin\OrderController::verifyProof/$1');
        $routes->post('(:segment)/status', 'Admin\OrderController::updateStatus/$1');
    });
    $routes->get('reconciliation', 'Admin\ReconciliationController::index', ['filter' => 'rbac:staff']);

    $routes->group('notifications', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\NotificationController::index');
        $routes->post('read-all', 'Admin\NotificationController::markAllRead');
        $routes->get('blast', 'Admin\NotificationController::blast');
        $routes->post('blast', 'Admin\NotificationController::sendBlast');
    });

    $routes->group('settings', ['filter' => 'rbac:admin'], static function ($routes) {
        $routes->get('/', 'Admin\SettingController::index');
        $routes->post('/', 'Admin\SettingController::update');
        $routes->post('maintenance', 'Admin\SettingController::toggleMaintenance');
    });

    $routes->get('audit', 'Admin\AuditController::index', ['filter' => 'rbac:viewer']);
});
