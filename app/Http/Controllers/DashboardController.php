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

        // FIX: Tambahkan fresh() agar data user (terutama credit_limit) selalu ambil terbaru dari DB
        // Bukan dari cache session yang mungkin belum update setelah approval.
        $user = Auth::user()->fresh();

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
        if (in_array($role, ['kepala_gudang', 'admin_gudang', 'purchase'])) {
            return $this->dashboardGudang($user);
        }

        // 4. FINANCE & KASIR (BARU)
        if (in_array($role, ['finance', 'kasir'])) {
            return $this->dashboardFinance($user);
        }

        // Default Fallback
        abort(403, 'Role Anda tidak memiliki dashboard yang terdaftar.');
    }

    /**
     * -------------------------------------------------------------------------
     * LOGIC DASHBOARD KHUSUS SALES (FIELD vs STORE)
     * -------------------------------------------------------------------------
     */
    private function dashboardSales($user)
    {
        // CEK TIPE SALES
        $isSalesStore = ($user->role === 'sales_store');

        // 1. Target & Omset (Bulanan) - BERLAKU UNTUK KEDUANYA
        $targetOmset = $user->sales_target ?? 0;

        $currentOmset = Order::where('user_id', $user->id) // Filter Punya Sendiri
            ->whereIn('status', ['approved', 'completed', 'shipped'])
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->sum('total_price');

        $omsetPercentage = ($targetOmset > 0) ? ($currentOmset / $targetOmset) * 100 : 0;

        // 2. Target Visit & Rencana (HANYA UNTUK SALES FIELD)
        $visitTarget = 0;
        $todayVisits = 0;
        $visitPercentage = 0;
        $plannedVisits = collect([]); // Collection kosong default

        if (!$isSalesStore) {
            // --- LOGIKA SALES FIELD (Lapangan) ---
            $visitTarget = $user->daily_visit_target ?? 5;

            // OPTIMIZE: Single query untuk stats dan planned visits
            $visitStats = Visit::selectRaw('
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_visits
                ')
                ->where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->first();

            $todayVisits = $visitStats->completed_visits ?? 0;
            $visitPercentage = ($visitTarget > 0) ? ($todayVisits / $visitTarget) * 100 : 0;

            // Rencana Kunjungan Hari Ini (dengan eager loading)
            $plannedVisits = Visit::with('customer')
                ->where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->get();
        } else {
            // --- LOGIKA SALES STORE (Toko) ---
            // OPTIMIZE: Single query
            $todayVisits = Visit::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            $visitPercentage = 0;
        }

        // 3. Plafon Kredit (Sisa Limit) - BERLAKU KEDUANYA
        // Karena $user sudah di-fresh() di index(), nilai ini PASTI akurat
        $limitQuota = $user->credit_limit_quota ?? 0;
        $usedCredit = 0;
        $remaining = 0;
        $isCritical = false;

        if ($limitQuota > 0) {
            // OPTIMIZE: Eager load paymentLogs untuk menghindari N+1 query
            $unpaidOrders = Order::with(['paymentLogs' => function ($query) {
                    $query->where('status', 'approved');
                }])
                ->where('user_id', $user->id)
                ->whereIn('payment_type', ['top', 'kredit'])
                ->where('payment_status', '!=', 'paid')
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->get();

            foreach ($unpaidOrders as $o) {
                $paidAmount = $o->paymentLogs->sum('amount');
                $usedCredit += ($o->total_price - $paidAmount);
            }

            $remaining = $limitQuota - $usedCredit;

            // Logika Alert: Jika sisa limit < 20%
            if (($remaining / $limitQuota) * 100 < 20) {
                $isCritical = true;
            }
        }

        // 4. Grafik Kinerja Pribadi (12 Bulan) - STRICT FILTER USER_ID
        $chartData = $this->getMonthlyChartData($user->id);

        return view('dashboard.index_sales', compact(
            'user', 'isSalesStore', // Kirim flag tipe sales ke view
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
        $pendingApprovalCount = Approval::where('status', 'pending')->count() + \App\Models\TopSubmission::where('status', 'pending')->count();

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
            'totalRevenue',
            'cashReceived',
            'totalReceivable',
            'warehouseAsset',
            'lowStockCount',
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
            'totalItems',
            'lowStockCount',
            'totalAsset',
            'showFinancials', // Kirim flag ini ke view
            'incomingGoods',
            'outgoingGoods',
            'pendingApproval'
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
            'cashToday',
            'totalReceivable',
            'pendingPayments',
            'pendingLimit',
            'recentTransactions'
        ));
    }

    /**
     * -------------------------------------------------------------------------
     * HELPER: GENERATE CHART DATA (OPTIMIZED)
     * -------------------------------------------------------------------------
     * Ini versi TERBAIK (Single Query).
     */
    private function getMonthlyChartData($userId = null)
    {
        // 1 Query untuk ambil semua data dalam setahun
        $query = Order::select(
            DB::raw('SUM(total_price) as total'),
            DB::raw('MONTH(created_at) as month')
        )
            ->whereYear('created_at', date('Y'))
            ->whereIn('status', ['approved', 'shipped', 'completed'])
            ->groupBy('month');

        // Filter User ID (Penting agar Sales tidak lihat omset global)
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $results = $query->pluck('total', 'month')->toArray();

        // Mapping data agar bulan yang kosong tetap bernilai 0
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[] = (int) ($results[$i] ?? 0); // Pastikan jadi integer
        }

        return $data;
    }
}
