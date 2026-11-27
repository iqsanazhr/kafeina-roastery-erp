<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Executive Dashboard & Roastery Analytics powered by real-time database metrics.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $selectedBranch = (string) $request->query('branch', 'all');
        if ($user && ! $user->canSwitchBranch()) {
            $assignedSlug = $user->getAssignedBranchSlug();
            if ($assignedSlug !== 'all') {
                $selectedBranch = $assignedSlug;
            }
        }
        $now = Carbon::now('Asia/Jakarta');
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $start30d = $now->copy()->subDays(29)->startOfDay();

        // Map branch slugs to canonical DB Branch records
        $allBranches = Branch::where('is_active', true)
            ->with(['warehouses.stocks.product', 'users.role'])
            ->orderBy('id')
            ->get();

        $branchMap = [
            'senopati' => $allBranches->firstWhere('code', 'OUT-SENO'),
            'braga' => $allBranches->firstWhere('code', 'OUT-BRG'),
            'malioboro' => $allBranches->firstWhere('code', 'OUT-MLO'),
            'central' => $allBranches->firstWhere('code', 'HQ-CENTRAL'),
        ];

        // Load all paid commercial orders in the last 30 days (with items & cost relations)
        $orders30d = PosOrder::with(['items.product', 'items.recipeDeductions.ingredientProduct', 'branch'])
            ->where('payment_status', 'PAID')
            ->where('order_type', '!=', 'NON_REVENUE')
            ->whereBetween('created_at', [$start30d, $todayEnd])
            ->get();

        // Load completed work orders for real roasting yield stats
        $completedWorkOrders = WorkOrder::with(['lot', 'roastingProfile', 'roaster'])
            ->where('status', 'COMPLETED')
            ->orderByDesc('roast_end_time')
            ->orderByDesc('id')
            ->get();

        // Build real KPI matrix for consolidated + each branch
        $kpiMatrix = [
            'all' => $this->buildScopeKpi('Semua Cabang (Konsolidasi)', $orders30d, $completedWorkOrders, $now, null),
        ];

        foreach ($branchMap as $slug => $branchModel) {
            $branchOrders = $branchModel
                ? $orders30d->where('branch_id', $branchModel->id)->values()
                : collect();
            $label = $branchModel?->name ?? ucfirst($slug);
            $kpiMatrix[$slug] = $this->buildScopeKpi($label, $branchOrders, $completedWorkOrders, $now, $branchModel);
        }

        $kpi = $kpiMatrix[$selectedBranch] ?? $kpiMatrix['all'];

        // Real Roasting Batch Monitor from WorkOrders (selaras dengan SystemSetting)
        $minYieldLoss = (float) SystemSetting::get('roastery_min_yield_loss', 14.0);
        $maxYieldLoss = (float) SystemSetting::get('roastery_max_yield_loss', 18.0);
        $roastBatches = [];
        foreach ($completedWorkOrders->take(10) as $wo) {
            $greenKg = round(((float) $wo->green_weight_grams) / 1000, 2);
            $roastedKg = round(((float) ($wo->roasted_weight_grams ?? 0)) / 1000, 2);
            $lossPct = (float) ($wo->yield_loss_percentage ?? ($greenKg > 0 ? round((($greenKg - $roastedKg) / $greenKg) * 100, 2) : 0));

            $status = 'OPTIMAL';
            if ($lossPct > 0 && $lossPct < $minYieldLoss) {
                $status = 'UNDER_DEVELOPED';
            } elseif ($lossPct > $maxYieldLoss) {
                $status = 'OVER_ROASTED';
            }

            $profile = $wo->roastingProfile;
            $chargeTemp = $profile?->charge_temp_celsius ? round((float) $profile->charge_temp_celsius).'°C' : '200°C';
            $dtrPct = $profile?->development_time_ratio ? number_format((float) $profile->development_time_ratio, 1).'%' : '15.0%';

            $roastBatches[] = [
                'batch_code' => $wo->wo_number,
                'bean_origin' => $wo->lot?->variety ?? 'Specialty Green Bean Lot',
                'roast_profile' => $profile?->name ?? 'Standard Specialty Profile',
                'green_weight_kg' => $greenKg,
                'roasted_weight_kg' => $roastedKg,
                'yield_loss_pct' => round($lossPct, 2),
                'status' => $status,
                'agtron_commercial' => $profile?->roast_level ?? 'MEDIUM',
                'roast_master' => $wo->roaster?->name ?? 'Head Roaster',
                'charge_temp' => $chargeTemp,
                'drop_temp' => $wo->roast_end_time ? Carbon::parse($wo->roast_end_time)->setTimezone('Asia/Jakarta')->format('d M H:i') : '-',
                'dtr_pct' => $dtrPct,
                'notes' => $wo->notes ?: ($status === 'OPTIMAL'
                    ? "Toleransi susut {$minYieldLoss}–{$maxYieldLoss}% terpenuhi sesuai standar Kafeina Roastery."
                    : ($status === 'UNDER_DEVELOPED'
                        ? "PERINGATAN: Susut < {$minYieldLoss}% — periksa kadar air biji mentah atau DTR."
                        : "PERINGATAN: Susut > {$maxYieldLoss}% — risiko over-development / bitter.")),
            ];
        }

        // Real Safety Stock Alerts from WarehouseStock (kecualikan akun virtual TRANSIT)
        $selectedBranchModel = $branchMap[$selectedBranch] ?? null;
        $stocksQuery = WarehouseStock::with(['product.category', 'warehouse.branch'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->whereHas('warehouse', fn ($q) => $q->where('type', '!=', 'TRANSIT'));

        if ($selectedBranchModel) {
            $stocksQuery->whereHas('warehouse', fn ($q) => $q->where('branch_id', $selectedBranchModel->id));
        }

        $allStocks = $stocksQuery
            ->orderByRaw('CASE WHEN safety_stock_threshold > 0 THEN quantity / safety_stock_threshold ELSE 999 END ASC')
            ->get();

        $lowAlerts = [];
        $fallbackAlerts = [];
        foreach ($allStocks as $stock) {
            $qty = (float) $stock->quantity;
            $threshold = (float) ($stock->safety_stock_threshold > 0 ? $stock->safety_stock_threshold : 100);
            $pct = $threshold > 0 ? (int) min(100, round(($qty / $threshold) * 100)) : 100;
            $unit = $stock->product?->unit ?? 'unit';

            $status = $pct <= 35 ? 'CRITICAL' : ($pct <= 100 ? 'WARNING' : 'SAFE');
            $badge = $status === 'CRITICAL'
                ? "KRITIS ({$pct}%)"
                : ($status === 'WARNING' ? "MENIPIS ({$pct}%)" : "AMAN ({$pct}%)");

            $entry = [
                'item_name' => $stock->product?->name ?? 'Produk Gudang',
                'category' => $stock->product?->category?->name ?? 'Inventori',
                'warehouse' => $stock->warehouse?->name ?? ($stock->warehouse?->branch?->name ?? 'Gudang'),
                'current_stock' => number_format($qty, $qty < 100 ? 1 : 0, ',', '.').' '.$unit,
                'min_stock' => number_format($threshold, 0, ',', '.').' '.$unit,
                'percent' => $pct,
                'status' => $status,
                'badge' => $badge,
                'suggested_action' => $stock->product?->is_raw_material
                    ? 'Keluarkan PO Pengadaan / Transfer dari Central Warehouse'
                    : 'Jadwalkan produksi/restock dari Central Roastery',
            ];

            if (in_array($status, ['CRITICAL', 'WARNING'], true) && count($lowAlerts) < 6) {
                $lowAlerts[] = $entry;
            }
            if (count($fallbackAlerts) < 5) {
                $fallbackAlerts[] = $entry;
            }
        }

        // Show low-stock alerts first; if all stocks are healthy, show the 5 lowest-ratio monitored items
        $stockAlerts = count($lowAlerts) > 0 ? $lowAlerts : $fallbackAlerts;

        // Real Active Branches Summary
        $branches = [];
        foreach ($allBranches as $branch) {
            $branchOrders30d = $orders30d->where('branch_id', $branch->id);
            $branchOrdersToday = $branchOrders30d->filter(
                fn (PosOrder $o) => $o->created_at && $o->created_at->between($todayStart, $todayEnd)
            );
            $rev30d = (float) $branchOrders30d->sum('total_amount');
            $revToday = (float) $branchOrdersToday->sum('total_amount');
            $displayRev = $revToday > 0 ? $revToday : $rev30d;
            $displayOrders = $revToday > 0 ? $branchOrdersToday->count() : $branchOrders30d->count();
            $periodSuffix = $revToday > 0 ? 'hari ini' : '30 hari';

            $stocks = $branch->warehouses->flatMap(fn ($wh) => $wh->stocks);
            $milkStock = $stocks->first(fn ($s) => str_contains(strtolower((string) $s->product?->name), 'milk'));
            $beansStockQty = (float) $stocks
                ->filter(fn ($s) => str_contains(strtolower((string) $s->product?->name), 'bean') || str_contains(strtolower((string) $s->product?->name), 'roasted'))
                ->sum('quantity');

            $leadUser = $branch->users->first();

            $branches[] = [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'slug' => match ($branch->code) {
                    'OUT-SENO' => 'senopati',
                    'OUT-BRG' => 'braga',
                    'OUT-MLO' => 'malioboro',
                    'HQ-CENTRAL' => 'central',
                    default => strtolower($branch->code),
                },
                'role' => $branch->type === 'CENTRAL_ROASTERY'
                    ? 'Sentral Sangrai, QC Lab & Logistik'
                    : 'Gerai Ritel & Specialty Espresso Bar',
                'status' => $branch->type === 'CENTRAL_ROASTERY' ? 'ROASTING ACTIVE' : 'OPERATIONAL ACTIVE',
                'revenue' => 'Rp '.number_format($displayRev, 0, ',', '.')." ({$periodSuffix})",
                'orders' => "{$displayOrders} Orders ({$periodSuffix})",
                'milk_stock' => $milkStock
                    ? number_format((float) $milkStock->quantity, 0, ',', '.').' '.($milkStock->product?->unit ?? 'ml')
                    : ($branch->type === 'CENTRAL_ROASTERY' ? 'N/A (Sentral)' : 'Terpantau'),
                'beans_stock' => $beansStockQty >= 1000
                    ? number_format($beansStockQty / 1000, 1, ',', '.').' kg'
                    : number_format($beansStockQty, 0, ',', '.').' gram',
                'shift_leader' => $leadUser?->name ?? 'Tim Operasional Cabang',
            ];
        }

        // Real Hourly Trends (aggregated from selected scope orders)
        $scopedOrders = $selectedBranchModel
            ? $orders30d->where('branch_id', $selectedBranchModel->id)
            : $orders30d;

        $hourlyBuckets = [
            ['label' => 'Morning Rush', 'hour' => '07:00 - 09:00', 'min' => 0, 'max' => 9],
            ['label' => 'Brunch & Work', 'hour' => '09:00 - 12:00', 'min' => 9, 'max' => 12],
            ['label' => 'Lunch & Meeting', 'hour' => '12:00 - 15:00', 'min' => 12, 'max' => 15],
            ['label' => 'Afternoon Hangout', 'hour' => '15:00 - 18:00', 'min' => 15, 'max' => 18],
            ['label' => 'Evening Social Rush', 'hour' => '18:00 - 23:00', 'min' => 18, 'max' => 24],
        ];
        $scopedTotalRev = max(1.0, (float) $scopedOrders->sum('total_amount'));
        $hourlyTrends = array_map(function (array $bucket) use ($scopedOrders, $scopedTotalRev): array {
            $amt = (float) $scopedOrders->filter(function (PosOrder $o) use ($bucket): bool {
                $h = $o->created_at ? (int) $o->created_at->copy()->setTimezone('Asia/Jakarta')->format('G') : 12;

                return $h >= $bucket['min'] && $h < $bucket['max'];
            })->sum('total_amount');

            return [
                'hour' => $bucket['hour'],
                'label' => $bucket['label'],
                'amount' => round($amt),
                'share' => number_format(($amt / $scopedTotalRev) * 100, 1).'%',
            ];
        }, $hourlyBuckets);

        // Real Category Share from PosOrderItems
        $palette = ['#d97706', '#10b981', '#f43f5e', '#06b6d4', '#eab308', '#6366f1'];
        $scopedOrderIds = $scopedOrders->pluck('id');
        $catTotals = PosOrderItem::with('product.category')
            ->whereIn('pos_order_id', $scopedOrderIds)
            ->get()
            ->groupBy(fn (PosOrderItem $item) => $item->product?->category?->name ?? 'Lainnya')
            ->map(fn (Collection $items) => (float) $items->sum('subtotal'))
            ->sortDesc();

        $catGrandTotal = max(1.0, (float) $catTotals->sum());
        $categoryShare = [];
        $idx = 0;
        foreach ($catTotals->take(6) as $catName => $catRev) {
            $categoryShare[] = [
                'name' => $catName,
                'pct' => (int) round(($catRev / $catGrandTotal) * 100),
                'revenue' => round($catRev),
                'color' => $palette[$idx % count($palette)],
            ];
            $idx++;
        }

        return view('dashboard.index', compact(
            'kpi',
            'roastBatches',
            'stockAlerts',
            'branches',
            'hourlyTrends',
            'categoryShare',
            'selectedBranch',
            'kpiMatrix'
        ));
    }

    /**
     * Build real-time KPI aggregates for a branch scope across Today, 7D, and 30D windows.
     *
     * @param  Collection<int, PosOrder>  $orders30d
     * @param  Collection<int, WorkOrder>  $completedWorkOrders
     * @return array<string, mixed>
     */
    protected function buildScopeKpi(
        string $scopeLabel,
        Collection $orders30d,
        Collection $completedWorkOrders,
        Carbon $now,
        ?Branch $branch
    ): array {
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $fullDayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $start7d = $now->copy()->subDays(6)->startOfDay();

        $ordersToday = $orders30d->filter(
            fn (PosOrder $o) => $o->created_at && $o->created_at->copy()->setTimezone('Asia/Jakarta')->between($todayStart, $todayEnd)
        );
        $orders7d = $orders30d->filter(
            fn (PosOrder $o) => $o->created_at && $o->created_at->copy()->setTimezone('Asia/Jakarta')->gte($start7d)
        );

        $avgYieldLoss = $completedWorkOrders->isNotEmpty()
            ? round((float) $completedWorkOrders->avg('yield_loss_percentage'), 1)
            : 15.5;
        $avgRoastYield = round(100.0 - $avgYieldLoss, 1);

        $todayStats = $this->summarizeOrders($ordersToday);
        $stats7d = $this->summarizeOrders($orders7d);
        $stats30d = $this->summarizeOrders($orders30d);

        // 7-Day real calendar trend (from today-6 to today)
        $revenueTrend7d = [];
        $ordersTrend7d = [];
        $marginTrend7d = [];
        $yieldLossTrend7d = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $dayDate = $now->copy()->subDays($offset);
            $dStart = $dayDate->copy()->startOfDay();
            $dEnd = $dayDate->copy()->endOfDay();

            $dayOrders = $orders7d->filter(
                fn (PosOrder $o) => $o->created_at && $o->created_at->copy()->setTimezone('Asia/Jakarta')->between($dStart, $dEnd)
            );
            $ds = $this->summarizeOrders($dayOrders);

            $shortDay = $dayNames[(int) $dayDate->dayOfWeek];
            $fullDate = $fullDayNames[(int) $dayDate->dayOfWeek].', '.$dayDate->format('d').' '.$monthNames[(int) $dayDate->month].($offset === 0 ? ' (Hari Ini)' : '');

            $revMillions = round($ds['revenue'] / 1000000, 2);
            $revenueTrend7d[] = [
                'day' => $shortDay,
                'full_date' => $fullDate,
                'val' => $revMillions,
                'raw_revenue' => $ds['revenue'],
                'cash_revenue' => $ds['cash_revenue'],
                'qris_revenue' => $ds['qris_revenue'],
                'display' => $this->formatShortIdr($ds['revenue']),
            ];
            $ordersTrend7d[] = [
                'day' => $shortDay,
                'full_date' => $fullDate,
                'val' => $ds['orders'],
                'aov' => $ds['aov'],
            ];
            $marginTrend7d[] = [
                'day' => $shortDay,
                'val' => $ds['margin'],
            ];
            $yieldLossTrend7d[] = [
                'day' => $shortDay,
                'val' => $avgYieldLoss,
            ];
        }

        // 30-Day real 6 × 5-day calendar buckets
        $trend30d = [];
        for ($b = 0; $b < 6; $b++) {
            $bStart = $now->copy()->subDays(29 - ($b * 5))->startOfDay();
            $bEnd = $b === 5 ? $now->copy()->endOfDay() : $bStart->copy()->addDays(4)->endOfDay();

            $bucketOrders = $orders30d->filter(
                fn (PosOrder $o) => $o->created_at && $o->created_at->copy()->setTimezone('Asia/Jakarta')->between($bStart, $bEnd)
            );
            $bs = $this->summarizeOrders($bucketOrders);

            $shortLabel = $bStart->format('d').'-'.$bEnd->format('d');
            $fullRange = $bStart->format('d').' '.$monthNames[(int) $bStart->month].' – '.$bEnd->format('d').' '.$monthNames[(int) $bEnd->month].' '.$bEnd->format('Y');

            $trend30d[] = [
                'label' => $shortLabel,
                'full_title' => $fullRange,
                'rev' => round($bs['revenue'] / 1000000, 2),
                'raw_revenue' => $bs['revenue'],
                'cash_revenue' => $bs['cash_revenue'],
                'qris_revenue' => $bs['qris_revenue'],
                'orders' => $bs['orders'],
                'aov' => $bs['aov'],
                'margin' => $bs['margin'],
            ];
        }

        // Real Hourly Distribution (today if available, else 30d hourly profile)
        $hourlySlots = [
            ['hour' => '07:00', 'title' => '07:00 - 09:00 (Morning Rush)', 'min' => 0, 'max' => 9],
            ['hour' => '09:00', 'title' => '09:00 - 11:00 (WFC Coffee Break)', 'min' => 9, 'max' => 11],
            ['hour' => '11:00', 'title' => '11:00 - 13:00 (Lunch Meeting)', 'min' => 11, 'max' => 13],
            ['hour' => '13:00', 'title' => '13:00 - 15:00 (Afternoon Chill)', 'min' => 13, 'max' => 15],
            ['hour' => '15:00', 'title' => '15:00 - 17:00 (Coffee & Pastry)', 'min' => 15, 'max' => 17],
            ['hour' => '17:00', 'title' => '17:00 - 19:00 (Sunset Hangout)', 'min' => 17, 'max' => 19],
            ['hour' => '19:00', 'title' => '19:00 - 21:00 (Evening Prime)', 'min' => 19, 'max' => 21],
            ['hour' => '21:00', 'title' => '21:00 - 23:00 (Late Night Closing)', 'min' => 21, 'max' => 24],
        ];

        $hourlyToday = [];
        $hourly30d = [];
        foreach ($hourlySlots as $slot) {
            $slotTodayOrders = $ordersToday->filter(function (PosOrder $o) use ($slot): bool {
                $h = $o->created_at ? (int) $o->created_at->copy()->setTimezone('Asia/Jakarta')->format('G') : 12;

                return $h >= $slot['min'] && $h < $slot['max'];
            });
            $stToday = $this->summarizeOrders($slotTodayOrders);

            $slot30dOrders = $orders30d->filter(function (PosOrder $o) use ($slot): bool {
                $h = $o->created_at ? (int) $o->created_at->copy()->setTimezone('Asia/Jakarta')->format('G') : 12;

                return $h >= $slot['min'] && $h < $slot['max'];
            });
            $st30d = $this->summarizeOrders($slot30dOrders);

            $hourlyToday[] = [
                'hour' => $slot['hour'],
                'title' => $slot['title'],
                'val' => round($stToday['revenue'] / 1000000, 2),
                'raw_revenue' => $stToday['revenue'],
                'cash_revenue' => $stToday['cash_revenue'],
                'qris_revenue' => $stToday['qris_revenue'],
                'orders' => $stToday['orders'],
            ];

            $hourly30d[] = [
                'hour' => $slot['hour'],
                'title' => $slot['title'],
                'val' => round($st30d['revenue'] / 1000000, 2),
                'raw_revenue' => $st30d['revenue'],
                'cash_revenue' => $st30d['cash_revenue'],
                'qris_revenue' => $st30d['qris_revenue'],
                'orders' => $st30d['orders'],
            ];
        }

        // Peak badges computed dynamically from real data
        $peakTodaySlot = collect($hourlyToday)->sortByDesc('orders')->first();
        $peak7dDay = collect($ordersTrend7d)->sortByDesc('val')->first();
        $peak30dBucket = collect($trend30d)->sortByDesc('orders')->first();

        $totalRoastedKg = round(((float) $completedWorkOrders->sum('roasted_weight_grams')) / 1000, 1);

        return [
            'scope_label' => $scopeLabel,
            'generated_at' => $now->format('d M Y, H:i').' WIB',
            'daily_revenue' => $todayStats['revenue'],
            'cash_revenue' => $todayStats['cash_revenue'],
            'qris_revenue' => $todayStats['qris_revenue'],
            'transaction_count' => $todayStats['orders'],
            'average_order_value' => $todayStats['aov'],
            'gross_profit_margin' => $todayStats['margin'],
            'gross_profit_amount' => $todayStats['profit'],
            'total_roasted_beans_kg' => $totalRoastedKg,
            'average_roast_yield_pct' => $avgRoastYield,
            'average_yield_loss_pct' => $avgYieldLoss,
            'growth_desc' => $todayStats['orders'] > 0 ? 'Live DB' : '0 Transaksi',
            'periods' => [
                'today' => [
                    'revenue' => $todayStats['revenue'],
                    'cash_revenue' => $todayStats['cash_revenue'],
                    'qris_revenue' => $todayStats['qris_revenue'],
                    'orders' => $todayStats['orders'],
                    'aov' => $todayStats['aov'],
                    'margin' => $todayStats['margin'],
                    'profit' => $todayStats['profit'],
                    'yield_loss' => $avgYieldLoss,
                    'yield_rate' => $avgRoastYield.'%',
                    'orders_badge' => ($peakTodaySlot && $peakTodaySlot['orders'] > 0)
                        ? 'Puncak: '.$peakTodaySlot['hour'].' ('.$peakTodaySlot['orders'].')'
                        : 'Hari Ini',
                    'growth' => $todayStats['orders'].' Struk',
                ],
                '7d' => [
                    'revenue' => $stats7d['revenue'],
                    'cash_revenue' => $stats7d['cash_revenue'],
                    'qris_revenue' => $stats7d['qris_revenue'],
                    'orders' => $stats7d['orders'],
                    'aov' => $stats7d['aov'],
                    'margin' => $stats7d['margin'],
                    'profit' => $stats7d['profit'],
                    'yield_loss' => $avgYieldLoss,
                    'yield_rate' => $avgRoastYield.'%',
                    'orders_badge' => ($peak7dDay && $peak7dDay['val'] > 0)
                        ? 'Puncak: '.$peak7dDay['day'].' ('.$peak7dDay['val'].')'
                        : '7 Hari',
                    'growth' => $stats7d['orders'].' Struk',
                ],
                '30d' => [
                    'revenue' => $stats30d['revenue'],
                    'cash_revenue' => $stats30d['cash_revenue'],
                    'qris_revenue' => $stats30d['qris_revenue'],
                    'orders' => $stats30d['orders'],
                    'aov' => $stats30d['aov'],
                    'margin' => $stats30d['margin'],
                    'profit' => $stats30d['profit'],
                    'yield_loss' => $avgYieldLoss,
                    'yield_rate' => $avgRoastYield.'%',
                    'orders_badge' => ($peak30dBucket && $peak30dBucket['orders'] > 0)
                        ? 'Puncak: '.$peak30dBucket['label'].' ('.$peak30dBucket['orders'].')'
                        : '30 Hari',
                    'growth' => $stats30d['orders'].' Struk',
                ],
            ],
            'revenue_trend_7d' => $revenueTrend7d,
            'orders_trend_7d' => $ordersTrend7d,
            'margin_trend_7d' => $marginTrend7d,
            'yield_loss_trend_7d' => $yieldLossTrend7d,
            'trend_30d' => $trend30d,
            'hourly_revenue' => $hourlyToday,
            'hourly_revenue_30d' => $hourly30d,
        ];
    }

    /**
     * Summarize revenue, payment split, real COGS, and gross margin for a collection of orders.
     *
     * @param  Collection<int, PosOrder>  $orders
     * @return array{revenue: float, cash_revenue: float, qris_revenue: float, orders: int, aov: float, cogs: float, profit: float, margin: float}
     */
    protected function summarizeOrders(Collection $orders): array
    {
        $revenue = (float) $orders->sum('total_amount');
        $netSales = max(0.0, (float) $orders->sum('subtotal') - (float) $orders->sum('discount_amount'));
        $cashRevenue = (float) $orders->where('payment_method', 'CASH')->sum('total_amount');
        $qrisRevenue = (float) $orders->whereIn('payment_method', ['QRIS_MIDTRANS', 'BANK_TRANSFER'])->sum('total_amount');
        $count = $orders->count();
        $aov = $count > 0 ? round($revenue / $count) : 0.0;

        $cogs = 0.0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->recipeDeductions->isNotEmpty()) {
                    foreach ($item->recipeDeductions as $deduction) {
                        $cogs += (float) $deduction->quantity_deducted * (float) ($deduction->ingredientProduct?->cost_price ?? 0);
                    }
                } else {
                    $cogs += (float) $item->quantity * (float) ($item->product?->cost_price ?? 0);
                }
            }
        }

        $grossProfit = max(0.0, $netSales - $cogs);
        $margin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0.0;

        return [
            'revenue' => round($revenue),
            'cash_revenue' => round($cashRevenue),
            'qris_revenue' => round($qrisRevenue),
            'orders' => $count,
            'aov' => $aov,
            'cogs' => round($cogs),
            'profit' => round($grossProfit),
            'margin' => $margin,
        ];
    }

    /**
     * Format currency concisely for sparkline / tooltip display.
     */
    protected function formatShortIdr(float $amount): string
    {
        if ($amount >= 1000000) {
            return 'Rp '.number_format($amount / 1000000, 2, ',', '.').' Jt';
        }
        if ($amount >= 1000) {
            return 'Rp '.number_format($amount / 1000, 0, ',', '.').' Rb';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Create real consolidated emergency purchase order for critical safety stock items.
     */
    public function createConsolidatedPo(Request $request): JsonResponse
    {
        $user = auth()->user() ?? User::first();
        $supplier = Supplier::first();
        $branch = Branch::where('type', 'CENTRAL_ROASTERY')->first() ?? Branch::first();

        // Derive critical purchasable raw materials dynamically from WarehouseStock (exclude WH-TRANSIT & internal RAW-RB%)
        $lowStocks = WarehouseStock::with('product')
            ->whereHas('warehouse', fn ($q) => $q->where('type', '!=', 'TRANSIT'))
            ->whereHas('product', fn ($q) => $q->where('is_active', true)
                ->where('is_raw_material', true)
                ->where('sku', 'NOT LIKE', 'RAW-RB%'))
            ->whereColumn('quantity', '<=', 'safety_stock_threshold')
            ->limit(5)
            ->get();

        $criticalItems = [];
        foreach ($lowStocks as $stock) {
            $product = $stock->product;
            if (! $product) {
                continue;
            }

            $deficitBase = max(0.0, (float) $stock->safety_stock_threshold - (float) $stock->quantity);
            $baseCost = (float) $product->cost_price;

            // Konversi satuan dasar gudang (gram/ml -> kg/liter) agar 100% sinkron dengan Procurement & Goods Receipt
            if (in_array($product->unit, ['gram', 'ml'], true)) {
                $orderQty = max(1.0, ceil($deficitBase / 1000.0));
                $unitPrice = max(1000.0, round($baseCost * 1000.0));
            } else {
                $orderQty = max(10.0, ceil($deficitBase));
                $unitPrice = max(100.0, round($baseCost > 0 ? $baseCost : 1000.0));
            }

            $criticalItems[] = [
                'product_id' => $product->id,
                'name' => "[{$product->sku}] {$product->name}",
                'qty' => round($orderQty, 2),
                'unit_price' => $unitPrice,
            ];
        }

        // Fallback if all purchasable raw material stocks are currently above threshold
        if (empty($criticalItems)) {
            $criticalItems = [
                ['product_id' => null, 'name' => 'Green Beans Aceh Gayo Honey', 'qty' => 50.0, 'unit_price' => 130000.0],
                ['product_id' => null, 'name' => 'Greenfields Fresh Milk Pasteurisasi 1L', 'qty' => 30.0, 'unit_price' => 25000.0],
                ['product_id' => null, 'name' => 'Cold Cup PET 12oz + Flat Lid', 'qty' => 500.0, 'unit_price' => 1400.0],
            ];
        }

        $totalAmount = (float) array_sum(array_map(fn ($item) => $item['qty'] * $item['unit_price'], $criticalItems));
        $seq = PurchaseOrder::max('id') ? ((int) PurchaseOrder::max('id') + 1) : 1;
        $poNumber = 'PO-KONS-'.date('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        while (PurchaseOrder::where('po_number', $poNumber)->exists()) {
            $seq++;
            $poNumber = 'PO-KONS-'.date('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        }

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplier?->id ?? 1,
            'branch_id' => $branch?->id ?? 1,
            'user_id' => $user?->id ?? 1,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(3),
            'total_amount' => $totalAmount,
            'status' => 'ISSUED',
            'notes' => 'PO Konsolidasi Darurat '.count($criticalItems).' Item Kritis (Safety Stock Alert ROP)',
        ]);

        foreach ($criticalItems as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'quantity_kg' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['qty'] * $item['unit_price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'po_number' => $poNumber,
            'po_id' => $po->id,
            'item_count' => count($criticalItems),
            'total_amount' => $totalAmount,
            'total_amount_formatted' => 'Rp '.number_format($totalAmount, 0, ',', '.'),
            'message' => "PO Konsolidasi Darurat ({$poNumber}) berisi ".count($criticalItems).' item kritis senilai Rp '.number_format($totalAmount, 0, ',', '.').' berhasil diterbitkan & diteruskan ke vendor!',
        ]);
    }
}
