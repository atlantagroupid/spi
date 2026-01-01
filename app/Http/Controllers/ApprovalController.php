<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\TopSubmission;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ApprovalController extends Controller
{
    // =========================================================================
    // 1. DASHBOARD & MENU UTAMA
    // =========================================================================

    public function index()
    {
        $user = Auth::user();
        $query = Approval::with(['requester', 'approveable'])->where('status', 'pending');

        // Filter Role
        if ($user->role === 'manager_bisnis') {
            $query->where(function ($q) {
                $q->where('model_type', 'like', '%Order%')
                  ->orWhere('model_type', 'like', '%PaymentLog%')
                  ->orWhere('model_type', 'like', '%Customer%');
            });
        } elseif ($user->role === 'kepala_gudang') {
            $query->where('model_type', 'like', '%Product%');
        } elseif ($user->role !== 'manager_operasional') {
            abort(403, 'Akses Ditolak');
        }

        $approvals = $query->latest()->paginate(10);
        return view('approvals.index', compact('approvals'));
    }

    // --- Menggunakan Helper Private untuk mengurangi duplikasi code ---
    public function transaksi() {
        $this->authorizeRole(['manager_bisnis', 'manager_operasional']);
        $approvals = $this->getPendingApprovals('Order');
        return view('approvals.transaksi', compact('approvals'));
    }

    public function piutang() {
        $this->authorizeRole(['manager_bisnis', 'manager_operasional']);
        $approvals = $this->getPendingApprovals('PaymentLog');
        return view('approvals.piutang', compact('approvals'));
    }

    public function customer() {
        $this->authorizeRole(['manager_bisnis', 'manager_operasional']);
        $approvals = $this->getPendingApprovals('Customer');
        return view('approvals.customers', compact('approvals'));
    }

    public function produk() {
        $this->authorizeRole(['kepala_gudang', 'manager_operasional']);
        $approvals = $this->getPendingApprovals('Product');
        return view('approvals.products', compact('approvals'));
    }

    // =========================================================================
    // 2. LOGIKA UTAMA (APPROVE & REJECT)
    // =========================================================================

    public function approve(Request $request, Approval $approval)
    {
        if ($approval->status != 'pending') return back()->with('error', 'Data sudah diproses.');

        DB::beginTransaction();
        try {
            $realData = $approval->approveable; // Menggunakan relasi morphTo
            $action = $approval->action;

            // ======================================================
            // 1. UPDATE UMUM (Customer, Product, Limit)
            // ======================================================
            if (in_array($action, ['update', 'update_customer', 'update_product', 'credit_limit_update'])) {
                if ($realData) $realData->update($approval->new_data);
            }

            // ======================================================
            // 2. CREATE PRODUK BARU
            // ======================================================
            elseif ($action == 'create' && $approval->model_type == \App\Models\Product::class) {
                \App\Models\Product::create($approval->new_data);
            }
            // [TAMBAHAN BARU] 2.1. APPROVE CUSTOMER BARU
            // Karena data customer sudah ada (status: pending), kita tinggal ubah jadi active
            elseif ($action == 'create' && $approval->model_type == \App\Models\Customer::class) {
                if ($realData) {
                    $realData->update(['status' => 'active']);
                }
            }
            // ======================================================
            // 3. DELETE DATA
            // ======================================================
            elseif ($action == 'delete' && $realData) {
                $realData->delete();
            }

            // ======================================================
            // 4. APPROVE ORDER
            // ======================================================
            elseif ($action === 'approve_order') {
                $order = \App\Models\Order::find($approval->model_id);
                if ($order) {
                    $order->update(['status' => 'approved']);

                    // Potong Limit Customer jika TOP/Kredit
                    if (in_array($order->payment_type, ['top', 'kredit']) && $order->customer) {
                        // Pastikan kolom di DB customer bernama 'credit_limit' atau 'credit_limit_quota'
                        // Sesuaikan dengan nama kolom Anda (misal: limit_quota)
                        $order->customer->decrement('credit_limit_quota', $order->total_price);
                    }

                    // [PENTING] Catat History Order
                    $this->recordOrderHistory($order, 'Disetujui', 'Order disetujui via Menu Approval.');
                }
            }

            // ======================================================
            // 5. APPROVE PEMBAYARAN (PAYMENT LOG)
            // ======================================================
            elseif ($action === 'approve_payment') {
                $log = \App\Models\PaymentLog::find($approval->model_id);
                if ($log) {
                    $log->update(['status' => 'approved']);

                    $order = $log->order;
                    if ($order) {
                        // Hitung total bayar yg sudah diapprove
                        $totalPaid = $order->paymentLogs()->where('status', 'approved')->sum('amount');

                        // Update status order jika lunas
                        if ($totalPaid >= $order->total_price) {
                            $order->update(['payment_status' => 'paid', 'status' => 'completed']);
                            $this->recordOrderHistory($order, 'Lunas', 'Pembayaran lunas. Order selesai.');
                        } else {
                            $order->update(['payment_status' => 'partial']);
                            $this->recordOrderHistory($order, 'Bayar Partial', 'Pembayaran sebagian diterima.');
                        }

                        // Kembalikan Limit Customer (Top Up Limit)
                        if (in_array($order->payment_type, ['top', 'kredit']) && $order->customer) {
                            $order->customer->increment('credit_limit_quota', $log->amount);
                        }
                    }
                }
            }

            // ======================================================
            // 6. REVISI SURAT JALAN (KASIR)
            // ======================================================
            elseif ($action === 'update_delivery_note' && $approval->model_type == \App\Models\Order::class) {
                $order = \App\Models\Order::find($approval->model_id);
                if ($order) {
                    $order->update([
                        'delivery_proof' => $approval->new_data['delivery_proof'], // Pastikan key array sama
                        'driver_name'    => $approval->new_data['driver_name'] ?? $order->driver_name,
                        'status'         => 'shipped'
                    ]);

                    $this->recordOrderHistory($order, 'Revisi SJ Disetujui', 'Revisi surat jalan disetujui Manager.');
                }
            }

            // ======================================================
            // FINALISASI
            // ======================================================
            $approval->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', 'Permintaan telah DISETUJUI.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error System: ' . $e->getMessage());
        }
    }

    // HELPER: CATAT HISTORY ORDER (Agar timeline di detail order tetap jalan)
    private function recordOrderHistory($order, $action, $description)
    {
        if ($order) {
            \App\Models\OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(), // ID Manager yang approve
                'action'      => $action,
                'description' => $description
            ]);
        }
    }

    public function reject(Request $request, Approval $approval)
    {
        if ($approval->status != 'pending') return back()->with('error', 'Data sudah diproses.');
        $request->validate(['reason' => 'required|string|max:255']);

        DB::transaction(function () use ($approval, $request) {
            $approval->update([
                'status' => 'rejected',
                'approver_id' => Auth::id(),
                'reason' => $request->reason,
            ]);

            // Rollback Khusus
            if ($approval->action === 'approve_order') {
                $order = Order::find($approval->model_id);
                if ($order) {
                    $order->update(['status' => 'rejected']);
                    foreach ($order->items as $item) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            } elseif ($approval->action === 'approve_payment') {
                PaymentLog::where('id', $approval->model_id)->update(['status' => 'rejected']);
            // [TAMBAHAN BARU] Reject Customer Baru
            } elseif ($approval->model_type == \App\Models\Customer::class && $approval->action == 'create') {
                if ($approval->approveable) {
                    $approval->approveable->update(['status' => 'rejected']);
                }
            }
        });

        return back()->with('success', 'Permintaan telah DITOLAK.');
    }

    public function show_detail($id)
    {
        // 1. Ambil data approval beserta relasinya
        // 'approveable' adalah fitur Laravel MorphTo yang otomatis mengambil model aslinya (Customer/Order/dll)
        $approval = \App\Models\Approval::with(['requester', 'approveable'])->findOrFail($id);

        // 2. LOGIKA PERBAIKAN (FALLBACK DATA)
        // Ambil dari kolom JSON 'data' di tabel approvals
        $dataContent = $approval->data;

        // JIKA kolom JSON kosong (kasus "null" kemarin), ambil dari data asli (tabel customers)
        if (empty($dataContent) && $approval->approveable) {
            $dataContent = $approval->approveable->toArray();
        }

        // 3. Kembalikan ke View Partial
        return view('approvals.partials._detail_content', [
            'approval' => $approval,
            // Kita kirim variable $dataContent yang sudah pasti terisi
            'data'     => $dataContent
        ]);
    }

    // =========================================================================
    // 3. HISTORY & DETAIL & EXPORT
    // =========================================================================

    public function history(Request $request)
    {
        $user = Auth::user();
        $search = $request->search;
        $date = $request->date; // <--- AMBIL TANGGAL DARI INPUT

        // 1. Query Approval Lama
        $appQuery = Approval::with(['requester', 'approver', 'approveable'])
            ->whereIn('status', ['approved', 'rejected']);

        $this->applyHistoryFilter($appQuery, $user, $search);

        // FILTER TANGGAL (APPROVAL)
        if ($date) {
            $appQuery->whereDate('updated_at', $date);
        }

        $approvals = $appQuery->latest()->get()->map(function ($item) {
            $item->history_type = class_basename($item->model_type);
            return $item;
        });

        // 2. Query TOP Submission (Hanya Bisnis/Ops)
        $topSubmissions = collect();
        if (in_array($user->role, ['manager_bisnis', 'manager_operasional'])) {
            $topQuery = TopSubmission::with(['sales', 'customer', 'approver'])
                ->whereIn('status', ['approved', 'rejected']);

            if ($search) $topQuery->whereHas('sales', fn($q) => $q->where('name', 'like', "%$search%"));

            // FILTER TANGGAL (TOP)
            if ($date) {
                $topQuery->whereDate('updated_at', $date);
            }

            $topSubmissions = $topQuery->latest()->get()->map(function ($item) {
                $item->history_type = 'TOP';
                return $item;
            });
        }

        // 3. Merge & Paginate
        $merged = $approvals->merge($topSubmissions)->sortByDesc('updated_at')->values();

        // Pagination Logic
        $page = Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $histories = new LengthAwarePaginator(
            $merged->slice(($page - 1) * $perPage, $perPage)->all(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('approvals.history', compact('histories'));
    }

    public function exportHistoryPdf(Request $request)
    {
        $user = Auth::user();
        $search = $request->search;
        $date = $request->date; // <--- AMBIL TANGGAL

        // 1. AMBIL APPROVAL BIASA
        $appQuery = Approval::with(['requester', 'approver', 'approveable'])
            ->whereIn('status', ['approved', 'rejected']);

        $this->applyHistoryFilter($appQuery, $user, $search);

        // FILTER TANGGAL PDF
        if ($date) {
            $appQuery->whereDate('updated_at', $date);
        }

        $approvals = $appQuery->latest()->get()->map(function ($item) {
            $item->history_type = class_basename($item->model_type);
            return $item;
        });

        // 2. AMBIL TOP SUBMISSION
        $topSubmissions = collect();
        if (in_array($user->role, ['manager_bisnis', 'manager_operasional'])) {
            $topQuery = TopSubmission::with(['sales', 'customer', 'approver'])
                ->whereIn('status', ['approved', 'rejected']);

            if ($search) $topQuery->whereHas('sales', fn($q) => $q->where('name', 'like', "%$search%"));

            // FILTER TANGGAL PDF
            if ($date) {
                $topQuery->whereDate('updated_at', $date);
            }

            $topSubmissions = $topQuery->latest()->get()->map(function ($item) {
                $item->history_type = 'TOP';
                return $item;
            });
        }

        // 3. GABUNGKAN
        $histories = $approvals->merge($topSubmissions)->sortByDesc('updated_at')->values();

        $pdf = Pdf::loadView('approvals.pdf_history', compact('histories', 'user'))
            ->setPaper('a4', 'landscape');

        // Nama file ada tanggalnya jika difilter
        $filename = 'Laporan-Riwayat-' . ($date ?? 'Semua') . '.pdf';
        return $pdf->download($filename);
    }
    // =========================================================================
    // 4. PRIVATE HELPERS (Untuk mengurangi duplikasi kode)
    // =========================================================================

    private function authorizeRole($roles) {
        if (!in_array(Auth::user()->role, $roles)) abort(403, 'Akses Ditolak');
    }

    private function getPendingApprovals($modelName) {
        return Approval::where('model_type', 'like', "%$modelName%")
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function applyHistoryFilter($query, $user, $search) {
        // Filter Role
        if ($user->role === 'kepala_gudang') {
            $query->where('model_type', 'like', '%Product%');
        } elseif ($user->role === 'manager_bisnis') {
            $query->where(function ($q) {
                $q->where('model_type', 'like', '%Customer%')
                  ->orWhere('model_type', 'like', '%Order%')
                  ->orWhere('model_type', 'like', '%PaymentLog%');
            });
        }
        // Filter Search
        if ($search) {
            $query->whereHas('requester', fn($q) => $q->where('name', 'like', "%$search%"));
        }
    }
}
