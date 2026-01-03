<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Gate;
use App\Models\Gudang;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display the location management page.
     */
    public function index()
    {
        $gudangs = Gudang::orderBy('name')->get();
        $gates = Gate::with('gudang')->orderBy('name')->get();
        $blocks = Block::with('gate.gudang')->orderBy('name')->get();

        return view('settings.locations', compact('gudangs', 'gates', 'blocks'));
    }

    /**
     * Store a new Gudang.
     */
    public function storeGudang(Request $request)
    {
        $messages = [
            'name.required' => 'Nama gudang wajib diisi.',
            'name.unique'   => 'Nama gudang ini sudah ada di sistem.',
        ];

        $request->validate([
            'name' => 'required|string|max:255|unique:gudangs,name'
        ], $messages);

        Gudang::create($request->all());

        return back()->with('success', 'Gudang baru berhasil ditambahkan.');
    }

    /**
     * Delete a Gudang.
     */
    public function destroyGudang($id)
    {
        try {
            Gudang::findOrFail($id)->delete();
            return back()->with('success', 'Gudang berhasil dihapus.');
        } catch (\Exception $e) {
            // Error ini biasanya karena Foreign Key Constraint (Gudang masih dipakai di Gate/Produk)
            return back()->with('error', 'Gagal menghapus gudang. Pastikan gudang ini sudah kosong (tidak ada Gate atau Produk yang terdaftar di sini).');
        }
    }

    /**
     * Store a new Gate.
     */
    public function storeGate(Request $request)
    {
        $messages = [
            'gudang_id.required' => 'Pilih gudang terlebih dahulu.',
            'name.required'      => 'Nama Gate/Lorong wajib diisi.',
        ];

        $request->validate([
            'gudang_id' => 'required|exists:gudangs,id',
            'name'      => 'required|string|max:255',
        ], $messages);

        Gate::create($request->all());

        return back()->with('success', 'Gate/Lorong berhasil ditambahkan.');
    }

    /**
     * Delete a Gate.
     */
    public function destroyGate($id)
    {
        try {
            Gate::findOrFail($id)->delete();
            return back()->with('success', 'Gate berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus Gate. Pastikan tidak ada Block/Rak yang terdaftar di Gate ini.');
        }
    }

    /**
     * Store a new Block.
     */
    public function storeBlock(Request $request)
    {
        $messages = [
            'gate_id.required' => 'Pilih Gate/Lorong terlebih dahulu.',
            'name.required'    => 'Nama Block/Rak wajib diisi.',
            'name.unique'      => 'Nama Block ini sudah ada di Gate tersebut.',
        ];

        $request->validate([
            'gate_id' => 'required|exists:gates,id',
            // Validasi unik: Nama block harus unik DI DALAM Gate yang sama
            'name' => 'required|string|max:255|unique:blocks,name,NULL,id,gate_id,' . $request->gate_id,
        ], $messages);

        Block::create($request->all());

        return back()->with('success', 'Block/Rak berhasil ditambahkan.');
    }

    /**
     * Delete a Block.
     */
    public function destroyBlock($id)
    {
        try {
            Block::findOrFail($id)->delete();
            return back()->with('success', 'Block berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus Block. Pastikan tidak ada Produk yang tersimpan di Block ini.');
        }
    }

    /**
     * Get Gates for a given Gudang for AJAX requests.
     */
    public function getGatesByGudang($gudangId)
    {
        $gates = Gate::where('gudang_id', $gudangId)->orderBy('name')->get();
        return response()->json($gates);
    }

    /**
     * Get Blocks for a given Gate for AJAX requests.
     */
    public function getBlocksByGate($gateId)
    {
        $blocks = Block::where('gate_id', $gateId)->orderBy('name')->get();
        return response()->json($blocks);
    }
}
