<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Bidang;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    /**
     * Menampilkan daftar pegawai dengan fitur pencarian dan filter
     */
    public function index(Request $request)
    {
        // Query dasar untuk pegawai
        $query = Pegawai::query()->with('bidang');

        // Fitur Pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('bidang', function ($subQuery) use ($search) {
                        $subQuery->where('nama_bidang', 'like', "%{$search}%");
                    });
            });
        }

        // Fitur Filter Bidang
        if ($request->filled('bidang')) {
            $query->where('bidang_id', $request->input('bidang'));
        }

        // Fitur Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Fitur Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validasi kolom sorting
        $allowedSortColumns = ['nip', 'nama', 'email', 'status', 'created_at', 'tanggal_masuk'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        // Ambil data pegawai dengan pagination
        $pegawais = $query->paginate(10);

        // Ambil daftar bidang untuk dropdown filter
        $bidangs = Bidang::all();

        // Kembalikan view dengan data
        return view('pages.pegawai.index', [
            'pegawais' => $pegawais,
            'bidangs' => $bidangs,
            'filterSearch' => $request->input('search'),
            'filterBidang' => $request->input('bidang'),
            'filterStatus' => $request->input('status'),
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'totalPegawai' => Pegawai::count(),
            'pegawaiAktif' => Pegawai::where('status', 'Aktif')->count(),
            'pegawaiCuti' => Pegawai::where('status', 'Cuti')->count(),
            'totalDepartemen' => Bidang::count(),
        ]);
    }

    /**
     * Menampilkan form untuk menambah pegawai baru
     */
    public function create()
    {
        $bidangs = Bidang::all();
        return view('pages.pegawai.create', compact('bidangs'));
    }

    /**
     * Menyimpan pegawai baru ke database
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'nip' => 'required|unique:pegawais,nip',
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawais,email',
            'bidang_id' => 'required|exists:bidangs,id',
            'jabatan' => 'required|string|max:255',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:Aktif,Cuti,Non-Aktif',
            'no_telepon' => 'nullable|string|max:20',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'avatar' => 'nullable|image|max:2048'
        ]);

        // Simpan data pegawai
        Pegawai::create($request->all());

        return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil ditambahkan');
    }

    /**
     * Menampilkan detail pegawai
     */
    public function show($id)
    {
        $pegawai = Pegawai::with('bidang')->findOrFail($id);
        return view('pages.pegawai.show', compact('pegawai'));
    }

    /**
     * Menampilkan form untuk mengedit pegawai
     */
    public function edit($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        $bidangs = Bidang::all();
        return view('pages.pegawai.edit', compact('pegawai', 'bidangs'));
    }

    /**
     * Memperbarui data pegawai
     */
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::findOrFail($id);

        // Validasi input
        $request->validate([
            'nip' => 'required|unique:pegawais,nip,' . $pegawai->id,
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawais,email,' . $pegawai->id,
            'bidang_id' => 'required|exists:bidangs,id',
            'jabatan' => 'required|string|max:255',
            'tanggal_masuk' => 'required|date',
            'status' => 'required|in:Aktif,Cuti,Non-Aktif',
            'no_telepon' => 'nullable|string|max:20',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'avatar' => 'nullable|image|max:2048'
        ]);

        // Update data pegawai
        $pegawai->update($request->all());

        return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil diperbarui');
    }

    /**
     * Menghapus pegawai
     */
    public function destroy($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('success', 'Pegawai berhasil dihapus');
    }
}
