<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Barryvdh\DomPDF\Facade\Pdf;

class StockMovementController extends Controller
{
    // Method untuk mengambil data (digunakan oleh index dan pdf)
    private function getMovements(Request $request)
    {
        $startDate = $request->input('start_date', today()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $type = $request->input('type', 'all');

        $incoming = collect();
        $outgoing = collect();

        // 1. Ambil Barang Masuk (jika diminta)
        if ($type === 'all' || $type === 'in') {
            $incoming = Approval::with('approver')
                ->where('model_type', Product::class)
                ->where('action', 'create')
                ->where('status', 'approved')
                ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->updated_at,
                        'type' => 'in',
                        'product_name' => $item->new_data['name'] ?? 'N/A',
                        'quantity' => $item->new_data['stock'] ?? 0,
                        'reference' => 'Produk Baru',
                        'notes' => 'Disetujui oleh ' . ($item->approver->name ?? 'Sistem'),
                    ];
                });
        }

        // 2. Ambil Barang Keluar (jika diminta)
        if ($type === 'all' || $type === 'out') {
            $outgoing = OrderItem::with(['product', 'order.customer'])
                ->whereHas('order', function ($query) use ($startDate, $endDate) {
                    $query->whereIn('status', ['shipped', 'completed'])
                        ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                })
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->order->updated_at,
                        'type' => 'out',
                        'product_name' => $item->product->name ?? 'Produk Dihapus',
                        'quantity' => $item->quantity,
                        'reference' => $item->order->invoice_number ?? 'N/A',
                        'notes' => 'Customer: ' . ($item->order->customer->name ?? 'N/A'),
                    ];
                });
        }

        // 3. Gabungkan dan urutkan
        return $incoming->concat($outgoing)->sortByDesc('date');
    }

    // Tampilkan halaman laporan dengan paginasi
    public function index(Request $request)
    {
        $allMovements = $this->getMovements($request);

        // Paginasi Manual
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentItems = $allMovements->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $movements = new LengthAwarePaginator($currentItems, $allMovements->count(), $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => $request->query()
        ]);

        return view('reports.stock.index', compact('movements'));
    }

    // Export laporan ke PDF
    public function exportPdf(Request $request)
    {
        $movements = $this->getMovements($request);
        $startDate = $request->input('start_date', today()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));

        $pdf = Pdf::loadView('reports.stock.pdf', compact('movements', 'startDate', 'endDate'));
        return $pdf->download('laporan-pergerakan-stok-' . $startDate . '-sd-' . $endDate . '.pdf');
    }
}
