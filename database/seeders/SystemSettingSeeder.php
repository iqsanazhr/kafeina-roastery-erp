<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // Company Profile
            [
                'group' => 'company',
                'key' => 'company_name',
                'value' => 'PT Kafeina Roastery Indonesia',
                'type' => 'string',
                'label' => 'Nama Legal Perusahaan',
                'description' => 'Nama resmi perseroan terbatas pemilik lisensi KAFEINA ERP.',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_npwp',
                'value' => '01.234.567.8-012.000',
                'type' => 'string',
                'label' => 'Nomor Pokok Wajib Pajak (NPWP)',
                'description' => 'NPWP pusat pelaporan pajak restoran PB1 dan faktur pembelian petani.',
                'is_public' => false,
            ],
            [
                'group' => 'company',
                'key' => 'company_address',
                'value' => 'Kawasan Industri Cilandak Commercial Estate Kav. 12, Jakarta Selatan',
                'type' => 'string',
                'label' => 'Alamat Kantor Pusat & Pabrik Sentral',
                'description' => 'Domisili resmi kantor pusat dan central roastery lab hub.',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_phone',
                'value' => '021-7890123',
                'type' => 'string',
                'label' => 'Telepon Hotline / Layanan Pelanggan',
                'description' => 'Nomor resmi saluran pengaduan dan kontak cabang.',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_email',
                'value' => 'management@kafeina.id',
                'type' => 'string',
                'label' => 'Email Resmi Manajemen',
                'description' => 'Email korporat untuk notifikasi dan audit laporan.',
                'is_public' => true,
            ],

            // Payment & Midtrans
            [
                'group' => 'payment',
                'key' => 'midtrans_server_key',
                'value' => env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-ijgspUmhyrjXM.WH3CE.x-dV'),
                'type' => 'string',
                'label' => 'Midtrans Server Key',
                'description' => 'Kunci rahasia API Midtrans untuk pembuatan QRIS dan verifikasi webhook.',
                'is_public' => false,
            ],
            [
                'group' => 'payment',
                'key' => 'midtrans_client_key',
                'value' => env('MIDTRANS_CLIENT_KEY', 'Mid-client-5Qt0QqyW2YU0CFah'),
                'type' => 'string',
                'label' => 'Midtrans Client Key',
                'description' => 'Kunci publik Midtrans untuk pemanggilan Snap JS frontend.',
                'is_public' => true,
            ],
            [
                'group' => 'payment',
                'key' => 'midtrans_is_production',
                'value' => env('MIDTRANS_IS_PRODUCTION', false) ? '1' : '0',
                'type' => 'boolean',
                'label' => 'Mode Transaksi Midtrans',
                'description' => 'Nonaktifkan untuk mode Sandbox (pengujian), aktifkan untuk Production (uang riil).',
                'is_public' => true,
            ],

            // Tax & Financial
            [
                'group' => 'tax',
                'key' => 'tax_pb1_rate',
                'value' => '10.0',
                'type' => 'decimal',
                'label' => 'Tarif Pajak Restoran (PB1 %)',
                'description' => 'Persentase pajak daerah yang otomatis dihitung pada setiap transaksi kasir POS.',
                'is_public' => true,
            ],
            [
                'group' => 'tax',
                'key' => 'service_charge_rate',
                'value' => '0.0',
                'type' => 'decimal',
                'label' => 'Tarif Service Charge (%)',
                'description' => 'Biaya layanan tambahan untuk pesanan dine-in di gerai ritel.',
                'is_public' => true,
            ],

            // Roastery Operational Parameters
            [
                'group' => 'roastery',
                'key' => 'roastery_min_yield_loss',
                'value' => '12.0',
                'type' => 'decimal',
                'label' => 'Batas Bawah Susut Ideal (%)',
                'description' => 'Batas minimum persentase susut bobot sangrai green ke roasted.',
                'is_public' => false,
            ],
            [
                'group' => 'roastery',
                'key' => 'roastery_max_yield_loss',
                'value' => '18.0',
                'type' => 'decimal',
                'label' => 'Batas Atas Susut Ideal (%)',
                'description' => 'Batas maksimum persentase susut bobot sangrai green ke roasted.',
                'is_public' => false,
            ],
            [
                'group' => 'inventory',
                'key' => 'inventory_default_safety_stock',
                'value' => '100.0',
                'type' => 'decimal',
                'label' => 'Ambang Peringatan Stok Minimum Default (kg)',
                'description' => 'Batas stok minimum untuk memicu badge peringatan restock di modul inventori.',
                'is_public' => false,
            ],
        ];

        foreach ($defaults as $item) {
            SystemSetting::updateOrCreate(
                ['key' => $item['key']],
                $item
            );
        }
    }
}
