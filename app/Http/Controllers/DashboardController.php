<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Approval;
use App\Models\Visit;
use App\Models\PaymentLog;
use App\Models\OrderItem;

class DashboardController extends Controller
{
    public function index()
    {
        Visit::runAutoCutoff();
        $user = Auth::user();
        $role = $user->role;

        // 1. SALES (Tetap)
        if (in_array($role, ['sales_field', 'sales_store'])) {
            return $this->dashboardSales($user);
        }

        // 2. MANAGER (Tetap - View Global)
        if (in_array($role, ['manager_operasional', 'manager_bisnis'])) {
            return $this->dashboardManager($user);
        }

        // 3. GUDANG (Kepala & Admin)
        if (in_array($role, ['kepala_gudang', 'admin_gudang'])) {
            return $this->dashboardGudang($user);
        }

        // 4. FINANCE & KASIR (BARU)
        if (in_array($role, ['finance', 'kasir'])) {
            return $this->dashboardFinance($user);
        }

        // Default Fallback
        return view('dashboard.index_non_sales', compact('user'));
    }

    /**
     * -------------------------------------------------------------------------
     * LOGIC DASHBOARD KHUSUS SALES
     * -------------------------------------------------------------------------
     */
    private function dashboardSales($user)
    {
        // 1. Target & Omset (Bulanan)
        $targetOmset = $user->sales_target ?? 0; // Pastikan kolom ini ada di tabel users

        $currentOmset = Order::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed', 'shipped'])
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->sum('total_price');

        $omsetPercentage = ($targetOmset > 0) ? ($currentOmset / $targetOmset) * 100 : 0;

        // 2. Target Visit (Harian)
        $visitTarget = $user->daily_visit_target ?? 5;

        $todayVisits = Visit::where('user_id', $user->id)
            ->whereDate('created_at', date('Y-m-d'))
            ->where('status', 'completed')
            ->count();

        $visitPercentage = ($visitTarget > 0) ? ($todayVisits / $visitTarget) * 100 : 0;

        // 3. Rencana Kunjungan Hari Ini
        $plannedVisits = Visit::with('customer')
            ->where('user_id', $user->id)
            ->whereDate('created_at', date('Y-m-d'))
            ->get();

        // 4. Plafon Kredit (Sisa Limit)
        $limitQuota = $user->credit_limit_quota ?? 0;
        $usedCredit = 0;
        $remaining = 0;
        $isCritical = false;

        if ($limitQuota > 0) {
            // Hitung Order yg pakai limit (TOP/Kredit) dan belum lunas
            $unpaidOrders = Order::where('user_id', $user->id) // FIX: Pakai user_id bukan id
                ->whereIn('payment_type', ['top', 'kredit'])
                ->where('payment_status', '!=', 'paid')
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->get();

            foreach ($unpaidOrders as $o) {
                $paidAmount = $o->paymentLogs->where('status', 'approved')->sum('amount');
                $usedCredit += ($o->total_price - $paidAmount);
            }

            $remaining = $limitQuota - $usedCredit;
            // Warning jika sisa kurang dari 20%
            if (($remaining / $limitQuota) * 100 < 20) {
                $isCritical = true;
            }
        }

        // 5. Grafik Kinerja Pribadi (12 Bulan)
        $chartData = $this->getMonthlyChartData($user->id); // Panggil Helper Bawah

        // LEMPAR KE VIEW KHUSUS SALES
        return view('dashboard.index_sales', compact(
            'user',
            'targetOmset', 'currentOmset', 'omsetPercentage',
            'visitTarget', 'todayVisits', 'visitPercentage',
            'plannedVisits',
            'limitQuota', 'usedCredit', 'remaining', 'isCritical',
            'chartData'
        ));
    }

