<?php

namespace App\Http\Controllers\Siswa;

use App\Contracts\IpfsAdapter;
use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Penilaian;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $portfolio = Portfolio::query()->where('siswa_id', $request->user()->id)
            ->with(['pengumpulanTugas.tugas', 'penilaian'])->latest()->paginate(20);
        return view('portfolio.index', compact('portfolio'));
    }

    public function create(Request $request): View
    {
        return view('portfolio.create', ['portfolio' => new Portfolio(), 'submissions' => $this->ownedSubmissions($request)]);
    }

    public function store(Request $request, IpfsAdapter $ipfs): RedirectResponse
    {
        $data = $this->validated($request);
        [$submission, $assessment] = $this->selectedAcademicWork($request, $data);
        $path = $this->storePrivateFile($request);
        $cid = null;
        try {
            if ($request->boolean('publish_to_ipfs')) {
                if (! $path || ! $data['is_public']) throw ValidationException::withMessages(['publish_to_ipfs' => 'Upload IPFS hanya tersedia untuk portfolio publik yang memiliki berkas.']);
                $file = $request->file('file');
                $result = $ipfs->upload(Storage::disk('local')->path($path), basename($path), $file->getMimeType() ?: 'application/octet-stream');
                $cid = $result['cid'];
            }
            $portfolio = DB::transaction(fn () => Portfolio::create([
                'siswa_id' => $request->user()->id,
                'pengumpulan_tugas_id' => $submission?->id,
                'penilaian_id' => $assessment?->id,
                'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
                'file_path' => $path, 'ipfs_cid' => $cid,
                'is_public' => $data['is_public'], 'published_at' => $data['is_public'] ? now() : null,
            ]));
        } catch (Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            if ($exception instanceof ValidationException) throw $exception;
            if ($request->boolean('publish_to_ipfs')) return back()->withErrors(['publish_to_ipfs' => 'Unggah IPFS gagal. Karya tetap privat dan belum dibuat.'])->withInput();
            throw $exception;
        }
        return redirect()->route('siswa.portfolio.show', $portfolio)->with('status', 'Karya portfolio berhasil disimpan.');
    }

    public function show(Request $request, Portfolio $portfolio): View
    {
        $portfolio = $this->visible($request, $portfolio)->with(['siswa', 'pengumpulanTugas.tugas', 'penilaian'])->firstOrFail();
        return view('portfolio.show', compact('portfolio'));
    }

    public function edit(Request $request, Portfolio $portfolio): View
    {
        $portfolio = Portfolio::query()->whereKey($portfolio->id)->where('siswa_id', $request->user()->id)->firstOrFail();
        return view('portfolio.edit', ['portfolio' => $portfolio, 'submissions' => $this->ownedSubmissions($request)]);
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio = Portfolio::query()->whereKey($portfolio->id)->where('siswa_id', $request->user()->id)->firstOrFail();
        $data = $this->validated($request, false);
        [$submission, $assessment] = $this->selectedAcademicWork($request, $data);
        $newPath = $this->storePrivateFile($request);
        $oldPath = $portfolio->file_path;
        $oldCid = $portfolio->ipfs_cid;
        $isPublic = $data['is_public'];
        $portfolio->update([
            'pengumpulan_tugas_id' => $submission?->id,
            'penilaian_id' => $assessment?->id,
            'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
            'file_path' => $newPath ?: $oldPath,
            'ipfs_cid' => $newPath ? null : $oldCid,
            'is_public' => $isPublic,
            'published_at' => $isPublic ? ($portfolio->published_at ?? now()) : null,
        ]);
        if ($newPath && $oldPath) Storage::disk('local')->delete($oldPath);
        if ((! $isPublic || $newPath) && $oldCid) session()->flash('warning', 'CID IPFS terdahulu bersifat publik permanen dan tidak dapat ditarik kembali.');
        return redirect()->route('siswa.portfolio.show', $portfolio)->with('status', 'Portfolio berhasil diperbarui.');
    }

    public function destroy(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio = Portfolio::query()->whereKey($portfolio->id)->where('siswa_id', $request->user()->id)->firstOrFail();
        $path = $portfolio->file_path;
        $cid = $portfolio->ipfs_cid;
        $portfolio->delete();
        if ($path) Storage::disk('local')->delete($path);
        return redirect()->route('siswa.portfolio.index')->with('status', $cid ? 'Portfolio dihapus dari SIMPAN. CID publik IPFS tetap dapat diakses melalui gateway.' : 'Portfolio berhasil dihapus.');
    }

    public function download(Request $request, Portfolio $portfolio): StreamedResponse
    {
        $portfolio = $this->visible($request, $portfolio)->firstOrFail();
        abort_unless($portfolio->file_path && Storage::disk('local')->exists($portfolio->file_path), 404);
        return Storage::disk('local')->download($portfolio->file_path);
    }

    private function visible(Request $request, Portfolio $portfolio): Builder
    {
        $user = $request->user();
        return Portfolio::query()->whereKey($portfolio->id)->where(function (Builder $query) use ($user): void {
            $query->where('siswa_id', $user->id)
                ->orWhere(fn (Builder $public) => $public->where('is_public', true)->whereNotNull('published_at'));
            if ($user->role === 'pengajar') {
                $query->orWhere(fn (Builder $shared) => $shared->where('is_public', true)->whereNotNull('published_at')
                    ->whereHas('siswa.keanggotaanKelas.kelas', fn (Builder $class) => $class->where('pengajar_id', $user->id)));
            }
        });
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'pengumpulan_tugas_id' => ['nullable', 'integer', Rule::exists('pengumpulan_tugas', 'id')],
            'penilaian_id' => ['nullable', 'integer', Rule::exists('penilaian', 'id')],
            'is_public' => ['required', 'boolean'],
            'publish_consent' => [$request->boolean('is_public') ? 'accepted' : 'nullable'],
            'publish_to_ipfs' => ['sometimes', 'accepted'],
            'file' => [$creating ? 'nullable' : 'sometimes', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt'],
        ], [
            'judul.required' => 'Judul portfolio wajib diisi.',
            'publish_consent.accepted' => 'Konfirmasi bahwa karya ini boleh dipublikasikan wajib dicentang.',
            'file.max' => 'Berkas portfolio maksimal 10 MB.',
            'file.mimes' => 'Format portfolio yang didukung: PDF, PNG, JPG, dan TXT.',
        ]);
        $data['is_public'] = (bool) $data['is_public'];
        return $data;
    }

    /** @return array{PengumpulanTugas|null, Penilaian|null} */
    private function selectedAcademicWork(Request $request, array $data): array
    {
        $submission = null;
        $assessment = null;
        if (! empty($data['pengumpulan_tugas_id'])) {
            $submission = PengumpulanTugas::query()->whereKey($data['pengumpulan_tugas_id'])->where('siswa_id', $request->user()->id)->firstOrFail();
        }
        if (! empty($data['penilaian_id'])) {
            $assessment = Penilaian::query()->whereKey($data['penilaian_id'])
                ->where('status', 'published')->whereNotNull('published_at')
                ->whereHas('pengumpulanTugas', fn (Builder $q) => $q->where('siswa_id', $request->user()->id))
                ->firstOrFail();
            if ($submission && (int) $assessment->pengumpulan_tugas_id !== (int) $submission->id) throw ValidationException::withMessages(['penilaian_id' => 'Nilai yang dipilih tidak sesuai dengan pengumpulan.']);
            $submission ??= $assessment->pengumpulanTugas;
        }
        return [$submission, $assessment];
    }

    private function storePrivateFile(Request $request): ?string
    {
        if (! $request->hasFile('file')) return null;
        return $request->file('file')->storeAs('portfolio/'.$request->user()->id, Str::uuid().'.'.$request->file('file')->extension(), 'local');
    }

    private function ownedSubmissions(Request $request)
    {
        return PengumpulanTugas::query()->where('siswa_id', $request->user()->id)->with(['tugas', 'penilaian'])
            ->latest('submitted_at')->get();
    }
}
