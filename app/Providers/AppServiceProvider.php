<?php

namespace App\Providers;

use App\Http\Controllers\TopSubmissionController;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; // <--- 1. Tambahkan baris ini
use Illuminate\Support\Facades\View; // <--- Tambahkan ini
use Illuminate\Support\Facades\Auth; // <--- Tambahkan ini
use App\Models\Approval; // Jika Anda punya model Approval terpusat
use App\Models\TopSubmission;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('app.env') === 'local') {
        URL::forceScheme('https');
    }
    }

    public function boot()
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view) {
            // 1. Inisialisasi Variabel (Default 0)
            $notifTotal = 0;
            $notifOrders = 0;
            $notifPayments = 0;
            $notifCustomers = 0;
            $notifProducts = 0;
            $notifPendingTOP = 0;

            if (Auth::check()) {
                $user = Auth::user();

                // --- SKENARIO 1: KEPALA GUDANG (Hanya Produk) ---
                if ($user->role === 'kepala_gudang') {
                    $notifProducts = Approval::where('status', 'pending')
                        ->where('model_type', 'LIKE', '%Product%')
                        ->count();

                    // Total Gudang cuma Product aja
                    $notifTotal = $notifProducts;
                }

                // --- SKENARIO 2: MANAGER BISNIS (Customer, Order, Payment) ---
                elseif ($user->role === 'manager_bisnis') {
                    $notifCustomers = Approval::where('status', 'pending')
                        ->where('model_type', 'LIKE', '%Customer%')->count();

                    $notifOrders = Approval::where('status', 'pending')
                        ->where('model_type', 'LIKE', '%Order%')->count();

                    $notifPayments = Approval::where('status', 'pending')
                        ->where('model_type', 'LIKE', '%PaymentLog%')->count();

                        $notifPendingTOP = TopSubmission::where('status', 'pending')
                        ->count();

                    // Produk HARUS 0 buat Manager Bisnis
                    $notifProducts = 0;

                    // Total Manager Bisnis
                    $notifTotal = $notifCustomers + $notifOrders + $notifPayments + $notifProducts + $notifPendingTOP;
                }

                // --- SKENARIO 3: MANAGER OPERASIONAL (Melihat Semuanya) ---
                elseif ($user->role === 'manager_operasional') {
                    $notifCustomers = Approval::where('status', 'pending')->where('model_type', 'LIKE', '%Customer%')->count();
                    $notifOrders    = Approval::where('status', 'pending')->where('model_type', 'LIKE', '%Order%')->count();
                    $notifPayments  = Approval::where('status', 'pending')->where('model_type', 'LIKE', '%PaymentLog%')->count();
                    $notifProducts  = Approval::where('status', 'pending')->where('model_type', 'LIKE', '%Product%')->count();
                    $notifPendingTOP = TopSubmission::where('status', 'pending')->count();
                    // Total Manager Ops = Jumlah Semua Pending di Database
                    $notifTotal = Approval::where('status', 'pending')->count() + $notifPendingTOP;
                }
            }

            // Kirim ke View
            $view->with('notifTotal', $notifTotal);
            $view->with('notifPendingOrders', $notifOrders);
            $view->with('notifPendingPayments', $notifPayments);
            $view->with('notifPendingCustomers', $notifCustomers);
            $view->with('notifPendingProducts', $notifProducts);
            $view->with('notifPendingTOP', $notifPendingTOP);
        });
    }
}