    /**
     * -------------------------------------------------------------------------
     * LOGIC DASHBOARD KHUSUS MANAGER
     * -------------------------------------------------------------------------
     */
    private function dashboardManager($user)
    {
        // 1. Statistik Global Keuangan
        $totalRevenue = Order::whereIn('status', ['approved', 'shipped', 'completed'])->sum('total_price');
        $cashReceived = PaymentLog::where('status', 'approved')->sum('amount');
        $totalReceivable = $totalRevenue - $cashReceived;

        // 2. Statistik Gudang s
        $warehouseAsset = Product::sum(DB::raw('price * stock'));
        $totalItems = Product::sum('stock');
        $lowStockCount = Product::where('stock', '<=', 50)->count();

        // Bungkus dalam array agar cocok dengan view index_manager.blade.php
        $warehouseStats = [
            'total_items' => $totalItems,
            'total_asset' => $warehouseAsset,
            'low_stock'   => $lowStockCount
        ];

        // 3. Approval Pending
        $pendingApprovalCount = Approval::where('status', 'pending')->count();

        // 4. Leaderboard Sales
        $topSales = User::whereIn('role', ['sales_field', 'sales_store'])
            ->withSum(['orders' => function ($q) {
                $q->whereIn('status', ['approved', 'shipped', 'completed'])
                  ->whereMonth('created_at', date('m'));
            }], 'total_price')
            ->orderByDesc('orders_sum_total_price')
            ->take(5)
            ->get();

        // 5. Grafik Global
        $chartData = $this->getMonthlyChartData(null);

        return view('dashboard.index_manager', compact(
            'user',
            'totalRevenue', 'cashReceived', 'totalReceivable',
            'warehouseAsset', 'lowStockCount',
            'warehouseStats',
            'pendingApprovalCount',
            'topSales',
            'chartData'
        ));
    }

    /**
     * LOGIC DASHBOARD GUDANG (UPDATED)
     * Admin Gudang tidak boleh lihat Rupiah (Nilai Aset)
     */
    private function dashboardGudang($user)
    {
        // Statistik Fisik (Aman untuk Admin)
        $totalItems = Product::sum('stock');
        $lowStockCount = Product::where('stock', '<=', 50)->count();

        // Statistik Keuangan (Hanya untuk Kepala Gudang & Manager)
        $totalAsset = 0;
        $showFinancials = false; // Default Admin gak boleh lihat

        if ($user->role === 'kepala_gudang' || $user->role === 'manager_operasional') {
            $totalAsset = Product::sum(DB::raw('price * stock'));
            $showFinancials = true;
        }

        // Barang Masuk/Keluar
        $incomingGoods = Approval::where('model_type', Product::class)
            ->where('status', 'approved')
            ->whereDate('updated_at', today())
            ->count();

        $outgoingGoods = Order::where('status', 'shipped')
            ->whereDate('updated_at', today())
            ->count();

        $pendingApproval = Approval::where('model_type', Product::class)
            ->where('status', 'pending')
            ->count();

        return view('dashboard.index_gudang', compact(
            'user',
            'totalItems', 'lowStockCount',
            'totalAsset', 'showFinancials', // Kirim flag ini ke view
            'incomingGoods', 'outgoingGoods', 'pendingApproval'
        ));
    }

    /**
     * LOGIC DASHBOARD FINANCE (BARU)
     */
    private function dashboardFinance($user)
    {
        // 1. Uang Masuk Hari Ini (Cash flow harian penting buat Finance)
        $cashToday = PaymentLog::where('status', 'approved')
            ->whereDate('payment_date', today())
            ->sum('amount');

        // 2. Total Piutang (Receivables)
        $allOrders = Order::whereIn('status', ['approved', 'shipped', 'completed'])->sum('total_price');
        $allPaid = PaymentLog::where('status', 'approved')->sum('amount');
        $totalReceivable = $allOrders - $allPaid;

        // 3. Menunggu Konfirmasi Pembayaran
        $pendingPayments = PaymentLog::where('status', 'pending')->count();

        // 4. Pengajuan TOP/Limit Baru (Jika Finance ikut approval)
        $pendingLimit = \App\Models\QuotaRequest::where('status', 'pending')->count();

        // 5. 5 Transaksi Terakhir (Semua Sales)
        $recentTransactions = PaymentLog::with(['order.customer', 'user']) // User disini adalah yang input bayar (Sales)
            ->where('status', 'approved')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index_finance', compact(
            'user',
            'cashToday', 'totalReceivable',
            'pendingPayments', 'pendingLimit',
            'recentTransactions'
        ));
    }

    /**
     * -------------------------------------------------------------------------
     * HELPER: GENERATE CHART DATA
     * -------------------------------------------------------------------------
     */
    private function getMonthlyChartData($userId = null)
    {
        $query = Order::select(
            DB::raw('SUM(total_price) as total'),
            DB::raw('MONTH(created_at) as month')
        )
        ->whereYear('created_at', date('Y'))
        ->whereIn('status', ['approved', 'shipped', 'completed'])
        ->groupBy('month');

        // Jika ada User ID, filter punya dia saja (untuk Sales Dashboard)
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $results = $query->pluck('total', 'month')->toArray();

        // Format array [100, 200, 0, ...] untuk 12 bulan
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[] = $results[$i] ?? 0;
        }

        return $data;
    }
}
