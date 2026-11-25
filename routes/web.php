<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pos\PosController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// --- Rutas protegidas con autenticación -------------------------------------
Route::middleware('auth')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Productos -------------------------------------------------------------
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class)->except(['create', 'show', 'edit']);
    Route::patch('/products/{product}/toggle-availability', [\App\Http\Controllers\Admin\ProductController::class, 'toggleAvailability'])->name('products.toggleAvailability');

    // --- Proveedores -----------------------------------------------------------
    Route::resource('suppliers', \App\Http\Controllers\Admin\SupplierController::class)->except(['create', 'show', 'edit']);
    Route::get('/suppliers/{supplier}/catalog', [\App\Http\Controllers\Admin\SupplierController::class, 'catalog'])->name('suppliers.catalog');
    Route::post('/suppliers/{supplier}/catalog', [\App\Http\Controllers\Admin\SupplierController::class, 'syncCatalog'])->name('suppliers.catalog.sync');
    Route::post('/suppliers/{supplier}/costs', [\App\Http\Controllers\Admin\SupplierController::class, 'updateCosts'])->name('suppliers.costs.update');

    // --- Pedidos de resurtido y Entrada de Mercadería ---------------------------
    Route::resource('product-orders', \App\Http\Controllers\Admin\ProductOrderController::class)->only(['index', 'store'])->names('product-orders');
    Route::get('/product-orders/{order}/pdf', [\App\Http\Controllers\Admin\ProductOrderController::class, 'pdf'])->name('product-orders.pdf');
    Route::patch('/product-orders/{order}/apply', [\App\Http\Controllers\Admin\ProductOrderController::class, 'apply'])->name('product-orders.apply');
    Route::get('/product-orders/{order}/goods-entry', [\App\Http\Controllers\Admin\GoodsEntryController::class, 'show'])->name('product-orders.goods-entry');
    Route::post('/product-orders/{order}/goods-entry', [\App\Http\Controllers\Admin\GoodsEntryController::class, 'store'])->name('product-orders.goods-entry.store');
    Route::get('/goods-entries/{entry}/pdf', [\App\Http\Controllers\Admin\GoodsEntryController::class, 'pdf'])->name('goods-entries.pdf');
    Route::get('/debit-notes/{debitNote}/pdf', [\App\Http\Controllers\Admin\GoodsEntryController::class, 'debitNotePdf'])->name('debit-notes.pdf');

    // --- Bajas de productos ---------------------------------------------------
    Route::get('/discards', [\App\Http\Controllers\Admin\DiscardController::class, 'index'])->name('discards.index');
    Route::post('/discards', [\App\Http\Controllers\Admin\DiscardController::class, 'store'])->name('discards.store');
    Route::patch('/discards/{discard}/decide', [\App\Http\Controllers\Admin\DiscardController::class, 'decide'])->name('discards.decide');
    Route::get('/discards/{discard}/pdf', [\App\Http\Controllers\Admin\DiscardController::class, 'pdf'])->name('discards.pdf');

    // --- Movimientos de Inventario ---------------------------------------------
    Route::get('/inventory-logs', [\App\Http\Controllers\Admin\InventoryLogController::class, 'index'])->name('inventory-logs.index');

        // --- Desbloqueo de Terminal -----------------------------------------------
    Route::post('/unlock-terminal', function (\Illuminate\Http\Request $request) {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta. Intenta nuevamente.'], 422);
        }

        return response()->json(['success' => true]);
    })->middleware('throttle:6,1')->name('unlock.terminal');

    // --- Gestionar Carousel ---------------------------------------------------
    Route::get('/carousel', [\App\Http\Controllers\Admin\CarouselController::class, 'index'])->name('carousel.index');
    Route::post('/carousel', [\App\Http\Controllers\Admin\CarouselController::class, 'store'])->name('carousel.store');
        Route::post('/carousel/{carousel}/update', [\App\Http\Controllers\Admin\CarouselController::class, 'update'])->name('carousel.update');
    Route::patch('/carousel/{carousel}/toggle', [\App\Http\Controllers\Admin\CarouselController::class, 'toggle'])->name('carousel.toggle');
    Route::delete('/carousel/{carousel}', [\App\Http\Controllers\Admin\CarouselController::class, 'destroy'])->name('carousel.destroy');

    // --- Caja ------------------------------------------------------------------
    Route::get('/cash', [\App\Http\Controllers\Admin\CashController::class, 'index'])->name('cash.index');
    Route::post('/cash/open', [\App\Http\Controllers\Admin\CashController::class, 'openSession'])->name('cash.open');
    Route::post('/cash/withdraw', [\App\Http\Controllers\Admin\CashController::class, 'withdraw'])->name('cash.withdraw');
    Route::post('/cash/close', [\App\Http\Controllers\Admin\CashController::class, 'closeSession'])->name('cash.close');
    Route::post('/cash/validate-auth', [\App\Http\Controllers\Admin\CashController::class, 'validateAuth'])->middleware('throttle:6,1')->name('cash.validateAuth');
    Route::get('/cash/history', [\App\Http\Controllers\Admin\CashController::class, 'history'])->name('cash.history');
    Route::get('/cash/session/{session}/pdf', [\App\Http\Controllers\Admin\CashController::class, 'sessionPdf'])->name('cash.session.pdf');
    Route::get('/cash/withdrawal/{withdrawal}/pdf', [\App\Http\Controllers\Admin\CashController::class, 'withdrawalPdf'])->name('cash.withdrawal.pdf');

    // --- POS -------------------------------------------------------------------
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::post('/pos/sale', [PosController::class, 'sale'])->name('pos.sale');
    Route::get('/pos/receipt/{order}', [PosController::class, 'receiptPdf'])->name('pos.receipt');
    Route::get('/pos/receipt/{order}/pdf', [PosController::class, 'receiptPdf'])->name('pos.receipt.pdf');
    Route::get('/pos/sales-history', [PosController::class, 'salesHistory'])->name('pos.salesHistory');

    // --- Bodega ----------------------------------------------------------------
    Route::get('/bodega', [\App\Http\Controllers\Admin\BodegaController::class, 'index'])->name('bodega.index');
    Route::post('/bodega/request-access', [\App\Http\Controllers\Admin\BodegaController::class, 'requestAccess'])->name('bodega.request-access');
    Route::get('/bodega/access-requests/{accessRequest}/status', [\App\Http\Controllers\Admin\BodegaController::class, 'checkRequestStatus'])->name('bodega.access-requests.status');
    Route::post('/bodega/access-requests/{accessRequest}/cancel', [\App\Http\Controllers\Admin\BodegaController::class, 'cancelRequest'])->name('bodega.access-requests.cancel');
    Route::post('/bodega/exit-branch', [\App\Http\Controllers\Admin\BodegaController::class, 'exitBranch'])->name('bodega.exit-branch');
    Route::get('/bodega/pending-requests', [\App\Http\Controllers\Admin\BodegaController::class, 'pendingRequestsForAdmin'])->name('bodega.pending-requests');
    Route::post('/bodega/access-requests/{accessRequest}/respond', [\App\Http\Controllers\Admin\BodegaController::class, 'respondToRequest'])->name('bodega.access-requests.respond');
    Route::post('/bodega/transfer', [\App\Http\Controllers\Admin\BodegaController::class, 'transfer'])->name('bodega.transfer');
    Route::get('/bodega/transfer', fn() => Inertia::render('Admin/Bodega/Transfer'))->name('bodega.transfer');

    // --- Traslados entre sucursales -------------------------------------------
    Route::get('/branch-transfers', [\App\Http\Controllers\Admin\BranchTransferController::class, 'index'])->name('branch-transfers.index');
    Route::post('/branch-transfers/validate-auth', [\App\Http\Controllers\Admin\BranchTransferController::class, 'validateAuth'])->middleware('throttle:6,1')->name('branch-transfers.validate-auth');
    Route::get('/branch-transfers/products-by-branch', [\App\Http\Controllers\Admin\BranchTransferController::class, 'productsByBranch'])->name('branch-transfers.products-by-branch');
    Route::get('/branch-transfers/{transfer}', [\App\Http\Controllers\Admin\BranchTransferController::class, 'show'])->name('branch-transfers.show');
    Route::post('/branch-transfers', [\App\Http\Controllers\Admin\BranchTransferController::class, 'store'])->name('branch-transfers.store');
    Route::patch('/branch-transfers/{transfer}/receive', [\App\Http\Controllers\Admin\BranchTransferController::class, 'receive'])->name('branch-transfers.receive');
    Route::get('/branch-transfers/{transfer}/pdf', [\App\Http\Controllers\Admin\BranchTransferController::class, 'pdf'])->name('branch-transfers.pdf');
    Route::get('/branch-transfers/{transfer}/shortage-pdf', [\App\Http\Controllers\Admin\BranchTransferController::class, 'shortagePdf'])->name('branch-transfers.shortage-pdf');

    // --- Reportes -------------------------------------------------------------
    Route::get('/reports/sales', [\App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'salesPdf'])->name('reports.sales.pdf');
    Route::get('/reports/inventory', [\App\Http\Controllers\Admin\ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/inventory/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'inventoryPdf'])->name('reports.inventory.pdf');
    // --- Conteo Físico y Auditoría de Inventario -----------------------------
    Route::post('/reports/inventory/count/save-draft', [\App\Http\Controllers\Admin\InventoryCountController::class, 'saveDraftCount'])->name('reports.inventory.count.saveDraft');
    Route::post('/reports/inventory/count/{count}/apply', [\App\Http\Controllers\Admin\InventoryCountController::class, 'applyCount'])->middleware('throttle:6,1')->name('reports.inventory.count.apply');
    Route::get('/reports/inventory/count/{count}/discrepancies-pdf', [\App\Http\Controllers\Admin\InventoryCountController::class, 'discrepanciesPdf'])->name('reports.inventory.count.discrepanciesPdf');

    // --- Gestión de usuarios (solo superadmin) -------------------------------
    Route::get('/users/suggest-username', [\App\Http\Controllers\Admin\UserController::class, 'suggestUsername'])->name('users.suggestUsername');
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-lock', [\App\Http\Controllers\Admin\UserController::class, 'toggleLock'])->name('users.toggleLock');
    Route::put('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.resetPassword');

    // --- Sucursales (solo superadmin) -----------------------------------------
    Route::get('/branches', [\App\Http\Controllers\Admin\BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [\App\Http\Controllers\Admin\BranchController::class, 'store'])->name('branches.store');
    Route::delete('/branches/{branch}', [\App\Http\Controllers\Admin\BranchController::class, 'destroy'])->name('branches.destroy');
});

require __DIR__.'/auth.php';
