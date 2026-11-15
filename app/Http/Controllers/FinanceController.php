<?php

namespace App\Http\Controllers;

use App\Models\Branch;

class FinanceController extends Controller
{
    public function index()
    {
        // P&L Statement (Laba Rugi Riil)
        $pnl = [
            'gross_revenue' => 38450000,
            'promo_discounts' => 1250000,
            'tax_pb1_11' => 4092000,
            'net_sales' => 33108000, // gross - discounts - PB1 (11%)
            'cogs_beans' => 6480000, // Based on real roasted batch cost per gram
            'cogs_milk' => 3240000,
            'cogs_packaging' => 1120000,
            'cogs_syrup_others' => 760000,
            'total_cogs' => 11600000, // Total HPP Riil
            'gross_profit' => 21880000, // Net sales - COGS
            'gross_margin_pct' => 65.35,
            'opex_salaries' => 8500000,
            'opex_utilities' => 2100000,
            'opex_roastery_overhead' => 1450000,
            'total_opex' => 12050000,
            'net_operating_profit' => 9830000,
            'net_margin_pct' => 29.36,
        ];

        // Recipe Variance & Waste Analysis (Standard vs Actual Consumption)
        $varianceAnalysis = [
            [
                'ingredient' => 'Biji Sangrai Espresso (Midnight Velvet)',
                'unit' => 'kg',
                'standard_consumption' => 62.50,
                'actual_consumption' => 66.20,
                'variance' => 3.70,
                'variance_pct' => 5.92,
                'waste_cost' => 555000,
                'root_cause' => 'Kalibrasi grinder pagi (dialing-in 4 shot) & purging antar ganti order',
            ],
            [
                'ingredient' => 'Greenfields Fresh Milk Pasteurisasi',
                'unit' => 'Liter',
                'standard_consumption' => 420.00,
                'actual_consumption' => 448.50,
                'variance' => 28.50,
                'variance_pct' => 6.78,
                'waste_cost' => 627000,
                'root_cause' => 'Sisa susu di milk jug setelah steaming microfoam & pembilasan steam wand',
            ],
            [
                'ingredient' => 'Oatly Barista Edition Oat Milk',
                'unit' => 'Liter',
                'standard_consumption' => 85.00,
                'actual_consumption' => 88.20,
                'variance' => 3.20,
                'variance_pct' => 3.76,
                'waste_cost' => 144000,
                'root_cause' => 'Presisi takaran pitcher barista mendekati sempurna (toleransi baik)',
            ],
            [
                'ingredient' => 'Cold Cup PET 12oz + Flat Lid',
                'unit' => 'pcs',
                'standard_consumption' => 520,
                'actual_consumption' => 528,
                'variance' => 8,
                'variance_pct' => 1.54,
                'waste_cost' => 9600,
                'root_cause' => 'Cup pecah/rusak saat pemasangan lid di bar kasir',
            ],
            [
                'ingredient' => 'Sirup Artisan Madagascar Vanilla',
                'unit' => 'ml',
                'standard_consumption' => 2400,
                'actual_consumption' => 2520,
                'variance' => 120,
                'variance_pct' => 5.00,
                'waste_cost' => 36000,
                'root_cause' => 'Kalibrasi pump dispenser 10ml sedikit berlebih',
            ],
        ];

        $branches = Branch::all();

        return view('finance.index', compact('pnl', 'varianceAnalysis', 'branches'));
    }
}
