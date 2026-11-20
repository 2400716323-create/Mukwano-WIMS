<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ProcurementCommunicationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManualStockAdjustmentController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\ErpExchangeController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PutAwayController;
use App\Http\Controllers\PutAwayTaskController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StockKeeperController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierMessageController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\SupplierLeadController;

use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

// Authenticated application routes
Route::middleware('auth')->group(function () {

    // Dashboard and stock keeper
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('stock-keeper', [StockKeeperController::class, 'index'])
        ->name('stock-keeper.index');

    // Logout
    Route::post('logout', [AuthController::class, 'destroy'])
        ->name('logout');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [
        NotificationController::class,
        'markAsRead'
    ])->name('notifications.read');

    Route::post('/notifications/read-all', [
        NotificationController::class,
        'markAllAsRead'
    ])->name('notifications.read-all');

    // Procurement Communication Centre
    Route::get('/procurement-communications', [
        ProcurementCommunicationController::class,
        'index'
    ])->name('procurement-communications.index');

    Route::get('/procurement-communications/messages', [
        ProcurementCommunicationController::class,
        'messages'
    ])->name('procurement-communications.messages');

    // User profile
    Route::get('profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');

    // Offline synchronization
    Route::post('offline/sync/goods-receipt', [
        OfflineSyncController::class,
        'goodsReceipt'
    ])->name('offline.sync.goods-receipt');

    Route::post('offline/sync/dispatch/{dispatchRequest}', [
        OfflineSyncController::class,
        'dispatch'
    ])->name('offline.sync.dispatch');

    Route::post('offline/sync/count/{stockCount}', [
        OfflineSyncController::class,
        'count'
    ])->name('offline.sync.count');

    // Administration - Administrator only
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:manage-administration')
        ->group(function () {

            Route::get('users', [AdminUserController::class, 'index'])
                ->name('users.index');

            Route::post('users', [AdminUserController::class, 'store'])
                ->name('users.store');

            Route::patch('users/{user}/role', [
                AdminUserController::class,
                'updateRole'
            ])->name('users.role');

            Route::patch('users/{user}/status', [
                AdminUserController::class,
                'updateStatus'
            ])->name('users.status');

            Route::patch('users/{user}/activate', [
                AdminUserController::class,
                'updateStatus'
            ])->name('users.activate');

            Route::patch('users/{user}/deactivate', [
                AdminUserController::class,
                'updateStatus'
            ])->name('users.deactivate');

            Route::get('roles', [RoleController::class, 'index'])
                ->name('roles.index');

            Route::get('settings', [SystemSettingsController::class, 'edit'])
                ->name('settings.edit');

            Route::put('settings', [SystemSettingsController::class, 'update'])
                ->name('settings.update');
        });

    // Audit Log - Administrator and Auditor (read-only)
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:view-audit-log')
        ->group(function () {

            Route::get('audit-log', [AuditLogController::class, 'index'])
                ->name('audit.index');
        });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('reports/{report}', [ReportController::class, 'show'])
        ->name('reports.show');

    Route::get('reports/{report}/csv', [ReportController::class, 'csv'])
        ->name('reports.csv');

    Route::get('reports/{report}/pdf', [ReportController::class, 'pdf'])
        ->name('reports.pdf');

    // ERP exchange
    Route::get('erp-exchange', [ErpExchangeController::class, 'index'])
        ->name('erp-exchange.index');

    Route::post('erp-exchange/products/preview', [
        ErpExchangeController::class,
        'preview'
    ])->name('erp-exchange.products.preview');

    Route::post('erp-exchange/products/{importId}/validate', [
        ErpExchangeController::class,
        'validateMapping'
    ])->name('erp-exchange.products.validate');

    Route::post('erp-exchange/products/{importId}/confirm', [
        ErpExchangeController::class,
        'confirm'
    ])->name('erp-exchange.products.confirm');

    Route::get('erp-exchange/products/{importId}/errors.csv', [
        ErpExchangeController::class,
        'errors'
    ])->name('erp-exchange.products.errors');

    Route::get('erp-exchange/products/{importId}', [
        ErpExchangeController::class,
        'show'
    ])->name('erp-exchange.products.show');

    Route::get('erp-exchange/reconciliation.csv', [
        ErpExchangeController::class,
        'reconciliationCsv'
    ])->name('erp-exchange.reconciliation.csv');

    // Alerts
    Route::get('alerts', [AlertController::class, 'index'])
        ->name('alerts.index');

    Route::get('alerts/near-expiry', [AlertController::class, 'nearExpiry'])
        ->name('alerts.expiring');

    Route::patch('alerts/{alert}/resolve', [
        AlertController::class,
        'resolve'
    ])->name('alerts.resolve');

    // Categories
    Route::resource('categories', CategoryController::class);

    Route::patch('categories/{category}/deactivate', [
        CategoryController::class,
        'deactivate'
    ])->name('categories.deactivate');

    Route::patch('categories/{category}/activate', [
        CategoryController::class,
        'activate'
    ])->name('categories.activate');

    // Products
    Route::get('products/export', [ProductImportController::class, 'export'])
        ->name('products.export');

    Route::post('products/import', [ProductImportController::class, 'store'])
        ->name('products.import');

    Route::get('product-images/{filename}', ProductImageController::class)
        ->where('filename', '[A-Za-z0-9][A-Za-z0-9._-]*')
        ->name('products.image');

    Route::resource('products', ProductController::class)
        ->except(['destroy']);

    Route::patch('products/{product}/deactivate', [
        ProductController::class,
        'deactivate'
    ])->name('products.deactivate');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    Route::patch('suppliers/{supplier}/deactivate', [
        SupplierController::class,
        'deactivate'
    ])->name('suppliers.deactivate');

    // Purchase orders
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
        ->name('purchase-orders.index');

    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])
        ->name('purchase-orders.create');

    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
        ->name('purchase-orders.store');

    Route::get('purchase-orders/{purchaseOrder}/edit', [
        PurchaseOrderController::class,
        'edit'
    ])->name('purchase-orders.edit');

    Route::put('purchase-orders/{purchaseOrder}', [
        PurchaseOrderController::class,
        'update'
    ])->name('purchase-orders.update');

    Route::get('purchase-orders/{purchaseOrder}/print', [
        PurchaseOrderController::class,
        'print'
    ])->name('purchase-orders.print');

    Route::post('purchase-orders/{purchaseOrder}/approve', [
        PurchaseOrderController::class,
        'approve'
    ])->name('purchase-orders.approve');

    Route::patch('purchase-orders/{purchaseOrder}/cancel', [
        PurchaseOrderController::class,
        'cancel'
    ])->name('purchase-orders.cancel');

    Route::get('purchase-orders/{purchaseOrder}', [
        PurchaseOrderController::class,
        'show'
    ])->name('purchase-orders.show');

    // Goods receipts
    Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])
        ->name('goods-receipts.index');

    Route::get('goods-receipts/create', [GoodsReceiptController::class, 'create'])
        ->name('goods-receipts.create');

    Route::post('goods-receipts/review', [GoodsReceiptController::class, 'review'])
        ->name('goods-receipts.review');

    Route::get('goods-receipts/review/{token}', [
        GoodsReceiptController::class,
        'showReview'
    ])->name('goods-receipts.review.show');

    Route::post('goods-receipts/review/{token}/confirm', [
        GoodsReceiptController::class,
        'confirm'
    ])->name('goods-receipts.confirm');

    Route::get('goods-receipts/{goodsReceipt}/print', [
        GoodsReceiptController::class,
        'print'
    ])->name('goods-receipts.print');

    Route::get('goods-receipts/{goodsReceipt}', [
        GoodsReceiptController::class,
        'show'
    ])->name('goods-receipts.show');

    // Inventory
    Route::get('inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');

    Route::get('stock-enquiry', [InventoryController::class, 'enquiry'])
        ->name('inventory.enquiry');

    Route::get('stock-ledger', [InventoryController::class, 'ledger'])
        ->name('inventory.ledger');

    Route::get('stock-transfers', [InventoryController::class, 'transfers'])
        ->name('stock-transfers.create');

    Route::post('stock-transfers/{stockBalance}', [
        InventoryController::class,
        'transfer'
    ])->name('stock-transfers.store');

    // Put-away
    Route::get('put-away', [PutAwayController::class, 'index'])
        ->name('put-away.index');

    Route::get('put-away/{stockBalance}/tasks/create', [
        PutAwayTaskController::class,
        'create'
    ])->name('put-away.tasks.create');

    Route::post('put-away/{stockBalance}/tasks', [
        PutAwayTaskController::class,
        'store'
    ])->name('put-away.tasks.store');

    Route::patch('put-away-tasks/{putAwayTask}/start', [
        PutAwayTaskController::class,
        'start'
    ])->name('put-away.tasks.start');

    Route::patch('put-away-tasks/{putAwayTask}/complete', [
        PutAwayTaskController::class,
        'complete'
    ])->name('put-away.tasks.complete');

    Route::get('put-away/{stockBalance}', [
        PutAwayController::class,
        'show'
    ])->name('put-away.show');

    Route::post('stock-balances/{stockBalance}/put-away', [
        InventoryController::class,
        'putAway'
    ])->name('stock-balances.put-away');

    // Batches
    Route::get('batches', [BatchController::class, 'index'])
        ->name('batches.index');

    Route::get('batches/{batch}', [BatchController::class, 'show'])
        ->name('batches.show');

    // Stock counts
    Route::get('stock-counts', [StockCountController::class, 'index'])
        ->name('stock-counts.index');

    Route::get('stock-counts/create', [StockCountController::class, 'create'])
        ->name('stock-counts.create');

    Route::post('stock-counts', [StockCountController::class, 'store'])
        ->name('stock-counts.store');

    Route::get('stock-counts/{stockCount}', [StockCountController::class, 'show'])
        ->name('stock-counts.show');

    Route::post('stock-counts/{stockCount}/submit', [
        StockCountController::class,
        'submit'
    ])->name('stock-counts.submit');

    Route::post('stock-counts/{stockCount}/save-draft', [
        StockCountController::class,
        'saveDraft'
    ])->name('stock-counts.save-draft');

    Route::post('stock-counts/{stockCount}/review', [
        StockCountController::class,
        'review'
    ])->name('stock-counts.review');

    Route::get('stock-count-settings', [
        StockCountController::class,
        'settings'
    ])->name('stock-counts.settings');

    Route::put('stock-count-settings', [
        StockCountController::class,
        'updateSettings'
    ])->name('stock-counts.settings.update');

    // Dispatch
    Route::get('dispatch-requests', [DispatchController::class, 'index'])
        ->name('dispatch-requests.index');

    Route::get('dispatch-requests/create', [DispatchController::class, 'create'])
        ->name('dispatch-requests.create');

    Route::post('dispatch-requests', [DispatchController::class, 'store'])
        ->name('dispatch-requests.store');

    Route::get('dispatch-requests/{dispatchRequest}/pick-list', [
        DispatchController::class,
        'pickList'
    ])->name('dispatch-requests.pick-list');

    Route::post('dispatch-requests/{dispatchRequest}/post', [
        DispatchController::class,
        'post'
    ])->name('dispatch-requests.post');

    Route::get('dispatch-requests/{dispatchRequest}/gdn', [
        DispatchController::class,
        'print'
    ])->name('dispatch-requests.print');

    // Locations and bin inventory
    Route::get('locations', [LocationController::class, 'index'])
        ->name('locations.index');

    Route::get('locations/create', [LocationController::class, 'create'])
        ->name('locations.create');

    Route::post('locations', [LocationController::class, 'store'])
        ->name('locations.store');

    Route::get('locations/{location}/edit', [
        LocationController::class,
        'edit'
    ])->name('locations.edit');

    Route::put('locations/{location}', [
        LocationController::class,
        'update'
    ])->name('locations.update');

    Route::get('locations/{location}', [
        LocationController::class,
        'show'
    ])->name('locations.show');

    Route::patch('locations/{location}/deactivate', [
        LocationController::class,
        'deactivate'
    ])->name('locations.deactivate');

    Route::patch('locations/{location}/activate', [
        LocationController::class,
        'activate'
    ])->name('locations.activate');

    Route::post('bin-inventory/{binInventory}/move', [
        LocationController::class,
        'moveInventory'
    ])->name('bin-inventory.move');

    Route::get('locations/{location}/label', [
        QrCodeController::class,
        'locationLabel'
    ])->name('locations.label');

    Route::get('products/{product}/label', [
        QrCodeController::class,
        'productLabel'
    ])->name('products.label');

    // QR code scanning
    Route::get('scan', [QrCodeController::class, 'scan'])
        ->name('qr.scan');

    Route::post('scan/resolve', [QrCodeController::class, 'resolve'])
        ->name('qr.resolve');

    // Manual Stock Adjustment Requests and Approvals
    Route::get('manual-stock-adjustments', [
        ManualStockAdjustmentController::class,
        'index'
    ])->name('manual-stock-adjustments.index');

    Route::get('manual-stock-adjustments/create', [
        ManualStockAdjustmentController::class,
        'create'
    ])->name('manual-stock-adjustments.create');

    Route::post('manual-stock-adjustments', [
        ManualStockAdjustmentController::class,
        'store'
    ])->name('manual-stock-adjustments.store');

    Route::get('manual-stock-adjustments/{manualStockAdjustment}', [
        ManualStockAdjustmentController::class,
        'show'
    ])->name('manual-stock-adjustments.show');

    Route::post('manual-stock-adjustments/{manualStockAdjustment}/review', [
        ManualStockAdjustmentController::class,
        'review'
    ])->name('manual-stock-adjustments.review');

    // Supplier internal messaging
    Route::get('/suppliers/{supplier}/messages', [
        SupplierMessageController::class,
        'index'
    ])->name('suppliers.messages.index');

    Route::post('/suppliers/{supplier}/messages', [
        SupplierMessageController::class,
        'store'
    ])->name('suppliers.messages.store');

    Route::post('/suppliers/{supplier}/messages/{supplierMessage}/read', [
        SupplierMessageController::class,
        'markRead'
    ])->name('suppliers.messages.read');
});

// Supplier Discovery Pool
Route::middleware('auth')
    ->prefix('supplier-pool')
    ->name('supplier-pool.')
    ->group(function () {

        Route::get('/', [SupplierLeadController::class, 'index'])
            ->name('index');

        Route::get('/download-workbook', [
            SupplierLeadController::class,
            'downloadWorkbook'
        ])->name('workbook');

        Route::get('/create', [SupplierLeadController::class, 'create'])
            ->name('create');

        Route::post('/', [SupplierLeadController::class, 'store'])
            ->name('store');

        Route::get('/{supplierLead}/edit', [SupplierLeadController::class, 'edit'])
            ->name('edit');

        Route::put('/{supplierLead}', [SupplierLeadController::class, 'update'])
            ->name('update');

        Route::post('/import', [SupplierLeadController::class, 'import'])
            ->name('import');
    });
