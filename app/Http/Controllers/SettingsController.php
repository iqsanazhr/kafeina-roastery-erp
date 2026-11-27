<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\QualityControl;
use App\Models\Role;
use App\Models\StockCard;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkOrder;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    /**
     * Ensure current user has super_admin permission before accessing any setting.
     */
    protected function authorizeSuperAdmin(): User
    {
        $user = Auth::user();
        if (! $user) {
            $activeUserId = session('active_user_id', 1);
            $user = User::with('role')->find($activeUserId) ?? User::with('role')->first();
        }

        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Modul Pengaturan Sistem hanya dapat diakses oleh peran Super Administrator.');
        }

        return $user;
    }

    /**
     * Display the settings dashboard with the active tab.
     */
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $activeTab = $request->query('tab', 'branches');
        if (! in_array($activeTab, ['branches', 'warehouses', 'users', 'payment', 'database'], true)) {
            $activeTab = 'branches';
        }

        // 1. Data for Branches Tab
        $branches = Branch::withCount(['warehouses', 'users'])->get();

        // 2. Data for Warehouses Tab
        $warehouses = InventoryWarehouse::with('branch')
            ->withCount('stocks')
            ->orderBy('branch_id')
            ->get();

        // 3. Data for Users / RBAC Tab
        $users = User::with(['role', 'branch'])
            ->orderBy('role_id')
            ->orderBy('name')
            ->get();
        $roles = Role::orderBy('id')->get();

        // 4. Data for Payment & Settings Tab
        $settingsGrouped = SystemSetting::all()->groupBy('group');

        // 5. Data for Database & Audit Tab
        $auditLogs = AuditLog::with('user')
            ->latest('id')
            ->limit(35)
            ->get();

        $tableStats = [
            'branches' => Branch::count(),
            'inventory_warehouses' => InventoryWarehouse::count(),
            'users' => User::count(),
            'products' => Product::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'green_bean_lots' => GreenBeanLot::count(),
            'quality_controls' => QualityControl::count(),
            'work_orders' => WorkOrder::count(),
            'delivery_notes' => DeliveryNote::count(),
            'pos_orders' => PosOrder::count(),
            'stock_cards' => StockCard::count(),
            'audit_logs' => AuditLog::count(),
        ];

        // Database size calculation (MySQL)
        $dbSizeMb = '0.00';
        try {
            $dbName = DB::getDatabaseName();
            $sizeRow = DB::selectOne('
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ', [$dbName]);

            if ($sizeRow && isset($sizeRow->db_size_mb)) {
                $dbSizeMb = (string) $sizeRow->db_size_mb;
            }
        } catch (Exception) {
            $dbSizeMb = 'N/A';
        }

        return view('settings.index', compact(
            'activeTab',
            'branches',
            'warehouses',
            'users',
            'roles',
            'settingsGrouped',
            'auditLogs',
            'tableStats',
            'dbSizeMb'
        ));
    }

    /**
     * Update Company Profile Information.
     */
    public function updateCompany(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_npwp' => ['nullable', 'string', 'max:50'],
            'company_address' => ['required', 'string', 'max:300'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_email' => ['required', 'email', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value, 'company');
        }

        return redirect()->route('settings.index', ['tab' => 'branches'])
            ->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    /**
     * Store a new branch.
     */
    public function storeBranch(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:CENTRAL_ROASTERY,RETAIL_BRANCH'],
            'address' => ['required', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = true;

        Branch::create($validated);

        return redirect()->route('settings.index', ['tab' => 'branches'])
            ->with('success', "Cabang baru [{$validated['code']}] {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Update an existing branch.
     */
    public function updateBranch(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:CENTRAL_ROASTERY,RETAIL_BRANCH'],
            'address' => ['required', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $branch->update($validated);

        return redirect()->route('settings.index', ['tab' => 'branches'])
            ->with('success', "Cabang [{$branch->code}] berhasil diperbarui.");
    }

    /**
     * Toggle branch active status (Soft Deactivation).
     */
    public function toggleBranch(Branch $branch): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if ($branch->code === 'HQ-CENTRAL' && $branch->is_active) {
            return back()->with('error', 'Pusat Roastery Sentral (HQ-CENTRAL) tidak dapat dinonaktifkan.');
        }

        $branch->update(['is_active' => ! $branch->is_active]);
        $statusStr = $branch->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('settings.index', ['tab' => 'branches'])
            ->with('success', "Cabang [{$branch->code}] berhasil {$statusStr}.");
    }

    /**
     * Store a new warehouse.
     */
    public function storeWarehouse(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:inventory_warehouses,code'],
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:CENTRAL_RAW,CENTRAL_ROASTED,TRANSIT,RETAIL_BAR'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        InventoryWarehouse::create($validated);

        return redirect()->route('settings.index', ['tab' => 'warehouses'])
            ->with('success', "Gudang baru [{$validated['code']}] {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Update an existing warehouse.
     */
    public function updateWarehouse(Request $request, InventoryWarehouse $warehouse): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:CENTRAL_RAW,CENTRAL_ROASTED,TRANSIT,RETAIL_BAR'],
        ]);

        $warehouse->update($validated);

        return redirect()->route('settings.index', ['tab' => 'warehouses'])
            ->with('success', "Gudang [{$warehouse->code}] berhasil diperbarui.");
    }

    /**
     * Store a new user account.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'password' => ['required', 'string', 'min:8'],
            'pin_code' => ['nullable', 'digits:6'],
        ]);

        $user = new User;
        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->role_id = (int) $validated['role_id'];
        $user->branch_id = (int) $validated['branch_id'];
        $user->password = Hash::make($validated['password']);
        $user->pin_code = ! empty($validated['pin_code']) ? Hash::make($validated['pin_code']) : null;
        $user->is_active = true;
        $user->save();

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Akun staf {$user->name} ({$user->email}) berhasil dibuat.");
    }

    /**
     * Update user details and assignments.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', "unique:users,email,{$user->id}"],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['email'] = strtolower(trim($validated['email']));
        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Data staf {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset user password.
     */
    public function resetUserPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Password untuk akun {$user->email} berhasil direset.");
    }

    /**
     * Reset user cashier PIN.
     */
    public function resetUserPin(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'new_pin' => ['required', 'digits:6'],
        ]);

        $user->update([
            'pin_code' => Hash::make($request->input('new_pin')),
            'pin_failed_attempts' => 0,
            'pin_locked_until' => null,
        ]);

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "PIN 6-digit untuk akun {$user->name} berhasil direset.");
    }

    /**
     * Toggle user active status.
     */
    public function toggleUser(User $user): RedirectResponse
    {
        $superAdmin = $this->authorizeSuperAdmin();

        if ($user->id === $superAdmin->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Akun {$user->name} berhasil {$statusStr}.");
    }

    /**
     * Update Payment & System Parameters.
     */
    public function updatePayment(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'midtrans_is_production' => ['sometimes', 'boolean'],
            'tax_pb1_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:30'],
            'roastery_min_yield_loss' => ['required', 'numeric', 'min:5', 'max:30'],
            'roastery_max_yield_loss' => ['required', 'numeric', 'min:5', 'max:40'],
            'inventory_default_safety_stock' => ['required', 'numeric', 'min:1', 'max:5000'],
        ]);

        if ($request->filled('midtrans_server_key')) {
            SystemSetting::set('midtrans_server_key', $request->input('midtrans_server_key'), 'payment', 'string', 'Midtrans Server Key');
        }

        if ($request->filled('midtrans_client_key')) {
            SystemSetting::set('midtrans_client_key', $request->input('midtrans_client_key'), 'payment', 'string', 'Midtrans Client Key', null);
        }

        SystemSetting::set('midtrans_is_production', $request->boolean('midtrans_is_production'), 'payment', 'boolean');
        SystemSetting::set('tax_pb1_rate', (float) $validated['tax_pb1_rate'], 'tax', 'decimal');
        SystemSetting::set('service_charge_rate', (float) $validated['service_charge_rate'], 'tax', 'decimal');
        SystemSetting::set('roastery_min_yield_loss', (float) $validated['roastery_min_yield_loss'], 'roastery', 'decimal');
        SystemSetting::set('roastery_max_yield_loss', (float) $validated['roastery_max_yield_loss'], 'roastery', 'decimal');
        SystemSetting::set('inventory_default_safety_stock', (float) $validated['inventory_default_safety_stock'], 'inventory', 'decimal');

        return redirect()->route('settings.index', ['tab' => 'payment'])
            ->with('success', 'Konfigurasi pembayaran, pajak, dan parameter roastery berhasil diperbarui.');
    }

    /**
     * Download a complete SQL dump of the database.
     */
    public function downloadDatabaseBackup(): StreamedResponse
    {
        $this->authorizeSuperAdmin();

        $fileName = 'kafeina_erp_backup_'.date('Y_m_d_His').'.sql';

        return response()->streamDownload(function () {
            echo "-- ==========================================================\n";
            echo "-- KAFEINA SPECIALTY ROASTERY ERP - DATABASE BACKUP DUMP\n";
            echo '-- Generated at: '.date('Y-m-d H:i:s')."\n";
            echo '-- Database: '.DB::getDatabaseName()."\n";
            echo "-- ==========================================================\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = [
                'roles',
                'branches',
                'users',
                'suppliers',
                'product_categories',
                'products',
                'product_recipes',
                'product_variants',
                'product_modifiers',
                'boms',
                'bom_items',
                'inventory_warehouses',
                'warehouse_stocks',
                'green_bean_lots',
                'purchase_orders',
                'purchase_order_items',
                'quality_controls',
                'roasting_profiles',
                'work_orders',
                'stock_cards',
                'material_requests',
                'material_request_items',
                'delivery_notes',
                'delivery_note_items',
                'pos_shifts',
                'pos_orders',
                'pos_order_items',
                'recipe_deductions',
                'redeem_codes',
                'audit_logs',
                'system_settings',
            ];

            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                echo "-- --------------------------------------------------------\n";
                echo "-- Table structure and data for `{$table}`\n";
                echo "-- --------------------------------------------------------\n";

                $rows = DB::table($table)->get();
                if ($rows->isEmpty()) {
                    echo "-- Table `{$table}` is empty.\n\n";

                    continue;
                }

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns = array_keys($rowArray);
                    $escapedColumns = array_map(fn ($c) => "`{$c}`", $columns);
                    $escapedValues = array_map(function ($val) {
                        if ($val === null) {
                            return 'NULL';
                        }

                        return "'".addslashes((string) $val)."'";
                    }, array_values($rowArray));

                    echo 'INSERT INTO `'.$table.'` ('.implode(', ', $escapedColumns).') VALUES ('.implode(', ', $escapedValues).");\n";
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- Backup completed successfully.\n";
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export master entity data as CSV.
     */
    public function exportMasterData(string $type): StreamedResponse
    {
        $this->authorizeSuperAdmin();

        $allowedTypes = ['branches', 'warehouses', 'users', 'products', 'audit_logs'];
        if (! in_array($type, $allowedTypes, true)) {
            abort(404, 'Tipe ekspor tidak ditemukan.');
        }

        $fileName = "kafeina_{$type}_export_".date('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'branches') {
                fputcsv($handle, ['ID', 'Kode', 'Nama Cabang', 'Tipe', 'Alamat', 'Telepon', 'Status Aktif']);
                Branch::orderBy('id')->chunk(100, function ($branches) use ($handle) {
                    foreach ($branches as $b) {
                        fputcsv($handle, [$b->id, $b->code, $b->name, $b->type, $b->address, $b->phone, $b->is_active ? 'Aktif' : 'Nonaktif']);
                    }
                });
            } elseif ($type === 'warehouses') {
                fputcsv($handle, ['ID', 'Kode', 'Nama Gudang', 'Cabang Induk', 'Tipe Gudang']);
                InventoryWarehouse::with('branch')->orderBy('id')->chunk(100, function ($whs) use ($handle) {
                    foreach ($whs as $w) {
                        fputcsv($handle, [$w->id, $w->code, $w->name, $w->branch?->name ?? '-', $w->type]);
                    }
                });
            } elseif ($type === 'users') {
                fputcsv($handle, ['ID', 'Nama Lengkap', 'Email', 'Role', 'Cabang Penugasan', 'Status']);
                User::with(['role', 'branch'])->orderBy('id')->chunk(100, function ($users) use ($handle) {
                    foreach ($users as $u) {
                        fputcsv($handle, [$u->id, $u->name, $u->email, $u->role?->display_name ?? '-', $u->branch?->name ?? '-', $u->is_active ? 'Aktif' : 'Nonaktif']);
                    }
                });
            } elseif ($type === 'products') {
                fputcsv($handle, ['ID', 'SKU', 'Nama Produk', 'Kategori', 'Harga Jual', 'Status Aktif']);
                Product::with('category')->orderBy('id')->chunk(100, function ($products) use ($handle) {
                    foreach ($products as $p) {
                        fputcsv($handle, [$p->id, $p->sku, $p->name, $p->category?->name ?? '-', (float) $p->selling_price, $p->is_active ? 'Aktif' : 'Nonaktif']);
                    }
                });
            } elseif ($type === 'audit_logs') {
                fputcsv($handle, ['ID', 'Waktu', 'Event', 'Entitas', 'ID Entitas', 'User Email', 'IP Address']);
                AuditLog::with('user')->latest('id')->limit(500)->chunk(100, function ($logs) use ($handle) {
                    foreach ($logs as $l) {
                        fputcsv($handle, [$l->id, $l->created_at, $l->event_type, class_basename($l->auditable_type), $l->auditable_id, $l->user?->email ?? 'System', $l->ip_address]);
                    }
                });
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Danger zone: Reset and reseed database in local environment only.
     */
    public function resetDatabaseLocal(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if (! app()->environment('local')) {
            abort(403, 'Aksi Berbahaya: Reset database otomatis hanya diizinkan pada environment LOCAL.');
        }

        $request->validate([
            'confirmation_text' => ['required', 'string'],
        ]);

        if (trim($request->input('confirmation_text')) !== 'RESET-KAFEINA-LOCAL') {
            return back()->with('error', 'Teks konfirmasi salah. Harap ketik "RESET-KAFEINA-LOCAL" secara persis.');
        }

        Artisan::call('migrate:fresh', ['--seed' => true]);

        return redirect()->route('settings.index', ['tab' => 'database'])
            ->with('success', 'Database berhasil direset dan di-seed ulang dengan data kanonikal standar.');
    }
}
