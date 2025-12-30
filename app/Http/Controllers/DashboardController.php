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

class DashboardController extends Controller
{
    public function index()
    {
        // >>> JALANKAN AUTO CUTOFF SEBELUM MEMUAT DASHBOARD <<<
        \App\Models\Visit::runAutoCutoff();

        $user = Auth::user();
        $role = $user->role;

        // --- 1. INITIALIZE VARIABLES (Nilai Default agar tidak error undefined) ---
        $achieved = 0;
        $target = 0;
        $percentage = 0;
        $todayVisits = 0;
        $visitTarget = 0;
        $visitPercentage = 0;
        $plannedVisits = [];
        $recentOrders = [];
        $incomingGoodsToday = collect();
        $outgoingGoodsToday = collect();

        $warehouseStats = [];
        $todayOrders = 0;
        $totalOrders = 0;
        $totalRevenue = 0;
        $cashReceived = 0;
        $totalReceivable = 0;
        $pendingApprovalCount = 0;
        $lowStockCount = 0;

        $chartLabels = [];
        $chartData = [];
        $topSales = [];
        $salesNames = [];
        $salesRevenue = [];
        $salesUser = $user; // Default diri sendiri
        $currentOmset = 0;
        $allSales = [];

        // Default variable
        $limitQuota = 0;
        $usedCredit = 0;
        $remaining = 0;
        $isCritical = false; // Trigger warning

        // ===================================================
        // A. LOGIKA KHUSUS SALES
        // ===================================================
        if ($role === 'sales') {
            // 1. Target Omset
            $target = $user->monthly_sales_target ?? 0;

            // Hitung Omset
            $achieved = Order::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed', 'shipped'])
                ->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))
                ->sum('total_price');

            $currentOmset = $achieved; // Mapping agar sesuai nama variabel di view
            $percentage = ($target > 0) ? ($achieved / $target) * 100 : 0;

            // 2. Target Kunjungan
            $visitTarget = $user->daily_visit_target ?? 5;

            $todayVisits = Visit::where('user_id', $user->id)
                ->whereDate('created_at', date('Y-m-d'))
                ->where('status', 'completed')
                ->count();

            $visitPercentage = ($visitTarget > 0) ? ($todayVisits / $visitTarget) * 100 : 0;

            // 3. Rencana Kunjungan
            $plannedVisits = Visit::with('customer')
                ->where('user_id', $user->id)
                ->whereDate('created_at', date('Y-m-d'))
                ->get();

