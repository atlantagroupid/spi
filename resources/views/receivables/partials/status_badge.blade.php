@if ($log->status == 'approved')
    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Sah</span>
@elseif($log->status == 'rejected')
    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Ditolak</span>
@elseif($log->status == 'pending')
    @if (in_array(Auth::user()->role, ['manager_bisnis', 'manager_operasional']))
        <div class="btn-group shadow-sm">
            <form action="{{ route('payments.approve', $log->id) }}" method="POST" onsubmit="return confirm('Validasi pembayaran ini?');">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-success px-3" title="Terima"><i class="bi bi-check-lg"></i></button>
            </form>
            <form action="{{ route('payments.reject', $log->id) }}" method="POST" onsubmit="return confirm('Tolak pembayaran ini?');">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-danger px-3" title="Tolak"><i class="bi bi-x-lg"></i></button>
            </form>
        </div>
    @else
        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
    @endif
@endif
