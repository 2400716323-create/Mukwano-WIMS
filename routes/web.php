<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\ErpExchangeController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PutAwayController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SystemSettingsController;
use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Authenticated application routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::post('offline/sync/goods-receipt', [OfflineSyncController::class, 'goodsReceipt'])
        ->name('offline.sync.goods-receipt');
    Route::post('offline/sync/dispatch/{dispatchRequest}', [OfflineSyncController::class, 'dispatch'])
        ->name('offline.sync.dispatch');
    Route::post('offline/sync/count/{stockCount}', [OfflineSyncController::class, 'count'])
        ->name('offline.sync.count');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:manage-administration')
        ->group(function () {
            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
            Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
            Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
            Route::patch('users/{user}/activate', [AdminUserController::class, 'updateStatus'])->name('users.activate');
            Route::patch('users/{user}/deactivate', [AdminUserController::class, 'updateStatus'])->name('users.deactivate');
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('settings', [SystemSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SystemSettingsController::class, 'update'])->name('settings.update');
        });

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{report}/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    Route::get('erp-exchange', [ErpExchangeController::class, 'index'])->name('erp-exchange.index');
    Route::post('erp-exchange/products/preview', [ErpExchangeController::class, 'preview'])
        ->name('erp-exchange.products.preview');
    Route::post('erp-exchange/products/{importId}/validate', [ErpExchangeController::class, 'validateMapping'])
        ->name('erp-exchange.products.validate');
    Route::post('erp-exchange/products/{importId}/confirm', [ErpExchangeController::class, 'confirm'])
        ->name('erp-exchange.products.confirm');
    Route::get('erp-exchange/products/{importId}/errors.csv', [ErpExchangeController::class, 'errors'])
        ->name('erp-exchange.products.errors');
    Route::get('erp-exchange/products/{importId}', [ErpExchangeController::class, 'show'])
        ->name('erp-exchange.products.show');
    Route::get('erp-exchange/reconciliation.csv', [ErpExchangeController::class, 'reconciliationCsv'])
        ->name('erp-exchange.reconciliation.csv');

    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    Route::resource('categories', CategoryController::class);
    Route::patch('categories/{category}/deactivate', [CategoryController::class, 'deactivate'])
        ->name('categories.deactivate');
    Route::patch('categories/{category}/activate', [CategoryController::class, 'activate'])
        ->name('categories.activate');

    Route::get('products/export', [ProductImportController::class, 'export'])->name('products.export');
    Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import');
    Route::get('product-images/{filename}', ProductImageController::class)
        ->where('filename', '[A-Za-z0-9][A-Za-z0-9._-]*')
        ->name('products.image');
    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::patch('products/{product}/deactivate', [ProductController::class, 'deactivate'])
        ->name('products.deactivate');

    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])
        ->name('suppliers.deactivate');

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])
        ->name('purchase-orders.edit');
    Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
        ->name('purchase-orders.update');
    Route::get('purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])
        ->name('purchase-orders.print');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('purchase-orders.approve');
    Route::patch('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->name('purchase-orders.cancel');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->name('purchase-orders.show');

    Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('goods-receipts/review', [GoodsReceiptController::class, 'review'])->name('goods-receipts.review');
    Route::get('goods-receipts/review/{token}', [GoodsReceiptController::class, 'showReview'])
        ->name('goods-receipts.review.show');
    Route::post('goods-receipts/review/{token}/confirm', [GoodsReceiptController::class, 'confirm'])
        ->name('goods-receipts.confirm');
    Route::get('goods-receipts/{goodsReceipt}/print', [GoodsReceiptController::class, 'print'])
        ->name('goods-receipts.print');
    Route::get('goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])
        ->name('goods-receipts.show');

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('stock-enquiry', [InventoryController::class, 'enquiry'])->name('inventory.enquiry');
    Route::get('stock-ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');
    Route::get('stock-transfers', [InventoryController::class, 'transfers'])->name('stock-transfers.create');
    Route::post('stock-transfers/{stockBalance}', [InventoryController::class, 'transfer'])
        ->name('stock-transfers.store');
    Route::get('put-away', [PutAwayController::class, 'index'])->name('put-away.index');
    Route::get('put-away/{stockBalance}', [PutAwayController::class, 'show'])->name('put-away.show');
    Route::post('stock-balances/{stockBalance}/put-away', [InventoryController::class, 'putAway'])
        ->name('stock-balances.put-away');

    Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

    Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
    Route::get('stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
    Route::post('stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
    Route::get('stock-counts/{stockCount}', [StockCountController::class, 'show'])->name('stock-counts.show');
    Route::post('stock-counts/{stockCount}/submit', [StockCountController::class, 'submit'])
        ->name('stock-counts.submit');
    Route::post('stock-counts/{stockCount}/review', [StockCountController::class, 'review'])
        ->name('stock-counts.review');
    Route::get('stock-count-settings', [StockCountController::class, 'settings'])
        ->name('stock-counts.settings');
    Route::put('stock-count-settings', [StockCountController::class, 'updateSettings'])
        ->name('stock-counts.settings.update');

    Route::get('dispatch-requests', [DispatchController::class, 'index'])->name('dispatch-requests.index');
    Route::get('dispatch-requests/create', [DispatchController::class, 'create'])->name('dispatch-requests.create');
    Route::post('dispatch-requests', [DispatchController::class, 'store'])->name('dispatch-requests.store');
    Route::get('dispatch-requests/{dispatchRequest}/pick-list', [DispatchController::class, 'pickList'])
        ->name('dispatch-requests.pick-list');
    Route::post('dispatch-requests/{dispatchRequest}/post', [DispatchController::class, 'post'])
        ->name('dispatch-requests.post');
    Route::get('dispatch-requests/{dispatchRequest}/gdn', [DispatchController::class, 'print'])
        ->name('dispatch-requests.print');

    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
    Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
    Route::get('locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
    Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
    Route::patch('locations/{location}/deactivate', [LocationController::class, 'deactivate'])
        ->name('locations.deactivate');
    Route::patch('locations/{location}/activate', [LocationController::class, 'activate'])
        ->name('locations.activate');
    Route::post('bin-inventory/{binInventory}/move', [LocationController::class, 'moveInventory'])
        ->name('bin-inventory.move');
    Route::get('locations/{location}/label', [QrCodeController::class, 'locationLabel'])
        ->name('locations.label');
    Route::get('products/{product}/label', [QrCodeController::class, 'productLabel'])
        ->name('products.label');

    Route::get('scan', [QrCodeController::class, 'scan'])->name('qr.scan');
    Route::post('scan/resolve', [QrCodeController::class, 'resolve'])->name('qr.resolve');
});
