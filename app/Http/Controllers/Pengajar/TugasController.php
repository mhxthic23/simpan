<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Tugas;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\NotificationService;

class TugasController extends Controller
{
    private const ALLOWED_FILE_TYPES = ['pdf', 'doc', 'docx', 'odt', 'txt', 'zip'];
    private const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'kelas_id' => ['nullable', 'integer', Rule::exists('kelas', 'id')->where('pengajar_id', $request->user()->id)],
        ], [
            'q.string' => 'Kata pencarian tidak valid.',
            'q.max' => 'Pencarian maksimal 255 karakter.',
            'kelas_id.integer' => 'Kelas yang dipilih tidak valid.',
            'kelas_id.exists' => 'Kelas tersebut tidak diajar oleh akun Anda.',
        ]);

        $tugas = $this->ownedTasks($request)
            ->with(['kelas' => fn ($query) => $query->with(['jurusan'])->withCount(['siswa' => fn ($siswa) => $siswa->where('role', 'siswa')])])
            ->withCount(['pengumpulanTugas as siswa_sudah_mengumpulkan_count' => fn (Builder $query) => $query
                ->select(DB::raw('count(distinct siswa_id)'))
                ->whereHas('siswa.keanggotaanKelas', fn (Builder $membership) => $membership->whereColumn('kelas_siswa.kelas_id', 'tugas.kelas_id'))])
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where('judul', 'like', "%{$term}%"))
            ->when($filters['kelas_id'] ?? null, fn (Builder $query, int $id) => $query->where('kelas_id', $id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengajar.tugas.index', [
            'tugas' => $tugas,
            'filters' => $filters,
            'kelasList' => $request->user()->kelasDiajar()->with('jurusan')->orderBy('nama')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('pengajar.tugas.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request), $this->messages());
        $data['pengajar_id'] = $request->user()->id;
        $data['deadline_at'] = $data['deadline_at'];
        $data['max_file_size'] = empty($data['max_file_size_mb']) ? null : (int) $data['max_file_size_mb'] * 1024 * 1024;
        unset($data['max_file_size_mb']);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $tugas = Tugas::create($data);

        if ($tugas->status === 'published') $this->notifyPublication($tugas);

        return redirect()->route('pengajar.tugas.show', $tugas)->with('status', 'Tugas berhasil dibuat.');
    }

    public function show(Request $request, Tugas $tugas): View
    {
        $tugas = $this->ownedTask($request, $tugas)->load(['kelas.jurusan.fakultas', 'kelas.pengajar']);
        $jumlahSiswa = $tugas->kelas->siswa()->where('role', 'siswa')->count();
        $jumlahSudahMengumpulkan = $tugas->pengumpulanTugas()
            ->whereHas('siswa.keanggotaanKelas', fn (Builder $query) => $query->where('kelas_id', $tugas->kelas_id))
            ->distinct('siswa_id')
            ->count('siswa_id');
        $jumlahBelumMengumpulkan = max(0, $jumlahSiswa - $jumlahSudahMengumpulkan);
        $pengumpulanTerbaru = $tugas->pengumpulanTugas()->with('siswa')->latest('submitted_at')->limit(5)->get();

        return view('pengajar.tugas.show', compact('tugas', 'jumlahSiswa', 'jumlahSudahMengumpulkan', 'jumlahBelumMengumpulkan', 'pengumpulanTerbaru'));
    }

    public function edit(Request $request, Tugas $tugas): View
    {
        $tugas = $this->ownedTask($request, $tugas);
        return view('pengajar.tugas.edit', ['tugas' => $tugas] + $this->formData($request));
    }

    public function update(Request $request, Tugas $tugas): RedirectResponse
    {
        $tugas = $this->ownedTask($request, $tugas);
        $data = $request->validate($this->rules($request), $this->messages());
        $data['pengajar_id'] = $request->user()->id;
        $data['max_file_size'] = empty($data['max_file_size_mb']) ? null : (int) $data['max_file_size_mb'] * 1024 * 1024;
        unset($data['max_file_size_mb']);
        $data['published_at'] = match (true) {
            $data['status'] === 'draft' => null,
            $tugas->published_at !== null => $tugas->published_at,
            default => now(),
        };

        $wasPublished = $tugas->status === 'published' && $tugas->published_at !== null;
        $tugas->update($data);
        if (! $wasPublished && $tugas->status === 'published') $this->notifyPublication($tugas);

        return redirect()->route('pengajar.tugas.show', $tugas)->with('status', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Tugas $tugas): RedirectResponse
    {
        $tugas = $this->ownedTask($request, $tugas);

        if ($tugas->pengumpulanTugas()->exists() || $tugas->rubrik()->exists()) {
            return back()->with('error', 'Tugas tidak dapat dihapus karena sudah memiliki pengumpulan atau rubrik yang perlu dipertahankan.');
        }

        try {
            DB::transaction(fn () => $tugas->delete());
        } catch (QueryException) {
            return back()->with('error', 'Tugas tidak dapat dihapus karena masih memiliki data terkait.');
        }

        return redirect()->route('pengajar.tugas.index')->with('status', 'Tugas berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function formData(Request $request): array
    {
        return [
            'kelasList' => $request->user()->kelasDiajar()->with('jurusan')->orderBy('nama')->get(),
            'allowedTypes' => self::ALLOWED_FILE_TYPES,
            'maxFileSizeMb' => self::MAX_FILE_SIZE_BYTES / 1024 / 1024,
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')->where('pengajar_id', $request->user()->id)],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:20000'],
            'deadline_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'max_file_size_mb' => ['nullable', 'integer', 'min:1', 'max:10'],
            'allowed_file_types' => ['required', 'array', 'min:1'],
            'allowed_file_types.*' => ['required', 'string', Rule::in(self::ALLOWED_FILE_TYPES)],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'kelas_id.required' => 'Kelas tujuan wajib dipilih.',
            'kelas_id.integer' => 'Kelas tujuan tidak valid.',
            'kelas_id.exists' => 'Kelas tujuan tidak ditemukan atau bukan kelas yang Anda ajar.',
            'judul.required' => 'Judul tugas wajib diisi.',
            'judul.string' => 'Judul tugas harus berupa teks.',
            'judul.max' => 'Judul tugas maksimal 255 karakter.',
            'deskripsi.string' => 'Instruksi tugas harus berupa teks.',
            'deskripsi.max' => 'Instruksi tugas terlalu panjang.',
            'deadline_at.required' => 'Tenggat tugas wajib diisi.',
            'deadline_at.date' => 'Format tenggat tugas tidak valid.',
            'status.required' => 'Status publikasi wajib dipilih.',
            'status.in' => 'Status tugas tidak valid.',
            'max_file_size_mb.integer' => 'Ukuran maksimum file harus berupa angka.',
            'max_file_size_mb.min' => 'Ukuran maksimum file minimal 1 MB.',
            'max_file_size_mb.max' => 'Ukuran maksimum file paling besar 10 MB.',
            'allowed_file_types.required' => 'Pilih setidaknya satu format file.',
            'allowed_file_types.array' => 'Format file tidak valid.',
            'allowed_file_types.min' => 'Pilih setidaknya satu format file.',
            'allowed_file_types.*.in' => 'Format file yang dipilih tidak didukung.',
        ];
    }

    private function ownedTasks(Request $request): Builder
    {
        return Tugas::query()
            ->whereHas('kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id));
    }

    private function ownedTask(Request $request, Tugas $tugas): Tugas
    {
        return $this->ownedTasks($request)->whereKey($tugas->id)->firstOrFail();
    }

    private function notifyPublication(Tugas $task): void
    {
        try {
            $task->load('kelas.siswa');
            foreach ($task->kelas->siswa->where('role', 'siswa') as $student) {
                app(NotificationService::class)->sendOnce(
                    $student, 'Tugas baru', 'Tugas "'.$task->judul.'" telah diterbitkan.', 'task_published',
                    'task:'.$task->id.':published:'.$task->published_at?->timestamp.':'.$student->id, $task
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
