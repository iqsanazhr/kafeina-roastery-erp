<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QcLabController;
use App\Http\Controllers\RoasteryController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\YieldLossController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication & Account Switching (Vercel Style)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('auth')->group(function () {
    Route::post('/switch-account', [AuthController::class, 'switchAccount'])->name('switch-account');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/switch-user/{user}', function (User $user) {
        $current = Auth::user();
        $isAllowed = $current && ($current->isSuperAdmin() || session()->has('impersonator_id'));

        if (! $isAllowed) {
            abort(403, 'Akses ditolak: Alih akun hanya diizinkan untuk Super Administrator.');
        }

        if ($current->isSuperAdmin() && ! session()->has('impersonator_id')) {
            session(['impersonator_id' => $current->id]);
        } elseif ((int) session('impersonator_id') === $user->id) {
            session()->forget('impersonator_id');
        }

        Auth::login($user);
        session(['active_user_id' => $user->id]);

        return redirect($user->canAccessModule('dashboard') ? route('dashboard.index') : ($user->canAccessModule('pos') ? route('pos.index') : ($user->canAccessModule('roastery') ? route('roastery.index') : ($user->canAccessModule('procurement') ? route('procurement.index') : ($user->canAccessModule('qc-lab') ? route('qc-lab.index') : route('inventory.index'))))));
    })->name('switch-user.get');
});

// Halaman Awal Mengarah Langsung ke Login
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Barista Touchscreen Web POS (TICK-041, TICK-045, TICK-046)
Route::prefix('pos')->name('pos.')->middleware('role:super_admin,cashier,supervisor')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    Route::post('/shift/open', [PosController::class, 'openShift'])->name('shift.open');
    Route::post('/shift/close', [PosController::class, 'closeShift'])->name('shift.close');
    Route::post('/redeem-code/validate', [PosController::class, 'validateRedeemCode'])->name('redeem.validate');
    Route::post('/redeem-code', [PosController::class, 'storeRedeemCode'])->name('redeem.store');
    Route::get('/redeem-codes', [PosController::class, 'getRedeemCodes'])->name('redeem.list');

    // Dynamic QRIS Midtrans Endpoints
    Route::post('/qris/generate', [PosController::class, 'generateQris'])->name('qris.generate');
    Route::get('/qris/status/{orderNumber}', [PosController::class, 'checkQrisStatus'])->name('qris.status');
    Route::post('/qris/simulate-pay', [PosController::class, 'simulateQrisPayment'])->name('qris.simulate');

    // Virtual Account / Bank Transfer Midtrans Endpoints
    Route::post('/va/generate', [PosController::class, 'generateVa'])->name('va.generate');
    Route::get('/va/status/{orderNumber}', [PosController::class, 'checkVaStatus'])->name('va.status');
    Route::post('/va/simulate-pay', [PosController::class, 'simulateVaPayment'])->name('va.simulate');

    // Sales History & Customer Orders
    Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders');
    Route::get('/orders/export-csv', [SalesOrderController::class, 'exportCsv'])->name('orders.export.csv');
    Route::get('/orders/{id}', [SalesOrderController::class, 'show'])->name('orders.show');
});

// Dedicated Sales Route Alias
Route::prefix('sales')->name('sales.')->middleware('role:super_admin,cashier,supervisor,procurement')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index'])->name('index');
    Route::get('/export-csv', [SalesOrderController::class, 'exportCsv'])->name('export.csv');
    Route::get('/{id}', [SalesOrderController::class, 'show'])->name('show');
});

// Dedicated Voucher & Promo Quota Management
Route::prefix('vouchers')->name('vouchers.')->middleware('role:super_admin,supervisor,store_manager,owner,admin')->group(function () {
    Route::get('/', [VoucherController::class, 'index'])->name('index');
    Route::post('/', [VoucherController::class, 'store'])->name('store');
    Route::put('/{voucher}', [VoucherController::class, 'update'])->name('update');
    Route::post('/{voucher}/quota', [VoucherController::class, 'updateQuota'])->name('quota');
    Route::post('/{voucher}/toggle', [VoucherController::class, 'toggle'])->name('toggle');
    Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
});

