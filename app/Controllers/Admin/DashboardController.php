<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AssetModel;
use App\Models\OrderModel;
use App\Models\PairModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $role = (string) session()->get('role');

        if ($role === 'viewer') {
            return redirect()->to('/admin/audit');
        }
        if (! in_array($role, ['admin', 'staff'], true)) {
            return redirect()->to('/user/orders');
        }

        $assetModel = new AssetModel();
        $pairModel = new PairModel();
        $orderModel = new OrderModel();

        $assets = $assetModel->findAllWithCategoryAndUsage();
        $blockedAssetIds = $assetModel->unavailableAssetIdsByLimit();
        $warningCount = 0;
        foreach ($assets as $asset) {
            $percent = isset($asset['usage_percent']) ? (float) $asset['usage_percent'] : null;
            if ($percent !== null && $percent >= 80 && $percent < 100) {
                $warningCount++;
            }
        }

        $impactedPairsCount = 0;
        if ($blockedAssetIds !== []) {
            $impactedPairsCount = $pairModel
                ->where('status', 'active')
                ->whereIn('to_asset_id', $blockedAssetIds)
                ->countAllResults();
        }

        return view('admin/dashboard/index', [
            'pageTitle' => 'Overview',
            'widgets' => [
                'asset_warning_count' => $warningCount,
                'asset_stock_limit_count' => count($blockedAssetIds),
                'impacted_pairs_count' => $impactedPairsCount,
                'total_orders' => $orderModel->countAllResults(),
            ],
            'assets' => array_slice($assets, 0, 8),
        ]);
    }
}
