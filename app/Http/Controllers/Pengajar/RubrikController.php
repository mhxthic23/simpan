<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Rubrik;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RubrikController extends Controller
{
    public function index(Request $request, Tugas $tugas): View
    {
        $tugas = $this->ownedTask($request, $tugas);
        $rubrik = $tugas->rubrik()->withCount('penilaianDetail')->orderBy('urutan')->get();
        $totalBobot = $rubrik->sum(fn (Rubrik $item) => (int) round((float) $item->bobot * 100));
        $terkunci = $rubrik->contains(fn (Rubrik $item) => $item->penilaian_detail_count > 0);

        return view('pengajar.rubrik.index', compact('tugas', 'rubrik', 'totalBobot', 'terkunci'));
    }

    public function store(Request $request, Tugas $tugas): RedirectResponse
    {
        $tugas = $this->ownedTask($request, $tugas);
        if ($tugas->rubrik()->exists()) {
            return back()->withErrors(['criteria' => 'Rubrik tugas ini sudah ada. Gunakan ubah untuk memperbaruinya.']);
        }

        $criteria = $this->validatedCriteria($request);
        try {
            DB::transaction(function () use ($tugas, $criteria): void {
                foreach ($criteria as $index => $item) {
                    $tugas->rubrik()->create($this->attributes($item, $index + 1));
                }
            });
        } catch (QueryException) {
            return back()->withErrors(['criteria' => 'Rubrik gagal disimpan. Periksa kembali datanya.'])->withInput();
        }

        return redirect()->route('pengajar.tugas.rubrik.index', $tugas)->with('status', 'Kriteria rubrik berhasil dibuat.');
    }

    public function update(Request $request, Tugas $tugas): RedirectResponse
    {
        $tugas = $this->ownedTask($request, $tugas);
        $criteria = $this->validatedCriteria($request, true);
        $existing = $tugas->rubrik()->withCount('penilaianDetail')->get();

        if ($existing->contains(fn (Rubrik $item) => $item->penilaian_detail_count > 0)) {
            return back()->withErrors(['criteria' => 'Rubrik tidak dapat diubah setelah digunakan dalam penilaian.'])->withInput();
        }

        $submittedIds = collect($criteria)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $ownedIds = $existing->pluck('id')->map(fn ($id) => (int) $id);
        if ($submittedIds->diff($ownedIds)->isNotEmpty()) {
            return back()->withErrors(['criteria' => 'Salah satu kriteria bukan bagian dari rubrik tugas ini.'])->withInput();
        }

        try {
            DB::transaction(function () use ($tugas, $criteria, $submittedIds, $ownedIds): void {
                $tugas->rubrik()->whereNotIn('id', $submittedIds->all())->delete();
                foreach ($criteria as $index => $item) {
                    $attributes = $this->attributes($item, $index + 1);
                    if (! empty($item['id'])) {
                        $tugas->rubrik()->whereKey($item['id'])->update($attributes);
                    } else {
                        $tugas->rubrik()->create($attributes);
                    }
                }
            });
        } catch (QueryException) {
            return back()->withErrors(['criteria' => 'Perubahan rubrik gagal disimpan karena datanya masih digunakan.'])->withInput();
        }

        return redirect()->route('pengajar.tugas.rubrik.index', $tugas)->with('status', 'Rubrik berhasil diperbarui.');
    }

    public function destroy(Request $request, Tugas $tugas, Rubrik $rubrik): RedirectResponse
    {
        $tugas = $this->ownedTask($request, $tugas);
        abort_unless($rubrik->tugas_id === $tugas->id, 404);

        if ($tugas->rubrik()->whereHas('penilaianDetail')->exists()) {
            return back()->with('error', 'Kriteria rubrik tidak dapat dihapus setelah rubrik digunakan dalam penilaian.');
        }

        try {
            DB::transaction(fn () => $rubrik->delete());
        } catch (QueryException) {
            return back()->with('error', 'Kriteria tidak dapat dihapus karena masih terkait dengan data penilaian.');
        }

        return back()->with('status', 'Kriteria rubrik berhasil dihapus.');
    }

    /** @return array<int, array<string, mixed>> */
    private function validatedCriteria(Request $request, bool $updating = false): array
    {
        $data = $request->validate([
            'criteria' => ['required', 'array', 'min:1', 'max:30'],
            'criteria.*.id' => ['nullable', 'integer', 'distinct'],
            'criteria.*.nama' => ['required', 'string', 'max:150'],
            'criteria.*.deskripsi' => ['nullable', 'string', 'max:5000'],
            'criteria.*.bobot' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:100'],
            'criteria.*.skor_maksimum' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:99999.99'],
        ], [
            'criteria.required' => 'Tambahkan setidaknya satu kriteria.',
            'criteria.min' => 'Tambahkan setidaknya satu kriteria.',
            'criteria.max' => 'Rubrik maksimal memiliki 30 kriteria.',
            'criteria.*.id.integer' => 'ID kriteria tidak valid.',
            'criteria.*.id.distinct' => 'Kriteria yang sama tidak boleh dikirim lebih dari satu kali.',
            'criteria.*.nama.required' => 'Nama setiap kriteria wajib diisi.',
            'criteria.*.nama.max' => 'Nama kriteria maksimal 150 karakter.',
            'criteria.*.deskripsi.max' => 'Indikator kriteria maksimal 5000 karakter.',
            'criteria.*.bobot.required' => 'Bobot setiap kriteria wajib diisi.',
            'criteria.*.bobot.numeric' => 'Bobot harus berupa angka.',
            'criteria.*.bobot.decimal' => 'Bobot maksimal memiliki dua angka desimal.',
            'criteria.*.bobot.gt' => 'Bobot harus lebih besar dari nol.',
            'criteria.*.bobot.lte' => 'Bobot tidak boleh lebih dari 100%.',
            'criteria.*.skor_maksimum.required' => 'Skor maksimum wajib diisi.',
            'criteria.*.skor_maksimum.numeric' => 'Skor maksimum harus berupa angka.',
            'criteria.*.skor_maksimum.decimal' => 'Skor maksimum maksimal memiliki dua angka desimal.',
            'criteria.*.skor_maksimum.gt' => 'Skor maksimum harus lebih besar dari nol.',
        ]);

        $totalCents = collect($data['criteria'])->sum(fn (array $item) => (int) round((float) $item['bobot'] * 100));
        if ($totalCents > 10000) {
            throw ValidationException::withMessages(['criteria' => 'Total bobot rubrik tidak boleh melebihi 100%.']);
        }

        return $data['criteria'];
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function attributes(array $item, int $order): array
    {
        return [
            'nama' => $item['nama'],
            'deskripsi' => $item['deskripsi'] ?? null,
            'bobot' => $item['bobot'],
            'skor_maksimum' => $item['skor_maksimum'],
            'urutan' => $order,
        ];
    }

    private function ownedTask(Request $request, Tugas $tugas): Tugas
    {
        return Tugas::query()->whereKey($tugas->id)
            ->whereHas('kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id))
            ->firstOrFail();
    }
}