            // 4. Riwayat Order
            $recentOrders = Order::with('customer')
                ->where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            // 5. Grafik Penjualan Bulanan (PRIBADI SALES)
            $salesData = Order::select(
                DB::raw('SUM(total_price) as total'),
                DB::raw('MONTH(created_at) as month')
            )
                ->where('user_id', $user->id) // <--- PENTING: Filter punya sales sendiri
                ->whereYear('created_at', date('Y'))
                ->whereIn('status', ['approved', 'completed', 'shipped'])
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            // Siapkan data untuk Chart.js
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $salesData[$i] ?? 0;
            }
        }

        // ===================================================
        // B. LOGIKA KHUSUS MANAGER / ADMIN / GUDANG
        // ===================================================
        else {
            // --- LOGIC DROPDOWN SALES (Untuk Modal Edit Target di Manager) ---
            $allSales = User::where('role', 'sales')->get();

            // Logic Dashboard Manager lihat Sales tertentu (Opsional)
            if (request('sales_id') && in_array($user->role, ['manager_bisnis', 'manager_operasional'])) {
                $salesUser = User::find(request('sales_id'));
            }

            // 1. Statistik Gudang
            $warehouseStats = [
                'total_items' => Product::sum('stock'),
                'total_asset' => Product::sum(DB::raw('price * stock')),
                'low_stock'   => Product::where('stock', '<=', 50)->count(),
            ];
            $lowStockCount = $warehouseStats['low_stock'];

            // 2. Statistik Transaksi
            $todayOrders = Order::whereDate('created_at', date('Y-m-d'))->count();
            $totalOrders = Order::count();

            // 3. Keuangan
            $totalRevenue = Order::whereIn('status', ['approved', 'shipped', 'completed'])->sum('total_price');
            $cashReceived = PaymentLog::where('status', 'approved')->sum('amount');
            // Sisa Piutang = Total Jual - Uang Masuk
            $totalReceivable = $totalRevenue - $cashReceived;

            // 4. Notifikasi Approval
            $queryApp = Approval::where('status', 'pending');

            if ($role === 'kepala_gudang') {

                $queryApp->where('model_type', 'App\Models\Product');
            } elseif ($role === 'manager_bisnis') {
                $queryApp->whereIn('model_type', [
                    'App\Models\Customer',
                    'App\Models\Order',
                    'App\Models\PaymentLog'
                ]);
            }

            $pendingApprovalCount = $queryApp->count();

            // --- KHUSUS KEPALA GUDANG ---
            if ($role === 'kepala_gudang') {
                // 1. Barang Masuk Hari Ini (Produk baru yang diapprove hari ini)
                $incomingGoodsToday = Approval::where('model_type', \App\Models\Product::class)
                    ->where('action', 'create')
                    ->where('status', 'approved')
                    ->whereDate('updated_at', today())
                    ->get();

                // 2. Barang Keluar Hari Ini (Dari order yang diproses/shipped hari ini)
                $outgoingGoodsToday = \App\Models\OrderItem::with(['product', 'order'])
                    ->whereHas('order', function ($query) {
                        $query->whereIn('status', ['shipped', 'completed'])
                            ->whereDate('updated_at', today());
                    })
                    ->get();
            }

            // 5. Grafik Penjualan
            $salesData = Order::select(
                DB::raw('SUM(total_price) as total'),
                DB::raw('MONTH(created_at) as month')
            )
                ->whereYear('created_at', date('Y'))
                ->whereIn('status', ['approved', 'shipped', 'completed'])
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            for ($i = 1; $i <= 12; $i++) {
                $chartData[] = $salesData[$i] ?? 0;
            }

            // 6. Top Sales (Leaderboard)
            if (in_array($role, ['manager_operasional', 'manager_bisnis'])) {
                $topSales = User::where('role', 'sales')
                    ->withCount(['visits' => function ($q) {
                        $q->whereMonth('created_at', date('m'));
                    }])
                    ->withSum(['orders' => function ($q) {
                        $q->whereIn('status', ['approved', 'shipped', 'completed'])
                            ->whereMonth('created_at', date('m'));
                    }], 'total_price')
                    ->orderByDesc('orders_sum_total_price')
                    ->take(5)
                    ->get();

                foreach ($topSales as $s) {
                    $salesNames[] = $s->name;
                    $salesRevenue[] = $s->orders_sum_total_price ?? 0;
                }
            }
        }

        // ===================================================
        // C. RETURN VIEW (INI YANG DIKEMBALIKAN KE 'dashboard')
        // ===================================================

        // Perbaikan: Kembalikan SEMUA role ke view 'dashboard' utama
        // agar logika di dalam view (blade) yang mengatur tampilannya.


        // ==========================================================
        // 1. DATA UNTUK GRAFIK BULANAN (Chart)
        // ==========================================================
        $chartQuery = \App\Models\Order::selectRaw('MONTH(created_at) as month, SUM(total_price) as total')
            ->whereYear('created_at', date('Y'))
            ->where('payment_status', 'paid'); // Hanya hitung yang LUNAS

        // Logika Pintar:
        // Kalau Manager -> Lihat omset TOTAL Perusahaan
        // Kalau Sales   -> Lihat omset DIA SENDIRI (Biar tau performa pribadi)
        if ($user->role == 'sales') {
            $chartQuery->where('user_id', $user->id);
        }

        $salesData = $chartQuery->groupBy('month')->pluck('total', 'month')->toArray();

        // Siapkan array data 12 bulan (Jan-Des)
        $chartArray = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartArray[] = $salesData[$i] ?? 0;
        }


        // ==========================================================
        // 2. DATA LEADERBOARD & EFEKTIVITAS (Tabel Bawah)
        // ==========================================================
        // PENTING: Ini kita buka untuk SEMUA ROLE biar Sales bisa lihat saingannya

        $topSales = \App\Models\User::where('role', 'sales') // Ambil hanya user sales
            ->withSum(['orders' => function ($q) {
                $q->where('payment_status', 'paid'); // Hitung total order lunas
            }], 'total_price')
            ->withCount('visits') // Hitung jumlah check-in kunjungan
            ->orderByDesc('orders_sum_total_price') // Urutkan dari omset tertinggi
            ->take(5) // Ambil 5 besar
            ->get();


        // Cek Role Sales/Manager Bisnis
        if (in_array($user->role, ['sales_store', 'sales_field', 'manager_bisnis'])) {

            // 1. Ambil Limit dari Database
            $limitQuota = $user->credit_limit_quota;

            // 2. Hitung yang sudah terpakai (Total Piutang Belum Lunas)
            // Ambil order yg belum lunas milik user ini
            $orders = \App\Models\Order::where('id', $user->id) // atau user_id tergantung relasi bapak
                ->whereIn('payment_type', ['top', 'kredit']) // Hanya yg makan limit
                ->where('payment_status', '!=', 'paid')      // Belum lunas
                ->where('status', '!=', 'cancelled')         // Order aktif
                ->where('status', '!=', 'rejected')
                ->get();

            foreach ($orders as $o) {
                // Rumus: Total Harga Order - (Yang sudah dibayar/dicicil)
                $paidAmount = $o->paymentLogs->where('status', 'approved')->sum('amount');
                $usedCredit += ($o->total_price - $paidAmount);
            }

            // 3. Hitung Sisa
            $remaining = $limitQuota - $usedCredit;

            // 4. Logika Warning (Jika Sisa < 20% dari Limit)
            if ($limitQuota > 0) {
                $percentageRemaining = ($remaining / $limitQuota) * 100;
                if ($percentageRemaining < 20) {
                    $isCritical = true;
                }
            } else {
                // Jika limit 0 tapi ada pemakaian, berarti minus/critical
                if ($usedCredit > 0) $isCritical = true;
            }
        }
        return view('dashboard', compact(
            'limitQuota',
            'usedCredit',
            'remaining',
            'isCritical',
            'todayVisits',
            'visitTarget',
            'visitPercentage',
            'plannedVisits',
            'currentOmset',
            'recentOrders',
            'salesUser',
            'chartLabels',
            'chartData',
            'warehouseStats',
            'pendingApprovalCount',
            'lowStockCount',
            'totalRevenue',
            'cashReceived',
            'totalReceivable',
            'topSales',
            'salesNames',
            'salesRevenue',
            'allSales',
            'incomingGoodsToday',
            'outgoingGoodsToday'
        ));
    }
}
