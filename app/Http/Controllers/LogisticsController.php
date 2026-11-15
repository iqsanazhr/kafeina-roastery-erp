<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\Product;

class LogisticsController extends Controller
{
    public function index()
    {
        $materialRequests = MaterialRequest::with(['fromBranch', 'toBranch', 'requestedBy', 'items.product'])->latest()->get();
        $deliveryNotes = DeliveryNote::with(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product'])->latest()->get();
        $branches = Branch::all();
        $warehouses = InventoryWarehouse::with('branch')->get();
        $products = Product::all();

        // Logistics specific KPIs
        $pendingMrCount = $materialRequests->where('status', 'PENDING')->count();
        $inTransitDnCount = $deliveryNotes->where('status', 'DISPATCHED')->count();
        $completedDnCount = $deliveryNotes->where('status', 'RECEIVED')->count();

        return view('logistics.index', compact(
            'materialRequests',
            'deliveryNotes',
            'branches',
            'warehouses',
            'products',
            'pendingMrCount',
            'inTransitDnCount',
            'completedDnCount'
        ));
    }
}
