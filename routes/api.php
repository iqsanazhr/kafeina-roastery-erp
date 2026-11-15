<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryDispatchController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\QualityControlController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - KAFEINA ERP
|--------------------------------------------------------------------------
*/

// Public Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-pin', [AuthController::class, 'verifyCashierPin']);
});

// Midtrans Webhook Callback
Route::post('/midtrans/webhook', [App\Http\Controllers\PosController::class, 'handleMidtransWebhook'])->name('api.midtrans.webhook');

// Authenticated Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/set-pin', [AuthController::class, 'setCashierPin'])->middleware('role:super_admin,store_manager,supervisor');
    });

    // SPRINT 2: Quality Control & Lot Tracking
    Route::prefix('qc')->middleware('role:super_admin,qc_inspector,procurement_officer')->group(function () {
        Route::get('/', [QualityControlController::class, 'index']);
        Route::post('/{lotId}/inspect', [QualityControlController::class, 'inspect']);
    });

    // SPRINT 3: Roastery Manufacturing & Yield Loss Engine
    Route::prefix('work-orders')->middleware('role:super_admin,head_roaster,roaster')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::post('/', [WorkOrderController::class, 'store']);
        Route::post('/{id}/start', [WorkOrderController::class, 'start']);
        Route::post('/{id}/complete', [WorkOrderController::class, 'complete']);
    });

    // SPRINT 4: Multi-Branch Inventory & FIFO Logistics
    Route::prefix('inventory')->middleware('role:super_admin,warehouse_staff,branch_cashier,store_manager')->group(function () {
        Route::post('/material-requests', [InventoryDispatchController::class, 'submitMaterialRequest']);
        Route::post('/material-requests/{id}/approve', [InventoryDispatchController::class, 'approveMaterialRequest'])
            ->middleware('role:super_admin,warehouse_staff,head_roaster');
        Route::post('/material-requests/{id}/dispatch', [InventoryDispatchController::class, 'dispatch'])
            ->middleware('role:super_admin,warehouse_staff');
        Route::post('/delivery-notes/{id}/verify', [InventoryDispatchController::class, 'verifyReceipt']);
    });

    // SPRINT 5: Web POS & Instant Auto-Deduct
    Route::prefix('pos')->middleware('role:super_admin,branch_cashier,store_manager,supervisor')->group(function () {
        Route::get('/shift/active', [PosController::class, 'activeShift']);
        Route::post('/shift/open', [PosController::class, 'openShift']);
        Route::post('/shift/{id}/close', [PosController::class, 'closeShift']);
        Route::post('/checkout', [PosController::class, 'checkout']);
        Route::post('/split-bill', [PosController::class, 'splitBill']);
    });
});
