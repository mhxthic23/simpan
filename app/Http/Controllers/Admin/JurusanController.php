<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Jurusan;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurusanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'fakultas_id' => ['nullable', 'integer', 'exists:fakultas,id'],
        ], [
            'q.string' => 'Kata pencarian tidak valid.',
            'q.max' => 'Pencarian maksimal 150 karakter.',
            'fakultas_id.integer' => 'Fakultas yang dipilih tidak valid.',
            'fakultas_id.exists' => 'Fakultas yang dipilih tidak ditemukan.',
        ]);

        $jurusan = Jurusan::query()
            ->with('fakultas')
            ->withCount('kelas')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('nama', 'like', "%{$term}%"))
            ->when($filters['fakultas_id'] ?? null, fn ($query, $id) => $query->where('fakultas_id', $id))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();
        $fakultasList = Fakultas::orderBy('nama')->get();

        return view('admin.akademik.jurusan.index', compact('jurusan', 'fakultasList', 'filters'));
    }

    public function create(): View
    {
        return view('admin.akademik.jurusan.create', ['fakultasList' => Fakultas::orderBy('nama')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'nama' => ['required', 'string', 'max:150', Rule::unique('jurusan', 'nama')->where('fakultas_id', $request->input('fakultas_id'))],
        ], $this->messages());

        Jurusan::create($data);

        return redirect()->route('admin.jurusan.index')->with('status', 'Jurusan berhasil ditambahkan.');
    }

    public function edit(Jurusan $jurusan): View
    {
        return view('admin.akademik.jurusan.edit', [
            'jurusan' => $jurusan,
            'fakultasList' => Fakultas::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $data = $request->validate([
            'fakultas_id' => ['required', 'integer', 'exists:fakultas,id'],
            'nama' => ['required', 'string', 'max:150', Rule::unique('jurusan', 'nama')->where('fakultas_id', $request->input('fakultas_id'))->ignore($jurusan->id)],
        ], $this->messages());

        $jurusan->update($data);

        return redirect()->route('admin.jurusan.index')->with('status', 'Data jurusan berhasil diperbarui.');
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        try {
            DB::transaction(fn () => $jurusan->delete());
        } catch (QueryException) {
            return back()->with('error', 'Jurusan tidak dapat dihapus karena masih memiliki kelas terkait.');
        }

        return redirect()->route('admin.jurusan.index')->with('status', 'Jurusan berhasil dihapus.');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'fakultas_id.required' => 'Fakultas wajib dipilih.',
            'fakultas_id.integer' => 'Fakultas yang dipilih tidak valid.',
            'fakultas_id.exists' => 'Fakultas yang dipilih tidak ditemukan.',
            'nama.required' => 'Nama jurusan wajib diisi.',
            'nama.string' => 'Nama jurusan harus berupa teks.',
            'nama.max' => 'Nama jurusan maksimal 150 karakter.',
            'nama.unique' => 'Nama jurusan sudah digunakan pada fakultas tersebut.',
        ];
    }
}
