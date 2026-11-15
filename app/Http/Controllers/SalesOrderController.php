<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PosOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderController extends Controller
{
    /**
     * Display sales history and customer orders across branches.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isBranchRestricted = $user && ! $user->canSwitchBranch() && $user->branch_id;

        if ($isBranchRestricted) {
            $selectedBranchId = $user->branch_id;
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::where('is_active', true)->get();
            $selectedBranchId = $request->query('branch_id');
        }

        $selectedPeriod = $request->query('period', '30d');
        $selectedPayment = $request->query('payment_method', 'ALL');
        $selectedType = $request->query('order_type', 'ALL');
        $search = trim((string) $request->query('search', ''));

        // Determine date range from period
        $startDate = match ($selectedPeriod) {
            'today' => Carbon::today()->toDateString(),
            '7d' => Carbon::now()->subDays(7)->toDateString(),
            '30d' => Carbon::now()->subDays(30)->toDateString(),
            'all' => null,
            default => Carbon::now()->subDays(30)->toDateString(),
        };
        $endDate = Carbon::today()->toDateString();

        // Custom dates if provided
        if ($request->filled('start_date')) {
            $startDate = $request->query('start_date');
        }
        if ($request->filled('end_date')) {
            $endDate = $request->query('end_date');
        }

        // Base Query
        $query = PosOrder::with(['branch', 'cashier', 'items.product', 'items.recipeDeductions.ingredientProduct'])
            ->latest('created_at');

        if ($isBranchRestricted) {
            $query->forBranch((int) $user->branch_id);
        } elseif ($selectedBranchId && $selectedBranchId !== 'ALL') {
            $query->forBranch((int) $selectedBranchId);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($selectedPayment && $selectedPayment !== 'ALL') {
            $query->paymentMethod($selectedPayment);
        }

        if ($selectedType && $selectedType !== 'ALL') {
            $query->orderType($selectedType);
        }

        if ($search !== '') {
            $query->search($search);
        }

        // Aggregate Metrics for Selected Filter
        $metricsQuery = clone $query;
        $totalOrdersCount = $metricsQuery->count();
        $totalGrossRevenue = (float) $metricsQuery->sum('subtotal');
        $totalDiscount = (float) $metricsQuery->sum('discount_amount');
        $totalTaxPb1 = (float) $metricsQuery->sum('tax_amount');
        $totalNetCollected = (float) $metricsQuery->sum('total_amount');
        $averageOrderValue = $totalOrdersCount > 0 ? round($totalNetCollected / $totalOrdersCount) : 0;

        // Breakdown per Payment Method
        $paymentBreakdown = (clone $query)
            ->reorder()
            ->selectRaw('payment_method, count(*) as count, sum(total_amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // Breakdown per Branch
        $branchBreakdown = (clone $query)
            ->reorder()
            ->selectRaw('branch_id, count(*) as count, sum(total_amount) as total')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $orders = $query->paginate(50)->withQueryString();

        return view('pos.orders', compact(
            'orders',
            'branches',
            'selectedBranchId',
            'selectedPeriod',
            'selectedPayment',
            'selectedType',
            'search',
            'startDate',
            'endDate',
            'totalOrdersCount',
            'totalGrossRevenue',
            'totalDiscount',
            'totalTaxPb1',
            'totalNetCollected',
            'averageOrderValue',
            'paymentBreakdown',
            'branchBreakdown'
        ));
    }

    /**
     * Get detailed order info and recipe deduction breakdown as JSON.
     */
    public function show(int $id): JsonResponse
    {
        $order = PosOrder::with([
            'branch',
            'cashier',
            'shift',
            'items.product.category',
            'items.recipeDeductions.ingredientProduct',
        ])->findOrFail($id);

        $user = auth()->user();
        if ($user && ! $user->canSwitchBranch() && $user->branch_id && $order->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda hanya dapat mengakses data transaksi dari cabang Anda sendiri.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Export sales orders to formatted CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $isBranchRestricted = $user && ! $user->canSwitchBranch() && $user->branch_id;

        if ($isBranchRestricted) {
            $selectedBranchId = $user->branch_id;
        } else {
            $selectedBranchId = $request->query('branch_id');
        }
        $selectedPeriod = $request->query('period', '30d');
        $selectedPayment = $request->query('payment_method', 'ALL');
        $selectedType = $request->query('order_type', 'ALL');
        $search = trim((string) $request->query('search', ''));

        $startDate = match ($selectedPeriod) {
            'today' => Carbon::today()->toDateString(),
            '7d' => Carbon::now()->subDays(7)->toDateString(),
            '30d' => Carbon::now()->subDays(30)->toDateString(),
            'all' => null,
            default => Carbon::now()->subDays(30)->toDateString(),
        };
        $endDate = Carbon::today()->toDateString();

        if ($request->filled('start_date')) {
            $startDate = $request->query('start_date');
        }
        if ($request->filled('end_date')) {
            $endDate = $request->query('end_date');
        }

        $query = PosOrder::with(['branch', 'cashier', 'items.product'])
            ->latest('created_at');

        if ($selectedBranchId && $selectedBranchId !== 'ALL') {
            $query->forBranch((int) $selectedBranchId);
        }
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        if ($selectedPayment && $selectedPayment !== 'ALL') {
            $query->paymentMethod($selectedPayment);
        }
        if ($selectedType && $selectedType !== 'ALL') {
            $query->orderType($selectedType);
        }
        if ($search !== '') {
            $query->search($search);
        }

        $filename = 'kafeina_riwayat_penjualan_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Title Block
            fputcsv($handle, ['KAFEINA ROASTERY ERP - BUKU PENJUALAN & RIWAYAT TRANSAKSI PELANGGAN']);
            fputcsv($handle, ['Periode', ($startDate ?? 'Semua').' s/d '.($endDate ?? 'Sekarang')]);
            fputcsv($handle, ['Waktu Ekspor', now()->format('Y-m-d H:i:s').' WIB']);
            fputcsv($handle, []);

            // Header Column
            fputcsv($handle, [
                'No. Order',
                'Waktu Transaksi',
                'Cabang',
                'Nama Pelanggan',
                'Nomor Meja',
                'Tipe Pesanan',
                'Kasir',
                'Metode Bayar',
                'Status Bayar',
                'Ref / Midtrans ID',
                'Subtotal (IDR)',
                'Diskon (IDR)',
                'PB1 11% (IDR)',
                'Total Bersih (IDR)',
                'Rincian Menu Item',
            ]);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    $itemDescriptions = [];
                    foreach ($order->items as $item) {
                        $pName = $item->product?->name ?? 'Produk';
                        $itemDescriptions[] = "{$item->quantity}x {$pName} (@Rp ".number_format($item->unit_price, 0, ',', '.').')';
                    }

                    fputcsv($handle, [
                        $order->order_number,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->branch?->name ?? 'Cabang',
                        $order->customer_name ?: 'Tamu Kafeina',
                        $order->table_number ?: '-',
                        $order->order_type,
                        $order->cashier?->name ?? 'Kasir',
                        $order->payment_method,
                        $order->payment_status,
                        $order->payment_reference ?: '-',
                        $order->subtotal,
                        $order->discount_amount,
                        $order->tax_amount,
                        $order->total_amount,
                        implode('; ', $itemDescriptions),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
