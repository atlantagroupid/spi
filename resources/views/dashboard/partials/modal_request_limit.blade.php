<div class="modal fade" id="requestLimitModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('quotas.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Ajukan Tambahan Limit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="amount" class="form-control" required placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label>Alasan</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Kenapa butuh limit tambahan?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Pengajuan</button>
                </div>
            </div>
        </form>
    </div>
</div>
