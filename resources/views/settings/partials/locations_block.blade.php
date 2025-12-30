{{-- resources/views/settings/partials/locations_block.blade.php --}}
<h5>Data Block</h5>
<hr>
<div class="row">
    <div class="col-md-4">
        <h6>Tambah Block Baru</h6>
        <form action="{{ route('settings.locations.block.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="block_gate_id" class="form-label">Pilih Gate</label>
                <select name="gate_id" id="block_gate_id" class="form-select" required>
                    <option value="">-- Pilih Gate --</option>
                    @foreach ($gates as $gate)
                        <option value="{{ $gate->id }}">{{ $gate->name }} ({{ $gate->gudang->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="block_name" class="form-label">Nama Block</label>
                <input type="text" name="name" class="form-control" id="block_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
    <div class="col-md-8">
        <h6>Daftar Block</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Nama Block</th>
                        <th>Gate</th>
                        <th>Gudang</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blocks as $block)
                        <tr>
                            <td>{{ $block->name }}</td>
                            <td>{{ $block->gate->name }}</td>
                            <td>{{ $block->gate->gudang->name }}</td>
                            <td>
                                <form action="{{ route('settings.locations.block.destroy', $block->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data block.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