// Master Manajemen Produk, Resep Bahan Baku, Varian & Modifiers (RBAC Gate)
Route::prefix('products')->name('products.')->middleware('role:super_admin,store_manager,supervisor,roaster')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::post('/{product}/toggle', [ProductController::class, 'toggleActive'])->name('toggle');

    // Resep Bahan Baku (BOM Bar)
    Route::post('/{product}/recipes', [ProductController::class, 'storeRecipe'])->name('recipes.store');
    Route::delete('/recipes/{recipe}', [ProductController::class, 'destroyRecipe'])->name('recipes.destroy');

    // Varian Harga (Ukuran & Kemasan)
    Route::post('/{product}/variants', [ProductController::class, 'storeVariant'])->name('variants.store');
    Route::delete('/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');

    // Modifiers & Add-ons Barista
    Route::post('/modifiers', [ProductController::class, 'storeModifier'])->name('modifiers.store');
    Route::delete('/modifiers/{modifier}', [ProductController::class, 'destroyModifier'])->name('modifiers.destroy');

    // Kategori Menu
    Route::post('/categories', [ProductController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [ProductController::class, 'updateCategory'])->name('categories.update');
});

// Executive Dashboard & Roastery Analytics (TICK-050)
Route::prefix('dashboard')->name('dashboard.')->middleware('role:super_admin,supervisor,procurement')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::post('/consolidated-po', [DashboardController::class, 'createConsolidatedPo'])->name('consolidated-po');
});

// ==========================================
// 6 OPERASIONAL ROASTERY PORTALS (SEPARATED)
// ==========================================

// 1. Procurement / Pengadaan Biji Mentah & Mitra Petani
Route::prefix('procurement')->name('procurement.')->middleware('role:super_admin,procurement')->group(function () {
    Route::get('/', [ProcurementController::class, 'index'])->name('index');
    Route::post('/suppliers', [ProcurementController::class, 'storeSupplier'])->name('suppliers.store');
    Route::post('/supplier', [ProcurementController::class, 'storeSupplier'])->name('supplier.store');
    Route::post('/po', [ProcurementController::class, 'storePo'])->name('po.store');
    Route::post('/qc', [ProcurementController::class, 'storeQc'])->name('qc.store');
});

// 2. QC Lab / Laboratorium Mutu Fisik & Cupping Score
Route::prefix('qc-lab')->name('qc-lab.')->middleware('role:super_admin,qc_inspector,procurement')->group(function () {
    Route::get('/', [QcLabController::class, 'index'])->name('index');
    Route::post('/qc', [QcLabController::class, 'store'])->name('store');
    Route::post('/store', [QcLabController::class, 'store'])->name('qc.store');
});

// 3. Roastery / Penjadwalan Work Order & BOM Sangrai
Route::prefix('roastery')->name('roastery.')->middleware('role:super_admin,roaster')->group(function () {
    Route::get('/', [RoasteryController::class, 'index'])->name('index');
    Route::post('/work-orders', [RoasteryController::class, 'storeWorkOrder'])->name('work-orders.store');
    Route::post('/wo', [RoasteryController::class, 'storeWorkOrder'])->name('wo.store');
    Route::post('/execute', [RoasteryController::class, 'executeRoast'])->name('execute');
    Route::post('/work-orders/{id}/start', [RoasteryController::class, 'storeWorkOrder'])->name('work-orders.start');
    Route::post('/work-orders/{id}/complete', [RoasteryController::class, 'executeRoast'])->name('work-orders.complete');
});

// 4. Yield Loss / Monitoring Susut Sangrai & HPP Riil
Route::prefix('yield-loss')->name('yield-loss.')->middleware('role:super_admin,roaster')->group(function () {
    Route::get('/', [YieldLossController::class, 'index'])->name('index');
    Route::post('/execute', [RoasteryController::class, 'executeRoast'])->name('execute');
});

