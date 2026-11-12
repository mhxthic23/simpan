<?php

namespace App\Http\Controllers;

use App\Models\Rubrik;
use App\Models\RubrikMarketplace;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RubrikMarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'kategori' => ['nullable', 'string', 'max:100']]);
        $rubrik = RubrikMarketplace::query()->where('status', 'published')
            ->with('pengajar:id,name')
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn (Builder $sub) => $sub->where('nama', 'like', "%{$term}%")->orWhere('deskripsi', 'like', "%{$term}%")))
            ->when($filters['kategori'] ?? null, fn (Builder $q, $category) => $q->where('kategori', $category))
            ->latest('published_at')->paginate(20)->withQueryString();
        return view('marketplace.index', compact('rubrik', 'filters'));
    }

    public function show(Request $request, RubrikMarketplace $rubrikMarketplace): View
    {
        abort_unless($rubrikMarketplace->status === 'published' || $request->user()->role === 'admin' || ($request->user()->role === 'pengajar' && $rubrikMarketplace->pengajar_id === $request->user()->id), 404);
        $rubrikMarketplace->load('pengajar:id,name');
        $tasks = $request->user()->role === 'pengajar'
            ? Tugas::query()->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->whereDoesntHave('rubrik')->orderBy('judul')->get()
            : collect();
        return view('marketplace.show', ['listing' => $rubrikMarketplace, 'tasks' => $tasks]);
    }

    public function create(): View
    {
        return view('marketplace.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $listing = RubrikMarketplace::create([
            'pengajar_id' => $request->user()->id,
            'nama' => $data['nama'], 'deskripsi' => $data['deskripsi'] ?? null, 'kategori' => $data['kategori'] ?? null,
            'struktur_rubrik' => $data['criteria'], 'status' => 'draft', 'is_verified' => false,
        ]);
        return redirect()->route('marketplace.show', $listing)->with('status', 'Template rubrik disimpan sebagai draft.');
    }

    public function publish(Request $request, RubrikMarketplace $rubrikMarketplace): RedirectResponse
    {
        $listing = RubrikMarketplace::query()->whereKey($rubrikMarketplace->id)->where('pengajar_id', $request->user()->id)->firstOrFail();
        $this->assertValidWeights($listing->struktur_rubrik);
        $listing->forceFill(['status' => 'published', 'published_at' => now()])->save();
        return back()->with('status', 'Template rubrik diajukan ke marketplace. Status verifikasi dapat dilihat pada detailnya.');
    }

    public function copy(Request $request, RubrikMarketplace $rubrikMarketplace): RedirectResponse
    {
        abort_unless($rubrikMarketplace->status === 'published', 404);
        $data = $request->validate(['tugas_id' => ['required', 'integer', Rule::exists('tugas', 'id')]]);
        $task = Tugas::query()->whereKey($data['tugas_id'])->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->firstOrFail();
        $criteria = $rubrikMarketplace->struktur_rubrik;
        $this->assertValidWeights($criteria);
        DB::transaction(function () use ($task, $criteria): void {
            $task = Tugas::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($task->rubrik()->exists()) throw ValidationException::withMessages(['tugas_id' => 'Tugas ini sudah memiliki kriteria rubrik.']);
            foreach ($criteria as $index => $item) $task->rubrik()->create([
                'nama' => $item['nama'], 'deskripsi' => $item['deskripsi'] ?? null,
                'bobot' => $item['bobot'], 'skor_maksimum' => $item['skor_maksimum'], 'urutan' => $index + 1,
            ]);
        });
        return redirect()->route('pengajar.tugas.rubrik.index', $task)->with('status', 'Kriteria template disalin ke tugas. Perubahan pada template marketplace tidak mengubah salinan ini.');
    }

    public function verify(RubrikMarketplace $rubrikMarketplace): RedirectResponse
    {
        abort_unless($rubrikMarketplace->status === 'published', 404);
        $rubrikMarketplace->forceFill(['is_verified' => ! $rubrikMarketplace->is_verified])->save();
        return back()->with('status', $rubrikMarketplace->is_verified ? 'Template ditandai terverifikasi.' : 'Status verifikasi template dicabut.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'], 'deskripsi' => ['nullable', 'string', 'max:10000'],
            'kategori' => ['nullable', 'string', 'max:100'], 'criteria' => ['required', 'array', 'min:1', 'max:30'],
            'criteria.*.nama' => ['required', 'string', 'max:150'], 'criteria.*.deskripsi' => ['nullable', 'string', 'max:5000'],
            'criteria.*.bobot' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:100'],
            'criteria.*.skor_maksimum' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:99999.99'],
        ], [
            'nama.required' => 'Nama template wajib diisi.',
            'nama.max' => 'Nama template maksimal 255 karakter.',
            'criteria.required' => 'Isi minimal satu kriteria.',
            'criteria.min' => 'Isi minimal satu kriteria.',
            'criteria.max' => 'Template maksimal berisi 30 kriteria.',
            'criteria.*.nama.required' => 'Nama setiap kriteria wajib diisi.',
            'criteria.*.bobot.required' => 'Bobot setiap kriteria wajib diisi.',
            'criteria.*.bobot.gt' => 'Bobot harus lebih besar dari nol.',
            'criteria.*.skor_maksimum.required' => 'Skor maksimum setiap kriteria wajib diisi.',
            'criteria.*.skor_maksimum.gt' => 'Skor maksimum harus lebih besar dari nol.',
        ]);
    }

    private function assertValidWeights(array $criteria): void
    {
        $total = collect($criteria)->sum(fn ($item) => (int) round((float) ($item['bobot'] ?? 0) * 100));
        if (! $criteria || $total !== 10000) throw ValidationException::withMessages(['criteria' => 'Total bobot template harus tepat 100% sebelum diterbitkan atau disalin.']);
    }
}
