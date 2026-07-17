<?php

namespace App\Providers;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Policies\CategoryPolicy;
use App\Domains\Catalog\Policies\ProductPolicy;
use App\Domains\Governance\Models\AuditLog;
use App\Domains\Governance\Policies\AuditLogPolicy;
use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\BranchPolicy;
use App\Domains\Identity\Policies\UserPolicy;
use App\Domains\Inventory\Models\GoodsReceipt;
use App\Domains\Inventory\Models\InventoryAdjustment;
use App\Domains\Inventory\Policies\GoodsReceiptPolicy;
use App\Domains\Inventory\Policies\InventoryAdjustmentPolicy;
use App\Domains\Planning\Models\ForecastRun;
use App\Domains\Planning\Models\ReorderPolicy;
use App\Domains\Planning\Models\RestockingAlert;
use App\Domains\Planning\Policies\ForecastRunPolicy;
use App\Domains\Planning\Policies\ReorderPolicyPolicy;
use App\Domains\Planning\Policies\RestockingAlertPolicy;
use App\Domains\Procurement\Models\PurchaseOrder;
use App\Domains\Procurement\Models\Supplier;
use App\Domains\Procurement\Policies\PurchaseOrderPolicy;
use App\Domains\Procurement\Policies\SupplierPolicy;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Policies\SalePolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(InventoryAdjustment::class, InventoryAdjustmentPolicy::class);
        Gate::policy(GoodsReceipt::class, GoodsReceiptPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(ForecastRun::class, ForecastRunPolicy::class);
        Gate::policy(ReorderPolicy::class, ReorderPolicyPolicy::class);
        Gate::policy(RestockingAlert::class, RestockingAlertPolicy::class);
    }
}
