<?php

declare(strict_types=1);

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

        // Item yang dapat diminta cabang: bahan baku operasional (non green beans) + produk kemasan retail 250g
        $products = Product::where(function ($q) {
            $q->where(function ($rq) {
                $rq->where('is_raw_material', true)
                    ->where('sku', 'NOT LIKE', 'RAW-GB%');
            })->orWhere('sku', 'LIKE', 'PRD-RET%');
        })->orderBy('sku')->get();

        // Logistics specific KPIs (selaras dengan ENUM delivery_notes.status: DISPATCHED, DELIVERED, RECONCILED)
        $pendingMrCount = $materialRequests->where('status', 'PENDING')->count();
        $inTransitDnCount = $deliveryNotes->where('status', 'DISPATCHED')->count();
        $completedDnCount = $deliveryNotes->whereIn('status', ['DELIVERED', 'RECONCILED'])->count();

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
