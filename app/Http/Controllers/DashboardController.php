<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Executive Dashboard & Roastery Analytics.
     */
    public function index(Request $request)
    {
        $selectedBranch = $request->query('branch', 'all');

        $kpiMatrix = [
            'all' => [
                'scope_label' => 'Semua Cabang (Konsolidasi)',
                'daily_revenue' => 38450000,
                'revenue_target' => 35000000,
                'revenue_growth_pct' => 14.2,
                'cash_revenue' => 12300000,
                'qris_revenue' => 26150000,
                'transaction_count' => 482,
                'average_order_value' => 79771,
                'gross_profit_margin' => 68.4,
                'gross_profit_amount' => 26300000,
                'total_roasted_beans_kg' => 1840,
                'average_roast_yield_pct' => 84.3,
                'average_yield_loss_pct' => 15.7,
                'target_desc' => 'Target: 35 Jt',
                'growth_desc' => '+14.2% vs Target',
                'revenue_trend_7d' => [
                    ['day' => 'Sen', 'val' => 31.4, 'display' => '31,4 Jt'],
                    ['day' => 'Sel', 'val' => 33.2, 'display' => '33,2 Jt'],
                    ['day' => 'Rab', 'val' => 29.8, 'display' => '29,8 Jt'],
                    ['day' => 'Kam', 'val' => 34.6, 'display' => '34,6 Jt'],
                    ['day' => 'Jum', 'val' => 37.1, 'display' => '37,1 Jt'],
                    ['day' => 'Sab', 'val' => 43.5, 'display' => '43,5 Jt'],
                    ['day' => 'Min', 'val' => 38.45, 'display' => '38,45 Jt'],
                ],
                'orders_trend_7d' => [
                    ['day' => 'Sen', 'val' => 395],
                    ['day' => 'Sel', 'val' => 418],
                    ['day' => 'Rab', 'val' => 372],
                    ['day' => 'Kam', 'val' => 430],
                    ['day' => 'Jum', 'val' => 465],
                    ['day' => 'Sab', 'val' => 548],
                    ['day' => 'Min', 'val' => 482],
                ],
                'margin_trend_7d' => [
                    ['day' => 'Sen', 'val' => 67.2],
                    ['day' => 'Sel', 'val' => 67.8],
                    ['day' => 'Rab', 'val' => 66.5],
                    ['day' => 'Kam', 'val' => 68.1],
                    ['day' => 'Jum', 'val' => 69.0],
                    ['day' => 'Sab', 'val' => 70.4],
                    ['day' => 'Min', 'val' => 68.4],
                ],
                'yield_loss_trend_7d' => [
                    ['day' => 'Sen', 'val' => 15.4],
                    ['day' => 'Sel', 'val' => 15.8],
                    ['day' => 'Rab', 'val' => 16.1],
                    ['day' => 'Kam', 'val' => 15.3],
                    ['day' => 'Jum', 'val' => 15.9],
                    ['day' => 'Sab', 'val' => 15.5],
                    ['day' => 'Min', 'val' => 15.7],
                ],
                'hourly_revenue' => [
                    ['hour' => '07:00', 'val' => 2.4, 'orders' => 35],
                    ['hour' => '09:00', 'val' => 5.1, 'orders' => 68],
                    ['hour' => '11:00', 'val' => 4.6, 'orders' => 59],
                    ['hour' => '13:00', 'val' => 4.8, 'orders' => 61],
                    ['hour' => '15:00', 'val' => 3.9, 'orders' => 49],
                    ['hour' => '17:00', 'val' => 4.2, 'orders' => 54],
                    ['hour' => '19:00', 'val' => 7.8, 'orders' => 98],
                    ['hour' => '21:00', 'val' => 5.65, 'orders' => 58],
                ],
            ],
            'senopati' => [
                'scope_label' => 'Flagship Senopati (Jakarta)',
                'daily_revenue' => 16850000,
                'revenue_target' => 15000000,
                'revenue_growth_pct' => 12.3,
                'cash_revenue' => 5200000,
                'qris_revenue' => 11650000,
                'transaction_count' => 214,
                'average_order_value' => 78738,
                'gross_profit_margin' => 71.2,
                'gross_profit_amount' => 11997200,
                'total_roasted_beans_kg' => 480,
                'average_roast_yield_pct' => 84.8,
                'average_yield_loss_pct' => 15.2,
                'target_desc' => 'Target: 15 Jt',
                'growth_desc' => '+12.3% vs Target',
                'revenue_trend_7d' => [
                    ['day' => 'Sen', 'val' => 13.8, 'display' => '13,8 Jt'],
                    ['day' => 'Sel', 'val' => 14.5, 'display' => '14,5 Jt'],
                    ['day' => 'Rab', 'val' => 13.2, 'display' => '13,2 Jt'],
                    ['day' => 'Kam', 'val' => 15.1, 'display' => '15,1 Jt'],
                    ['day' => 'Jum', 'val' => 16.4, 'display' => '16,4 Jt'],
                    ['day' => 'Sab', 'val' => 18.9, 'display' => '18,9 Jt'],
                    ['day' => 'Min', 'val' => 16.85, 'display' => '16,85 Jt'],
                ],
                'orders_trend_7d' => [
                    ['day' => 'Sen', 'val' => 175],
                    ['day' => 'Sel', 'val' => 184],
                    ['day' => 'Rab', 'val' => 168],
                    ['day' => 'Kam', 'val' => 192],
                    ['day' => 'Jum', 'val' => 208],
                    ['day' => 'Sab', 'val' => 242],
                    ['day' => 'Min', 'val' => 214],
                ],
                'margin_trend_7d' => [
                    ['day' => 'Sen', 'val' => 70.1],
                    ['day' => 'Sel', 'val' => 70.6],
                    ['day' => 'Rab', 'val' => 69.8],
                    ['day' => 'Kam', 'val' => 71.0],
                    ['day' => 'Jum', 'val' => 71.5],
                    ['day' => 'Sab', 'val' => 72.4],
                    ['day' => 'Min', 'val' => 71.2],
                ],
                'yield_loss_trend_7d' => [
                    ['day' => 'Sen', 'val' => 15.0],
                    ['day' => 'Sel', 'val' => 15.3],
                    ['day' => 'Rab', 'val' => 15.5],
                    ['day' => 'Kam', 'val' => 14.9],
                    ['day' => 'Jum', 'val' => 15.4],
                    ['day' => 'Sab', 'val' => 15.1],
                    ['day' => 'Min', 'val' => 15.2],
                ],
                'hourly_revenue' => [
                    ['hour' => '07:00', 'val' => 1.2, 'orders' => 16],
                    ['hour' => '09:00', 'val' => 2.4, 'orders' => 31],
                    ['hour' => '11:00', 'val' => 2.1, 'orders' => 27],
                    ['hour' => '13:00', 'val' => 2.2, 'orders' => 28],
                    ['hour' => '15:00', 'val' => 1.8, 'orders' => 23],
                    ['hour' => '17:00', 'val' => 2.0, 'orders' => 25],
                    ['hour' => '19:00', 'val' => 3.2, 'orders' => 41],
                    ['hour' => '21:00', 'val' => 1.95, 'orders' => 23],
                ],
            ],
            'braga' => [
                'scope_label' => 'Branch Braga Heritage (Bandung)',
                'daily_revenue' => 12400000,
                'revenue_target' => 11000000,
                'revenue_growth_pct' => 12.7,
                'cash_revenue' => 4100000,
                'qris_revenue' => 8300000,
                'transaction_count' => 158,
                'average_order_value' => 78481,
                'gross_profit_margin' => 67.5,
                'gross_profit_amount' => 8370000,
                'total_roasted_beans_kg' => 360,
                'average_roast_yield_pct' => 84.1,
                'average_yield_loss_pct' => 15.9,
                'target_desc' => 'Target: 11 Jt',
                'growth_desc' => '+12.7% vs Target',
                'revenue_trend_7d' => [
                    ['day' => 'Sen', 'val' => 10.2],
                    ['day' => 'Sel', 'val' => 10.8],
                    ['day' => 'Rab', 'val' => 9.9],
                    ['day' => 'Kam', 'val' => 11.2],
                    ['day' => 'Jum', 'val' => 12.0],
                    ['day' => 'Sab', 'val' => 14.5],
                    ['day' => 'Min', 'val' => 12.4],
                ],
                'orders_trend_7d' => [
                    ['day' => 'Sen', 'val' => 130],
                    ['day' => 'Sel', 'val' => 138],
                    ['day' => 'Rab', 'val' => 126],
                    ['day' => 'Kam', 'val' => 142],
                    ['day' => 'Jum', 'val' => 152],
                    ['day' => 'Sab', 'val' => 185],
                    ['day' => 'Min', 'val' => 158],
                ],
                'margin_trend_7d' => [
                    ['day' => 'Sen', 'val' => 66.5],
                    ['day' => 'Sel', 'val' => 66.8],
                    ['day' => 'Rab', 'val' => 65.9],
                    ['day' => 'Kam', 'val' => 67.2],
                    ['day' => 'Jum', 'val' => 68.0],
                    ['day' => 'Sab', 'val' => 69.1],
                    ['day' => 'Min', 'val' => 67.5],
                ],
                'yield_loss_trend_7d' => [
                    ['day' => 'Sen', 'val' => 15.7],
                    ['day' => 'Sel', 'val' => 16.0],
                    ['day' => 'Rab', 'val' => 16.3],
                    ['day' => 'Kam', 'val' => 15.6],
                    ['day' => 'Jum', 'val' => 16.1],
                    ['day' => 'Sab', 'val' => 15.8],
                    ['day' => 'Min', 'val' => 15.9],
                ],
                'hourly_revenue' => [
                    ['hour' => '07:00', 'val' => 0.8, 'orders' => 11],
                    ['hour' => '09:00', 'val' => 1.6, 'orders' => 21],
                    ['hour' => '11:00', 'val' => 1.5, 'orders' => 19],
                    ['hour' => '13:00', 'val' => 1.6, 'orders' => 20],
                    ['hour' => '15:00', 'val' => 1.3, 'orders' => 16],
                    ['hour' => '17:00', 'val' => 1.4, 'orders' => 18],
                    ['hour' => '19:00', 'val' => 2.6, 'orders' => 33],
                    ['hour' => '21:00', 'val' => 1.6, 'orders' => 20],
                ],
            ],
            'malioboro' => [
                'scope_label' => 'Branch Malioboro Sanctuary (Yogyakarta)',
                'daily_revenue' => 9200000,
                'revenue_target' => 9000000,
                'revenue_growth_pct' => 2.2,
                'cash_revenue' => 3000000,
                'qris_revenue' => 6200000,
                'transaction_count' => 110,
                'average_order_value' => 83636,
                'gross_profit_margin' => 65.8,
                'gross_profit_amount' => 6053600,
                'total_roasted_beans_kg' => 280,
                'average_roast_yield_pct' => 83.9,
                'average_yield_loss_pct' => 16.1,
                'target_desc' => 'Target: 9 Jt',
                'growth_desc' => '+2.2% vs Target',
                'revenue_trend_7d' => [
                    ['day' => 'Sen', 'val' => 7.4],
                    ['day' => 'Sel', 'val' => 7.9],
                    ['day' => 'Rab', 'val' => 6.7],
                    ['day' => 'Kam', 'val' => 8.3],
                    ['day' => 'Jum', 'val' => 8.7],
                    ['day' => 'Sab', 'val' => 10.2],
                    ['day' => 'Min', 'val' => 9.2],
                ],
                'orders_trend_7d' => [
                    ['day' => 'Sen', 'val' => 90],
                    ['day' => 'Sel', 'val' => 96],
                    ['day' => 'Rab', 'val' => 78],
                    ['day' => 'Kam', 'val' => 96],
                    ['day' => 'Jum', 'val' => 105],
                    ['day' => 'Sab', 'val' => 121],
                    ['day' => 'Min', 'val' => 110],
                ],
                'margin_trend_7d' => [
                    ['day' => 'Sen', 'val' => 64.9],
                    ['day' => 'Sel', 'val' => 65.4],
                    ['day' => 'Rab', 'val' => 64.1],
                    ['day' => 'Kam', 'val' => 65.6],
                    ['day' => 'Jum', 'val' => 66.2],
                    ['day' => 'Sab', 'val' => 67.5],
                    ['day' => 'Min', 'val' => 65.8],
                ],
                'yield_loss_trend_7d' => [
                    ['day' => 'Sen', 'val' => 16.0],
                    ['day' => 'Sel', 'val' => 16.2],
                    ['day' => 'Rab', 'val' => 16.4],
                    ['day' => 'Kam', 'val' => 15.9],
                    ['day' => 'Jum', 'val' => 16.3],
                    ['day' => 'Sab', 'val' => 16.0],
                    ['day' => 'Min', 'val' => 16.1],
                ],
                'hourly_revenue' => [
                    ['hour' => '07:00', 'val' => 0.4, 'orders' => 5],
                    ['hour' => '09:00', 'val' => 1.1, 'orders' => 13],
                    ['hour' => '11:00', 'val' => 1.0, 'orders' => 12],
                    ['hour' => '13:00', 'val' => 1.0, 'orders' => 12],
                    ['hour' => '15:00', 'val' => 0.8, 'orders' => 10],
                    ['hour' => '17:00', 'val' => 0.8, 'orders' => 10],
                    ['hour' => '19:00', 'val' => 2.0, 'orders' => 24],
                    ['hour' => '21:00', 'val' => 2.1, 'orders' => 24],
                ],
            ],
            'central' => [
                'scope_label' => 'Central Roastery Hub (Cilandak)',
                'daily_revenue' => 8950000,
                'revenue_target' => 8000000,
                'revenue_growth_pct' => 11.8,
                'cash_revenue' => 1200000,
                'qris_revenue' => 7750000,
                'transaction_count' => 24,
                'average_order_value' => 372916,
                'gross_profit_margin' => 54.0,
                'gross_profit_amount' => 4833000,
                'total_roasted_beans_kg' => 1840,
                'average_roast_yield_pct' => 84.3,
                'average_yield_loss_pct' => 15.7,
                'target_desc' => 'Target: 8 Jt (B2B)',
                'growth_desc' => '+11.8% vs Target',
                'revenue_trend_7d' => [
                    ['day' => 'Sen', 'val' => 7.8],
                    ['day' => 'Sel', 'val' => 8.2],
                    ['day' => 'Rab', 'val' => 7.5],
                    ['day' => 'Kam', 'val' => 8.6],
                    ['day' => 'Jum', 'val' => 9.1],
                    ['day' => 'Sab', 'val' => 8.4],
                    ['day' => 'Min', 'val' => 8.95],
                ],
                'orders_trend_7d' => [
                    ['day' => 'Sen', 'val' => 20],
                    ['day' => 'Sel', 'val' => 22],
                    ['day' => 'Rab', 'val' => 19],
                    ['day' => 'Kam', 'val' => 23],
                    ['day' => 'Jum', 'val' => 25],
                    ['day' => 'Sab', 'val' => 22],
                    ['day' => 'Min', 'val' => 24],
                ],
                'margin_trend_7d' => [
                    ['day' => 'Sen', 'val' => 53.2],
                    ['day' => 'Sel', 'val' => 53.8],
                    ['day' => 'Rab', 'val' => 52.9],
                    ['day' => 'Kam', 'val' => 54.2],
                    ['day' => 'Jum', 'val' => 54.8],
                    ['day' => 'Sab', 'val' => 53.6],
                    ['day' => 'Min', 'val' => 54.0],
                ],
                'yield_loss_trend_7d' => [
                    ['day' => 'Sen', 'val' => 15.4],
                    ['day' => 'Sel', 'val' => 15.8],
                    ['day' => 'Rab', 'val' => 16.1],
                    ['day' => 'Kam', 'val' => 15.3],
                    ['day' => 'Jum', 'val' => 15.9],
                    ['day' => 'Sab', 'val' => 15.5],
                    ['day' => 'Min', 'val' => 15.7],
                ],
                'hourly_revenue' => [
                    ['hour' => '07:00', 'val' => 0.0, 'orders' => 0],
                    ['hour' => '09:00', 'val' => 2.4, 'orders' => 6],
                    ['hour' => '11:00', 'val' => 2.8, 'orders' => 7],
                    ['hour' => '13:00', 'val' => 1.8, 'orders' => 5],
                    ['hour' => '15:00', 'val' => 1.2, 'orders' => 4],
                    ['hour' => '17:00', 'val' => 0.75, 'orders' => 2],
                    ['hour' => '19:00', 'val' => 0.0, 'orders' => 0],
                    ['hour' => '21:00', 'val' => 0.0, 'orders' => 0],
                ],
            ],
        ];

        $kpi = $kpiMatrix[$selectedBranch] ?? $kpiMatrix['all'];

        $roastBatches = [
            [
                'batch_code' => 'BATCH-ROAST-20261108-01',
                'bean_origin' => 'Aceh Gayo Honey Anaerobic',
                'roast_profile' => 'Omni Filter / Medium-Light',
                'green_weight_kg' => 12.00,
                'roasted_weight_kg' => 10.15,
                'yield_loss_pct' => 15.42,
                'status' => 'OPTIMAL',
                'agtron_commercial' => '65 / 80',
                'roast_master' => 'Bramantyo Wicaksono',
                'charge_temp' => '200°C',
                'drop_temp' => '208°C',
                'dtr_pct' => '14.8%',
                'notes' => 'Toleransi susut 14-18% terpenuhi sempurna. Peach & floral notes terpreservasi baik.',
            ],
            [
                'batch_code' => 'BATCH-ROAST-20261108-02',
                'bean_origin' => 'Ijen Natural Carbonic Maceration',
                'roast_profile' => 'Light Filter Agtron 75',
                'green_weight_kg' => 15.00,
                'roasted_weight_kg' => 12.55,
                'yield_loss_pct' => 16.33,
                'status' => 'OPTIMAL',
                'agtron_commercial' => '72 / 88',
                'roast_master' => 'Bramantyo Wicaksono',
                'charge_temp' => '195°C',
                'drop_temp' => '204°C',
                'dtr_pct' => '13.5%',
                'notes' => 'Aroma jasmine & sparkling tropical acidity optimal. First crack bersih di 08:35.',
            ],
            [
                'batch_code' => 'BATCH-ROAST-20261108-03',
                'bean_origin' => 'Toraja Sapan Washed Grade 1',
                'roast_profile' => 'Espresso Roast Medium',
                'green_weight_kg' => 10.00,
                'roasted_weight_kg' => 8.75,
                'yield_loss_pct' => 12.50,
                'status' => 'UNDER_DEVELOPED',
                'agtron_commercial' => '58 / 68',
                'roast_master' => 'Dimas Satria',
                'charge_temp' => '190°C',
                'drop_temp' => '198°C',
                'dtr_pct' => '11.2%',
                'notes' => 'PERINGATAN: Susut < 14%. Kadar air biji mentah terlalu tinggi atau DTR terlalu pendek (resiko rasa grassy/vegetal).',
            ],
            [
                'batch_code' => 'BATCH-ROAST-20261108-04',
                'bean_origin' => 'Flores Bajawa Anaerobic Natural',
                'roast_profile' => 'Dark Espresso Roast',
                'green_weight_kg' => 8.00,
                'roasted_weight_kg' => 6.45,
                'yield_loss_pct' => 19.38,
                'status' => 'OVER_ROASTED',
                'agtron_commercial' => '42 / 50',
                'roast_master' => 'Dimas Satria',
                'charge_temp' => '205°C',
                'drop_temp' => '222°C',
                'dtr_pct' => '22.4%',
                'notes' => 'PERINGATAN: Susut > 18%. Over-development beyond second crack (resiko minyak keluar & gosong/bitter).',
            ],
            [
                'batch_code' => 'BATCH-ROAST-20261108-05',
                'bean_origin' => 'Bali Kintamani Washed Carbonic',
                'roast_profile' => 'Omni Roast Light',
                'green_weight_kg' => 14.00,
                'roasted_weight_kg' => 11.80,
                'yield_loss_pct' => 15.71,
                'status' => 'OPTIMAL',
                'agtron_commercial' => '68 / 82',
                'roast_master' => 'Bramantyo Wicaksono',
                'charge_temp' => '198°C',
                'drop_temp' => '206°C',
                'dtr_pct' => '15.1%',
                'notes' => 'Profil citrus orange & brown sugar seimbang. Masuk batch cupping sore.',
            ],
        ];

        $stockAlerts = [
            [
                'item_name' => 'Green Beans Aceh Gayo Honey',
                'category' => 'Biji Kopi Mentah',
                'warehouse' => 'Central Roastery Hub (Cilandak)',
                'current_stock' => '35.0 kg',
                'min_stock' => '100.0 kg',
                'percent' => 35,
                'status' => 'CRITICAL',
                'badge' => 'KRITIS (35%)',
                'suggested_action' => 'Keluarkan PO Darurat ke Koperasi Gayo Sara Ate',
            ],
            [
                'item_name' => 'Cold Cup PET 12oz + Flat Lid',
                'category' => 'Packaging Outlet',
                'warehouse' => 'Branch Senopati Flagship',
                'current_stock' => '250 pcs',
                'min_stock' => '1.000 pcs',
                'percent' => 25,
                'status' => 'CRITICAL',
                'badge' => 'KRITIS (25%)',
                'suggested_action' => 'Dispatch 2 Karton (2.000 pcs) dari Central Warehouse',
            ],
            [
                'item_name' => 'Greenfields Fresh Milk Pasteurisasi 1L',
                'category' => 'Bahan Baku Segar',
                'warehouse' => 'Branch Senopati Flagship',
                'current_stock' => '18 pack',
                'min_stock' => '50 pack',
                'percent' => 36,
                'status' => 'WARNING',
                'badge' => 'MENIPIS (36%)',
                'suggested_action' => 'Pemesanan Suplier Harian (Pengiriman Pagi 06:00)',
            ],
            [
                'item_name' => 'Oatly Barista Edition Oat Milk 1L',
                'category' => 'Plant-Based Milk',
                'warehouse' => 'Branch Braga Bandung',
                'current_stock' => '12 pack',
                'min_stock' => '30 pack',
                'percent' => 40,
                'status' => 'WARNING',
                'badge' => 'MENIPIS (40%)',
                'suggested_action' => 'Alokasi Pengiriman Logistik Mingguan',
            ],
            [
                'item_name' => 'Degassing Valve Foil Pouch 250g Matte Black',
                'category' => 'Kemasan Retail',
                'warehouse' => 'Central Roastery Hub (Cilandak)',
                'current_stock' => '90 pcs',
                'min_stock' => '300 pcs',
                'percent' => 30,
                'status' => 'WARNING',
                'badge' => 'MENIPIS (30%)',
                'suggested_action' => 'Restock Supplier Packaging Lokal',
            ],
        ];

        if ($selectedBranch === 'senopati') {
            $stockAlerts = array_values(array_filter($stockAlerts, fn ($a) => str_contains($a['warehouse'], 'Senopati')));
        } elseif ($selectedBranch === 'braga') {
            $stockAlerts = array_values(array_filter($stockAlerts, fn ($a) => str_contains($a['warehouse'], 'Braga')));
        } elseif ($selectedBranch === 'central') {
            $stockAlerts = array_values(array_filter($stockAlerts, fn ($a) => str_contains($a['warehouse'], 'Central')));
        }

        $branches = [
            [
                'code' => 'CENTRAL-HUB',
                'name' => 'Central Roastery & Lab (Cilandak)',
                'role' => 'Sentral Penggilingan & Logistik',
                'status' => 'ROASTING ACTIVE',
                'revenue' => 'Rp 8.950.000 (B2B Beans)',
                'orders' => '24 Pengiriman B2B',
                'milk_stock' => 'N/A (Sentral)',
                'beans_stock' => '4.250 kg (Green) / 380 kg (Roasted)',
                'shift_leader' => 'Bramantyo W. (Head Roaster)',
            ],
            [
                'code' => 'JKT-SEN-01',
                'name' => 'Flagship Store Senopati (Jakarta Selatan)',
                'role' => 'Outlet Dine-in & Espresso Bar',
                'status' => 'OPERATIONAL BUSY',
                'revenue' => 'Rp 16.850.000',
                'orders' => '214 Orders',
                'milk_stock' => '18 / 50 pack (36%)',
                'beans_stock' => '28.5 kg Ready Barista',
                'shift_leader' => 'Rizky Ramadhan (Barista Shift 1)',
            ],
            [
                'code' => 'BDG-BRG-01',
                'name' => 'Branch Braga Heritage (Bandung)',
                'role' => 'Outlet Dine-in & Manual Brew',
                'status' => 'OPERATIONAL STABLE',
                'revenue' => 'Rp 12.400.000',
                'orders' => '158 Orders',
                'milk_stock' => '34 / 50 pack (68%)',
                'beans_stock' => '22.0 kg Ready Barista',
                'shift_leader' => 'Agung Pratama (Barista Shift 1)',
            ],
            [
                'code' => 'YGY-MLB-01',
                'name' => 'Branch Malioboro Sanctuary (Yogyakarta)',
                'role' => 'Outlet Dine-in & Co-Working',
                'status' => 'OPERATIONAL STABLE',
                'revenue' => 'Rp 9.200.000',
                'orders' => '110 Orders',
                'milk_stock' => '28 / 50 pack (56%)',
                'beans_stock' => '18.5 kg Ready Barista',
                'shift_leader' => 'Siti Nurhaliza (Barista Shift 1)',
            ],
        ];

        $hourlyTrends = [
            ['hour' => '07:00 - 09:00', 'label' => 'Morning Rush', 'amount' => 7450000, 'share' => '19.4%'],
            ['hour' => '09:00 - 12:00', 'label' => 'Brunch & Work', 'amount' => 8800000, 'share' => '22.9%'],
            ['hour' => '12:00 - 15:00', 'label' => 'Lunch & Meeting', 'amount' => 6900000, 'share' => '17.9%'],
            ['hour' => '15:00 - 18:00', 'label' => 'Afternoon Hangout', 'amount' => 6100000, 'share' => '15.9%'],
            ['hour' => '18:00 - 21:00', 'label' => 'Evening Social Rush', 'amount' => 9200000, 'share' => '23.9%'],
        ];

        $categoryShare = [
            ['name' => 'Espresso Based', 'pct' => 42, 'revenue' => 16149000, 'color' => '#d97706'],
            ['name' => 'Manual Brew Reserve', 'pct' => 26, 'revenue' => 9997000, 'color' => '#10b981'],
            ['name' => 'Roasted Beans Retail', 'pct' => 18, 'revenue' => 6921000, 'color' => '#f43f5e'],
            ['name' => 'Tea & Artisan Drinks', 'pct' => 8, 'revenue' => 3076000, 'color' => '#06b6d4'],
            ['name' => 'Pastry & Food', 'pct' => 6, 'revenue' => 2307000, 'color' => '#eab308'],
        ];

        return view('dashboard.index', compact('kpi', 'roastBatches', 'stockAlerts', 'branches', 'hourlyTrends', 'categoryShare', 'selectedBranch', 'kpiMatrix'));
    }

    /**
     * Create real consolidated emergency purchase order for critical safety stock items.
     */
    public function createConsolidatedPo(Request $request): JsonResponse
    {
        $user = auth()->user() ?? User::first();
        $supplier = Supplier::first();
        $branch = Branch::where('type', 'ROASTERY_HUB')->first() ?? Branch::first();

        // 5 Critical items from Safety Stock Alerts (ROP)
        $criticalItems = [
            ['name' => 'Green Beans Aceh Gayo Honey', 'qty' => 65.0, 'unit_price' => 125000],
            ['name' => 'Cold Cup PET 12oz + Flat Lid', 'qty' => 2000, 'unit_price' => 850],
            ['name' => 'Greenfields Fresh Milk Pasteurisasi 1L', 'qty' => 32, 'unit_price' => 24000],
            ['name' => 'Oatly Barista Edition Oat Milk 1L', 'qty' => 18, 'unit_price' => 42000],
            ['name' => 'Degassing Valve Foil Pouch 250g Matte Black', 'qty' => 300, 'unit_price' => 3200],
        ];

        $totalAmount = (float) array_sum(array_map(fn ($item) => $item['qty'] * $item['unit_price'], $criticalItems));
        $poNumber = 'PO-KONS-'.date('Ymd').'-'.str_pad((string) (PurchaseOrder::count() + 1), 3, '0', STR_PAD_LEFT);

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplier?->id ?? 1,
            'branch_id' => $branch?->id ?? 1,
            'user_id' => $user?->id ?? 1,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(3),
            'total_amount' => $totalAmount,
            'status' => 'ISSUED',
            'notes' => 'PO Konsolidasi Darurat 5 Item Kritis (Safety Stock Alert ROP)',
        ]);

        foreach ($criticalItems as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
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
            'message' => "PO Konsolidasi Darurat ({$poNumber}) berisi 5 item kritis senilai Rp ".number_format($totalAmount, 0, ',', '.').' berhasil diterbitkan & diteruskan ke vendor!',
        ]);
    }
}
