<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\PurchaseOrder;
use App\Models\QualityControl;
use App\Models\RoastingProfile;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use App\Observers\AuditLogObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_starts_with((string) config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        Branch::observe(AuditLogObserver::class);
        InventoryWarehouse::observe(AuditLogObserver::class);
        SystemSetting::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
        Supplier::observe(AuditLogObserver::class);
        PurchaseOrder::observe(AuditLogObserver::class);
        QualityControl::observe(AuditLogObserver::class);
        GreenBeanLot::observe(AuditLogObserver::class);
        WorkOrder::observe(AuditLogObserver::class);
        RoastingProfile::observe(AuditLogObserver::class);
        WarehouseStock::observe(AuditLogObserver::class);
        MaterialRequest::observe(AuditLogObserver::class);
        DeliveryNote::observe(AuditLogObserver::class);
        PosShift::observe(AuditLogObserver::class);
        PosOrder::observe(AuditLogObserver::class);

        // Share active user and role accounts across all views (Vercel Style)
        View::composer('*', function ($view) {
            $activeUserId = session('active_user_id', 1);
            $currentUser = Auth::user()
                ?? User::with(['role', 'branch'])->find($activeUserId)
                ?? User::with(['role', 'branch'])->first();
            $allUsers = User::with(['role', 'branch'])->where('is_active', true)->get();

            $view->with('currentUser', $currentUser);
            $view->with('allUsers', $allUsers);
        });
    }
}
