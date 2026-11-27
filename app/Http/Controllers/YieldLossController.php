<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\GreenBeanLot;
use App\Models\Product;
use App\Models\RoastingProfile;
use App\Models\User;
use App\Models\WorkOrder;

class YieldLossController extends Controller
{
    public function index()
    {
        $workOrders = WorkOrder::with(['lot', 'roastingProfile', 'roaster'])->latest()->get();
        $roastingProfiles = RoastingProfile::all();
        $lots = GreenBeanLot::where('current_weight_grams', '>', 0)->get();
        if ($lots->isEmpty()) {
            $lots = GreenBeanLot::all();
        }
        $boms = Bom::with(['product', 'items.materialProduct'])->get();
        $roasters = User::all();
        $roastedProducts = Product::where('is_raw_material', true)
            ->where('sku', 'LIKE', 'RAW-RB%')
            ->orderBy('sku')
            ->get();

        $activeWorkOrders = $workOrders->where('status', '!=', 'COMPLETED')->values();
        $completedBatches = $workOrders->where('status', 'COMPLETED')->values();

        $avgYieldLoss = $completedBatches->avg('yield_loss_percentage')
            ? round((float) $completedBatches->avg('yield_loss_percentage'), 1)
            : 15.7;

        $optimalBatchesCount = $completedBatches->filter(function (WorkOrder $wo): bool {
            $loss = (float) ($wo->yield_loss_percentage ?? 0);

            return $loss >= 14.0 && $loss <= 18.0;
        })->count();

        // Hitung HPP Riil aktual rata-rata dari seluruh batch selesai
        $hppSamples = [];
        foreach ($completedBatches as $wo) {
            $green = (float) ($wo->green_weight_grams ?? 0);
            $roasted = (float) ($wo->roasted_weight_grams ?? 0);
            if ($green > 0 && $roasted > 0) {
                $basePrice = ($wo->lot && (float) $wo->lot->price_per_gram > 0)
                    ? (float) $wo->lot->price_per_gram
                    : 130.0;
                $hppSamples[] = round(($basePrice * ($green / $roasted)) + 15.0, 2);
            }
        }

        $avgRealHppPerGram = count($hppSamples) > 0
            ? round(array_sum($hppSamples) / count($hppSamples), 0)
            : 158.0;

        return view('yield_loss.index', compact(
            'workOrders',
            'activeWorkOrders',
            'roastingProfiles',
            'lots',
            'boms',
            'roasters',
            'roastedProducts',
            'completedBatches',
            'avgYieldLoss',
            'optimalBatchesCount',
            'avgRealHppPerGram'
        ));
    }
}