// 5. Inventori / Manajemen Stok Multi-Cabang & ROP
Route::prefix('inventory')->name('inventory.')->middleware('role:super_admin,warehouse,supervisor,roaster')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/stock-cards/export-csv', [InventoryController::class, 'exportStockCardsCsv'])->name('stock-cards.export.csv');
    Route::post('/material-requests', [InventoryController::class, 'storeMaterialRequest'])->name('material-requests.store');
    Route::post('/mr', [InventoryController::class, 'storeMaterialRequest'])->name('mr.store');
    Route::post('/delivery-notes', [InventoryController::class, 'storeDeliveryNote'])->name('dn.store');
    Route::post('/receipt/confirm', [InventoryController::class, 'confirmReceipt'])->name('receipt.confirm');
    Route::post('/material-requests/{id}/approve', [InventoryController::class, 'approveMaterialRequest'])->name('material-requests.approve');
    Route::post('/material-requests/{id}/dispatch', [InventoryController::class, 'dispatchTransfer'])->name('material-requests.dispatch');
    Route::post('/delivery-notes/{id}/verify', [InventoryController::class, 'confirmReceipt'])->name('delivery-notes.verify');
});

// 6. Logistik / Distribusi Antar-Cabang & Surat Jalan
Route::prefix('logistics')->name('logistics.')->middleware('role:super_admin,warehouse,supervisor')->group(function () {
    Route::get('/', [LogisticsController::class, 'index'])->name('index');
    Route::post('/mr', [InventoryController::class, 'storeMaterialRequest'])->name('mr.store');
    Route::post('/dn', [InventoryController::class, 'storeDeliveryNote'])->name('dn.store');
    Route::post('/receipt/confirm', [InventoryController::class, 'confirmReceipt'])->name('receipt.confirm');
    Route::post('/material-requests/{id}/approve', [InventoryController::class, 'approveMaterialRequest'])->name('material-requests.approve');
    Route::post('/material-requests/{id}/dispatch', [InventoryController::class, 'dispatchTransfer'])->name('material-requests.dispatch');
    Route::post('/delivery-notes/{id}/verify', [InventoryController::class, 'confirmReceipt'])->name('delivery-notes.verify');
});

// Financial Reports, Real P&L & Leakage Analysis (TICK-051, TICK-052, TICK-053)
Route::prefix('finance')->name('finance.')->middleware('role:super_admin')->group(function () {
    Route::get('/', [FinancialReportController::class, 'index'])->name('index');
    Route::get('/export-csv', [FinancialReportController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export-pdf', [FinancialReportController::class, 'exportPdf'])->name('export.pdf');
});

// System Settings & Master Data Management (Super Admin Exclusive)
Route::prefix('settings')->name('settings.')->middleware('role:super_admin')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/company', [SettingsController::class, 'updateCompany'])->name('company.update');
    Route::post('/branch', [SettingsController::class, 'storeBranch'])->name('branch.store');
    Route::put('/branch/{branch}', [SettingsController::class, 'updateBranch'])->name('branch.update');
    Route::post('/branch/{branch}/toggle', [SettingsController::class, 'toggleBranch'])->name('branch.toggle');
    Route::post('/warehouse', [SettingsController::class, 'storeWarehouse'])->name('warehouse.store');
    Route::put('/warehouse/{warehouse}', [SettingsController::class, 'updateWarehouse'])->name('warehouse.update');
    Route::post('/user', [SettingsController::class, 'storeUser'])->name('user.store');
    Route::put('/user/{user}', [SettingsController::class, 'updateUser'])->name('user.update');
    Route::post('/user/{user}/reset-password', [SettingsController::class, 'resetUserPassword'])->name('user.reset-password');
    Route::post('/user/{user}/reset-pin', [SettingsController::class, 'resetUserPin'])->name('user.reset-pin');
    Route::post('/user/{user}/toggle', [SettingsController::class, 'toggleUser'])->name('user.toggle');
    Route::post('/payment', [SettingsController::class, 'updatePayment'])->name('payment.update');
    Route::get('/database/backup', [SettingsController::class, 'downloadDatabaseBackup'])->name('database.backup');
    Route::get('/database/export/{type}', [SettingsController::class, 'exportMasterData'])->name('database.export');
    Route::post('/database/reset-local', [SettingsController::class, 'resetDatabaseLocal'])->name('database.reset-local');
});
