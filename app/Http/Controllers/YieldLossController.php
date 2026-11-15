<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\GreenBeanLot;
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

        // Calculate Yield Loss metrics
        $completedBatches = $workOrders->where('status', 'COMPLETED');
        $avgYieldLoss = $completedBatches->avg('yield_loss_percentage')
            ? round($completedBatches->avg('yield_loss_percentage'), 1)
            : 15.7;

        $optimalBatchesCount = $completedBatches->filter(function ($wo) {
            return $wo->yield_loss_percentage >= 14.0 && $wo->yield_loss_percentage <= 18.0;
        })->count();

        return view('yield_loss.index', compact(
            'workOrders',
            'roastingProfiles',
            'lots',
            'boms',
            'roasters',
            'completedBatches',
            'avgYieldLoss',
            'optimalBatchesCount'
        ));
    }
}
