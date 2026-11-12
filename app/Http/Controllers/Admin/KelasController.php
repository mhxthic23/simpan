<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'jurusan_id' => ['nullable', 'integer', 'exists:jurusan,id'],
            'pengajar_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'pengajar')],
        ], [
            'q.string' => 'Kata pencarian tidak valid.',
            'q.max' => 'Pencarian maksimal 150 karakter.',
            'jurusan_id.integer' => 'Jurusan yang dipilih tidak valid.',
            'jurusan_id.exists' => 'Jurusan yang dipilih tidak ditemukan.',
            'pengajar_id.integer' => 'Pengajar yang dipilih tidak valid.',
            'pengajar_id.exists' => 'Pengajar yang dipilih tidak ditemukan.',
        ]);

        $kelas = Kelas::query()
            ->with(['jurusan.fakultas', 'pengajar'])
            ->withCount('siswa')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('nama', 'like', "%{$term}%"))
            ->when($filters['jurusan_id'] ?? null, fn ($query, $id) => $query->where('jurusan_id', $id))
            ->when($filters['pengajar_id'] ?? null, fn ($query, $id) => $query->where('pengajar_id', $id))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();
        $jurusanList = Jurusan::with('fakultas')->orderBy('nama')->get();
        $pengajarList = User::where('role', 'pengajar')->orderBy('name')->get();

        return view('admin.akademik.kelas.index', compact('kelas', 'filters', 'jurusanList', 'pengajarList'));
    }

    public function create(): View
    {
        return view('admin.akademik.kelas.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());
        Kelas::create($data);

        return redirect()->route('admin.kelas.index')->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas): View
    {
        $kelas->load(['jurusan.fakultas', 'pengajar', 'siswa' => fn ($query) => $query->orderBy('name')]);
        $siswaTersedia = User::query()
            ->where('role', 'siswa')
            ->whereDoesntHave('keanggotaanKelas', fn ($query) => $query->where('kelas_id', $kelas->id))
            ->orderBy('name')
            ->get();
        $kelasTujuan = Kelas::where('id', '<>', $kelas->id)->with('jurusan.fakultas')->orderBy('nama')->get();

        return view('admin.akademik.kelas.show', compact('kelas', 'siswaTersedia', 'kelasTujuan'));
    }

    public function edit(Kelas $kelas): View
    {
        return view('admin.akademik.kelas.edit', ['kelas' => $kelas] + $this->formOptions());
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());
        $kelas->update($data);

        return redirect()->route('admin.kelas.index')->with('status', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        try {
            DB::transaction(fn () => $kelas->delete());
        } catch (QueryException) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki tugas atau aktivitas akademik terkait.');
        }

        return redirect()->route('admin.kelas.index')->with('status', 'Kelas berhasil dihapus. Keanggotaan siswa pada kelas ini juga dihapus.');
    }

    public function addStudent(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'siswa')],
        ], [
            'siswa_id.required' => 'Siswa wajib dipilih.',
            'siswa_id.integer' => 'Akun siswa tidak valid.',
            'siswa_id.exists' => 'Akun tidak ditemukan atau bukan siswa aktif.',
        ]);

        try {
            DB::transaction(fn () => KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $data['siswa_id']]));
        } catch (QueryException) {
            return back()->withErrors(['siswa_id' => 'Siswa tersebut sudah terdaftar di kelas ini atau data relasinya tidak valid.']);
        }

        return back()->with('status', 'Siswa berhasil ditambahkan ke kelas.');
    }

    public function removeStudent(Kelas $kelas, User $user): RedirectResponse
    {
        $deleted = DB::transaction(fn () => KelasSiswa::query()
            ->where('kelas_id', $kelas->id)
            ->where('siswa_id', $user->id)
            ->delete());

        if (! $deleted) {
            return back()->with('error', 'Siswa tersebut tidak terdaftar di kelas ini.');
        }

        return back()->with('status', 'Siswa berhasil dikeluarkan dari kelas.');
    }

    public function moveStudent(Request $request, Kelas $kelas, User $user): RedirectResponse
    {
        $data = $request->validate([
            'kelas_tujuan_id' => ['required', 'integer', 'exists:kelas,id'],
        ], [
            'kelas_tujuan_id.required' => 'Kelas tujuan wajib dipilih.',
            'kelas_tujuan_id.integer' => 'Kelas tujuan tidak valid.',
            'kelas_tujuan_id.exists' => 'Kelas tujuan tidak ditemukan.',
        ]);

        if ((int) $data['kelas_tujuan_id'] === $kelas->id) {
            return back()->withErrors(['kelas_tujuan_id' => 'Kelas tujuan harus berbeda dari kelas asal.']);
        }

        if ($user->role !== 'siswa') {
            return back()->withErrors(['siswa' => 'Hanya akun dengan role siswa yang dapat dipindahkan.']);
        }

        try {
            DB::transaction(function () use ($kelas, $user, $data): void {
                $membership = KelasSiswa::query()
                    ->where('kelas_id', $kelas->id)
                    ->where('siswa_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($membership === null) {
                    throw new \RuntimeException('Siswa tidak terdaftar pada kelas asal.');
                }

                if (KelasSiswa::where('kelas_id', $data['kelas_tujuan_id'])->where('siswa_id', $user->id)->exists()) {
                    throw new \RuntimeException('Siswa sudah terdaftar pada kelas tujuan.');
                }

                $membership->delete();
                KelasSiswa::create(['kelas_id' => $data['kelas_tujuan_id'], 'siswa_id' => $user->id]);
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['kelas_tujuan_id' => $exception->getMessage()]);
        } catch (QueryException) {
            return back()->withErrors(['kelas_tujuan_id' => 'Siswa tidak dapat dipindahkan karena relasi kelas tidak valid.']);
        }

        return redirect()->route('admin.kelas.show', $data['kelas_tujuan_id'])->with('status', 'Siswa berhasil dipindahkan ke kelas tujuan.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'jurusanList' => Jurusan::with('fakultas')->orderBy('nama')->get(),
            'pengajarList' => User::where('role', 'pengajar')->orderBy('name')->get(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'jurusan_id' => ['required', 'integer', 'exists:jurusan,id'],
            'pengajar_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'pengajar')],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'nama.required' => 'Nama kelas wajib diisi.',
            'nama.string' => 'Nama kelas harus berupa teks.',
            'nama.max' => 'Nama kelas maksimal 150 karakter.',
            'jurusan_id.required' => 'Jurusan wajib dipilih.',
            'jurusan_id.integer' => 'Jurusan yang dipilih tidak valid.',
            'jurusan_id.exists' => 'Jurusan yang dipilih tidak ditemukan.',
            'pengajar_id.required' => 'Pengajar wajib dipilih.',
            'pengajar_id.integer' => 'Pengajar yang dipilih tidak valid.',
            'pengajar_id.exists' => 'Akun pengajar tidak ditemukan atau tidak memiliki role pengajar.',
        ];
    }
}
