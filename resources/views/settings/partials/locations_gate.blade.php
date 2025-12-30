{{-- resources/views/settings/partials/locations_gate.blade.php --}}
<h5>Data Gate</h5>
<hr>
<div class="row">
    <div class="col-md-4">
        <h6>Tambah Gate Baru</h6>
        <form action="{{ route('settings.locations.gate.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="gate_gudang_id" class="form-label">Pilih Gudang</label>
                <select name="gudang_id" id="gate_gudang_id" class="form-select" required>
                    <option value="">-- Pilih Gudang --</option>
                    @foreach ($gudangs as $gudang)
                        <option value="{{ $gudang->id }}">{{ $gudang->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="gate_name" class="form-label">Nama Gate</label>
                <input type="text" name="name" class="form-control" id="gate_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
    <div class="col-md-8">
        <h6>Daftar Gate</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Nama Gate</th>
                        <th>Gudang</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gates as $gate)
                        <tr>
                            <td>{{ $gate->name }}</td>
                            <td>{{ $gate->gudang->name }}</td>
                            <td>
                                <form action="{{ route('settings.locations.gate.destroy', $gate->id) }}" method="POST" onsubmit="return confirm('Yakin hapus? Menghapus gate akan menghapus semua block di dalamnya.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Belum ada data gate.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
