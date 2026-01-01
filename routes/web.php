<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TopSubmissionController;
use App\Http\Controllers\UserQuotaController;

/*
|--------------------------------------------------------------------------
| Web Routes (Refactored & Grouped)
|--------------------------------------------------------------------------
*/

// --- SCHEDULER ---
Schedule::command('invoice:remind')->dailyAt('08:00');

// --- PUBLIC & GUEST ---
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// --- AUTHENTICATED USER (Harus Login) ---
Route::middleware('auth')->group(function () {

    // --- A. DASHBOARD & AUTH ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // --- B. PROFILE & NOTIFIKASI ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifications/read', function () {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.markRead');

    // --- AJAX routes for locations ---
    Route::get('/ajax/gates/{gudangId}', [\App\Http\Controllers\LocationController::class, 'getGatesByGudang'])->name('ajax.gates');
    Route::get('/ajax/blocks/{gateId}', [\App\Http\Controllers\LocationController::class, 'getBlocksByGate'])->name('ajax.blocks');

    // --- C. MANAJEMEN USER & SETTINGS (Manager Operasional) ---
    Route::middleware(['role:manager_operasional'])->group(function () {
        // User Management
        Route::resource('users', UserController::class);
        Route::post('/users/update-target', [UserController::class, 'updateSalesTarget'])->name('users.updateTarget');

        // Settings
        Route::controller(SettingController::class)->prefix('settings')->name('settings.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/general', 'updateGeneral')->name('updateGeneral');
            Route::post('/category', 'storeCategory')->name('storeCategory');
            Route::delete('/category/{id}', 'destroyCategory')->name('destroyCategory');
            Route::post('/customer-category', 'storeCustomerCategory')->name('storeCustomerCategory');
            Route::delete('/customer-category/{id}', 'destroyCustomerCategory')->name('destroyCustomerCategory');
        });

        // Location Management
        Route::controller(\App\Http\Controllers\LocationController::class)->prefix('settings/locations')->name('settings.locations.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/gudang', 'storeGudang')->name('gudang.store');
            Route::put('/gudang/{id}', 'updateGudang')->name('gudang.update');
            Route::delete('/gudang/{id}', 'destroyGudang')->name('gudang.destroy');
            Route::post('/gate', 'storeGate')->name('gate.store');
            Route::put('/gate/{id}', 'updateGate')->name('gate.update');
            Route::delete('/gate/{id}', 'destroyGate')->name('gate.destroy');
            Route::post('/block', 'storeBlock')->name('block.store');
            Route::put('/block/{id}', 'updateBlock')->name('block.update');
            Route::delete('/block/{id}', 'destroyBlock')->name('block.destroy');
        });
    });

    // --- D. MASTER DATA ---
    // Products
    Route::middleware(['role:manager_operasional,kepala_gudang,admin_gudang,purchase'])->group(function () {
        Route::resource('products', ProductController::class);
        Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
            Route::patch('/{id}/update-restock', 'updateRestock')->name('updateRestock');
            Route::post('/{id}/update-discount', 'updateDiscount')->name('updateDiscount');
        });
    });

    // Customers
    Route::middleware(['role:manager_operasional,manager_bisnis'])->group(function () {
        Route::patch('/customers/{id}/approve', [CustomerController::class, 'approve'])->name('customers.approve');
        Route::patch('/customers/{id}/reject', [CustomerController::class, 'reject'])->name('customers.reject');
    });
    Route::controller(CustomerController::class)->group(function () {
        Route::get('/customers/top-list', 'listTop')->name('customers.top_list');
        Route::resource('customers', CustomerController::class);
    });


    // TOP Submissions
    Route::resource('top-submissions', TopSubmissionController::class)->only(['index', 'create', 'store']);
    Route::controller(TopSubmissionController::class)->prefix('top-submissions')->name('top-submissions.')->group(function () {
        Route::put('/{id}/approve', 'approve')->name('approve');
        Route::put('/{id}/reject', 'reject')->name('reject');
    });

    // --- E. TRANSAKSI (ORDERS) ---
    // PENTING: Custom route ditaruh SEBELUM Resource
    Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/print-pdf', 'printPdf')->name('printPdf');
        Route::get('/export', 'export')->name('export');
        Route::get('/export-list-pdf', 'exportListPdf')->name('exportListPdf');
        Route::put('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');
        Route::get('/{id}/export-pdf', 'exportPdf')->name('exportPdf');
        Route::post('/{order}/process', 'processOrder')->name('process');
        Route::post('/{id}/confirm-arrival', 'confirmArrival')->name('confirmArrival');
    });
    // Resource ditaruh di bawah
    Route::resource('orders', OrderController::class);

    // --- F. KUNJUNGAN (VISIT) ---
    // Tambahkan 'show' ke dalam daftar pengecualian (except)
    Route::resource('visits', VisitController::class)->except(['update', 'destroy', 'show']);
    Route::controller(VisitController::class)->prefix('visits')->name('visits.')->group(function () {

        // === TAMBAHAN BARU ===
        // Route untuk halaman "Rencana Visit" (Mengarah ke form createPlan)
        Route::get('/plan', 'createPlan')->name('plan');
        // =====================

        Route::get('/create-plan', 'createPlan')->name('createPlan'); // (Opsional: biarkan saja buat backup)
        Route::post('/store-plan', 'storePlan')->name('storePlan');
        Route::post('/update-target', 'updateTarget')->name('updateTarget');
        Route::post('/{visit}/check-in', 'checkIn')->name('checkIn');
        Route::get('/{visit}/perform', 'perform')->name('perform');
        Route::put('/{visit}/update', 'update')->name('update');
    });

    // --- G. KEUANGAN & PIUTANG ---
    Route::controller(ReceivableController::class)->group(function () {
        // Receivable Pages & Actions
        Route::prefix('receivables')->name('receivables.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/completed', 'completed')->name('completed');
            Route::get('/export', 'export')->name('export');
            Route::get('/print-pdf', 'printPdf')->name('printPdf');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/remind', 'sendReminders')->name('remind');
            Route::put('/{id}/pay', 'store')->name('pay'); // Payment submission
        });

        // Payment Log Approval (Legacy/Backup)
        Route::prefix('payment-logs')->name('payments.')->group(function () {
            Route::put('/{id}/approve', 'approve')->name('approve');
            Route::put('/{id}/reject', 'reject')->name('reject');
        });
    });

    // --- H. SYSTEM APPROVAL (PUSAT APPROVAL) ---
    Route::controller(ApprovalController::class)->prefix('approvals')->name('approvals.')->group(function () {
        // Index dengan middleware spesifik
        Route::get('/', 'index')->name('index')->middleware('role:manager_operasional');

        Route::get('/history', 'history')->name('history');

        // Route PDF History
        Route::get('/history/export-pdf', 'exportHistoryPdf')->name('history.pdf');

        // Kategori
        Route::get('/transaksi', 'transaksi')->name('transaksi');
        Route::get('/bayar-piutang', 'piutang')->name('piutang');
        Route::get('/customers', 'customer')->name('customers');
        Route::get('/products', 'produk')->name('products');

        // Actions & Details
        Route::get('/{id}/detail', 'show_detail')->name('detail');

        Route::put('/{approval}/approve', 'approve')->name('approve');
        Route::put('/{approval}/reject', 'reject')->name('reject');
    });

    // --- I. REPORTING ---
    Route::middleware(['role:manager_operasional,kepala_gudang'])->group(function () {
        Route::controller(\App\Http\Controllers\StockMovementController::class)->prefix('reports/stock-movements')->name('stock_movements.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/pdf', 'exportPdf')->name('pdf');
        });
    });
    // --- J. MANAJEMEN PLAFON KREDIT (Manager Ops, Bisnis & Sales) ---
    // KITA PINDAHKAN KESINI AGAR BISA DIAKSES BANYAK ROLE
    Route::middleware(['role:manager_operasional,manager_bisnis,sales,sales_store,sales_field'])->group(function () {
        Route::controller(UserQuotaController::class)->prefix('team-quotas')->name('quotas.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');             // Sales Request Limit
            Route::put('/{id}/approve', 'approve')->name('approve'); // Manager Approve
            Route::put('/{id}', 'updateManual')->name('update');     // Manual Update
        });
    });
}); // End Middleware Auth
