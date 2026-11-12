<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FakultasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ], ['q.string' => 'Kata pencarian tidak valid.', 'q.max' => 'Pencarian maksimal 150 karakter.']);

        $fakultas = Fakultas::query()
            ->withCount('jurusan')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('nama', 'like', "%{$term}%"))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('admin.akademik.fakultas.index', compact('fakultas', 'filters'));
    }

    public function create(): View
    {
        return view('admin.akademik.fakultas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:fakultas,nama'],
        ], $this->messages());

        Fakultas::create($data);

        return redirect()->route('admin.fakultas.index')->with('status', 'Fakultas berhasil ditambahkan.');
    }

    public function edit(Fakultas $fakultas): View
    {
        return view('admin.akademik.fakultas.edit', compact('fakultas'));
    }

    public function update(Request $request, Fakultas $fakultas): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('fakultas', 'nama')->ignore($fakultas->id)],
        ], $this->messages());

        $fakultas->update($data);

        return redirect()->route('admin.fakultas.index')->with('status', 'Data fakultas berhasil diperbarui.');
    }

    public function destroy(Fakultas $fakultas): RedirectResponse
    {
        try {
            DB::transaction(fn () => $fakultas->delete());
        } catch (QueryException) {
            return back()->with('error', 'Fakultas tidak dapat dihapus karena masih memiliki jurusan terkait.');
        }

        return redirect()->route('admin.fakultas.index')->with('status', 'Fakultas berhasil dihapus.');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'nama.required' => 'Nama fakultas wajib diisi.',
            'nama.string' => 'Nama fakultas harus berupa teks.',
            'nama.max' => 'Nama fakultas maksimal 150 karakter.',
            'nama.unique' => 'Nama fakultas tersebut sudah digunakan.',
        ];
    }
}
